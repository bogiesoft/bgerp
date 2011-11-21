<?php 


/**
 * Директорията, където ще се съхраняват временните файлове
 */
defIfNot('IMAP_TEMP_PATH', EF_TEMP_PATH . "/imap/");


/**
 * Максимално време за еднократно фетчване на писма
 */
defIfNot('IMAP_MAX_FETCHING_TIME',  30);

/**
 * Входящи писма
 */
class email_Messages extends core_Master
{
	

    /**
     *  Заглавие на таблицата
     */
    var $title = "Входящи писма";
    
    
    /**
     * Права
     */
    var $canRead = 'admin, email';
    
    
    /**
     *  
     */
    var $canEdit = 'no_one';
    
    
    /**
     *  
     */
    var $canAdd = 'admin, email';
    
    
    /**
     *  
     */
    var $canView = 'admin, email';
    
    
    /**
     *  
     */
    var $canList = 'admin, email';
    
    /**
     *  
     */
    var $canDelete = 'no_one';
    
	
	/**
	 * 
	 */
	var $canEmail = 'admin, email';
	
    
    /**
     * 
     */
	var $loadList = 'email_Wrapper, plg_Created, doc_DocumentPlg, plg_RowTools, plg_Rejected, plg_State';
    
	
	/**
	 * Нов темплейт за показване
	 */
	var $singleLayoutFile = 'email/tpl/SingleLayoutMessages.html';
	
	
    /**
     * Икона по подразбиране за единичния обект
     */
    var $singleIcon = 'img/16/email.png';


	/**
	 * 
	 * Enter description here ...
	 * @var unknown_type
	 */
	var $textHtmlKey;
    	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('accId', 'key(mvc=email_Accounts,select=eMail)', 'caption=Акаунт');
		$this->FLD("messageId", "varchar", "caption=Съобщение ID");
		$this->FLD("subject", "varchar", "caption=Тема");
		$this->FLD("from", "varchar", 'caption=От');
		$this->FLD("fromName", "varchar", 'caption=От Име');
		$this->FLD("to", "varchar", 'caption=До');
		$this->FLD("headers", "text", 'caption=Хедъри');
		$this->FLD("textPart", "richtext", 'caption=Текстова част');
		$this->FLD("htmlPart", "text", 'caption=HTML част');
		$this->FLD("spam", "int", 'caption=Спам');
		$this->FLD("lg", "varchar", 'caption=Език');
		$this->FLD('hash', 'varchar(32)', 'caption=Keш');
		$this->FLD('country', 'key(mvc=drdata_countries,select=commonName)', 'caption=Държава');
		$this->FLD('fromIp', 'ip', 'caption=IP');
		$this->FLD('files', 'keylist(mvc=fileman_Files,select=name,maxColumns=1)', 'caption=Файлове');		
		$this->FLD('emlFile', 'key(mvc=fileman_Files,select=name)', 'caption=eml файл');
		$this->FLD('htmlFile', 'key(mvc=fileman_Files,select=name)', 'caption=html файл');
		$this->FLD('msgFile', 'key(mvc=fileman_Files,select=name)', 'caption=msg файл');
		
		$this->setDbUnique('hash');
		
	}
	
		
	/**
	 * Взема записите от пощенската кутия и ги вкарва в модела
	 *
	 * @param number $oneMailId - Потребителя, за когото ще се проверяват записите.
	 * Ако е празен, тогава ще се проверяват за всички.
	 * 
	 * @return boolean TRUE
	 */
	function getMailInfo($oneMailId = FALSE)
	{  
		$query = email_Accounts::getQuery();
		
        if (!$oneMailId) {
			
			//$query->where("#period = ''");
						
		} else {
			$oneMailId = intval($oneMailId);
			$query->where("#id = '$oneMailId'");
		}
		
		$query->where("#state = 'active'");
		
        $Fileman = cls::get('fileman_Files');
		
		while ($accaunt = $query->fetch()) {
			$host = $accaunt->server;
			$port = $accaunt->port;
			$user = $accaunt->user;
			//$user = $accaunt->user;
			$pass = $accaunt->password;
			$subHost = $accaunt->subHost;
			$ssl = $accaunt->ssl;
			$accId = $accaunt->id;
			
			unset($imapCls);
			
			$imapCls = cls::get('email_Imap');

			$imap = $imapCls->login( $host, $port, $user, $pass, $subHost, $folder = "INBOX", $ssl );
			
			if (!$imap) {
                
				continue;
			}

            set_time_limit(100);

			$statistics = $imapCls->statistics($imap);

			$numMsg = $statistics['Nmsgs'];
            
			// $id - Номера на съобщението
			$i = 0;
			
			// До коя секунда в бъдещето максимално да се теглят писма?
            $maxTime = time() + IMAP_MAX_FETCHING_TIME;
			
            while (($i < $numMsg) && ($maxTime > time())) {
            	
            	$i++;
            	
            	unset($imapMime);
            	
            	$imapMime = cls::get('email_Mime');
            	
            	$imapMime->unsetMime();
            	
            	$imapMime->setFromImap($imap, $i);
            	
            	$hash = $imapMime->getHash();
            	
            	if($this->fetchField(array("#hash = '[#1#]'", $hash), 'id')) { 
                   	$htmlRes .= "\n<li> Skip: $hash</li>";
				
                   	continue;
               	} else {
                   	$htmlRes .= "\n<li style='color:green'> Get: $hash</li>";
               	}
            	
               	unset($rec);
               
               	$rec = new stdClass();
               
               	$rec->accId = $accId;
               
               	$rec->hash = $hash;
               
               	$rec->messageId = $imapMime->getMessageId();
               
               	$rec->subject = $imapMime->getSubject();
               
               	$rec->from = $imapMime->getFrom();

               	$rec->fromName = $imapMime->getFromName();
               
               	$rec->to = $imapMime->getTo();
              
               	$rec->headers = $imapMime->getHeaders();
               
               	$rec->textPart = $imapMime->getText();
               
               	$rec->htmlPart = $imapMime->getHtml();
               
               	$rec->spam = $imapMime->getSpam();
               
               	$rec->lg = $imapMime->getLg();
               
				$rec->country = $imapMime->getCountry();
				
				$rec->fromIp = $imapMime->getSenderIp();
				
				$rec->files = $imapMime->getAttachments();
				
				$rec->emlFile = $imapMime->getEmlFileId();
				
				$rec->htmlFile = $imapMime->getHtmlFileId();
				
               	$rec->msgFile = $imapMime->getMessageFileId();

               	email_Messages::save($rec, NULL, 'IGNORE');
               	
               	//TODO Да се премахне коментара
				//$imapCls->delete($imap, $i);
            }
            
            //TODO Да се премахне коментара
			//$imapCls->expunge($imap);
		
			$imapCls->close($imap);
			
		}
		
		return $htmlRes;
	}
	
	
	/**
	 * Връща ключа на текстовата и html частта
	 */
	function getTextHtmlKey($mail, $key)
	{
		$newKey = $key . '.1';

		if (isset($mail[$newKey])) {
						
			unset($mail[$key]);
			$this->getTextHtmlKey($mail, $newKey);

		} else {
			
			$arr['text'] = $key;

			$htmlText = substr($arr['text'], 0, -1) . '2';
			
			$arr['html'] = $htmlText;
			
			$this->textHtmlKey = $arr;
		}
		
		return $mail;
	}
	
	
	/**
	 * Връща аватара на Автора
	 */
	function getAuthorAvatar($id)
	{
		
		return NULL;
	}
	
	
	/**
	 * Връща името и мейла
	 */
	function getAuthorName($id)
	{
		$query = email_Messages::getQuery();
		$query->where("#id = '$id'");
		$query->show('from, fromName');
		$rec = $query->fetch();
		$from = trim($rec->from);
		$fromName = trim($rec->fromName);

		$name = $from;
		
		if ($fromName) {
			$name = $fromName . "<br />" . $name;
		}
		
		$len = mb_strlen($fromName) + mb_strlen($from);
		if ($len > 32) {
			$name = mb_substr($name, 0, 32);
            $name .= "...";
		}
				
		return $name;
	}
	
	
	
	/**
	 * Връща датана на създаване на мейла
	 */
	function getDate($id)
	{
		$query = email_Messages::getQuery();
		$query->where("#id = '$id'");
		$query->show('createdOn');
		$rec = $query->fetch();
		
		$date = $rec->createdOn;
		
		return $date;
	}
	
	
	/**
	 * TODO ?
	 * Преобразува containerId в машинен вид
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec)
	{
		$row->containerId = $rec->containerId;
		
		//TODO team@ep-bags.com да се сложи в конфигурационния файл
		if (trim(strtolower($rec->to)) == 'team@ep-bags.com') {
			$row->to = NULL;
		}
		
		if ($rec->files) {
			$vals = type_Keylist::toArray($rec->files);
			if (count($vals)) {
				$row->files = '';
				foreach ($vals as $keyD) {
					$row->files .= fileman_Files::getSingleLink($keyD);
				}
			}
		}
		
		$row->emlFile = fileman_Files::getSingleLink($rec->emlFile);
		$row->htmlFile = fileman_Files::getSingleLink($rec->htmlFile);
		$row->msgFile = fileman_Files::getSingleLink($rec->msgFile);
		
		$row->htmlFile = fileman_Files::getSingleLink($rec->htmlFile);
		
		$pattern = '/\s*[0-9a-f_A-F]+.eml\s*/';
		$row->emlFile = preg_replace($pattern, 'EMAIL.eml', $row->emlFile);
		
		$pattern = '/\s*[0-9a-f_A-F]+.html\s*/';
		$row->htmlFile = preg_replace($pattern, 'EMAIL.html', $row->htmlFile);
		
		$pattern = '/\s*[0-9a-f_A-F]+.msg\s*/';
		$row->msgFile = preg_replace($pattern, 'EMAIL.msg', $row->msgFile);
		
		$row->files .= $row->emlFile . $row->htmlFile;
		
		
	}
 
    

	/**
	 * Вкарваме файла във fileman
	 */
	function insertToFileman($path)
	{
		$Fileman = cls::get('fileman_Files');
		$fh = $Fileman->addNewFile($path, 'Email');
		
		@unlink($path);
		
		return $fh;
	}
	
	
	/**
	 * Записва файловете
	 */
	function insertToFile($path, $file)
	{
		$fp = fopen($path, w);
		fputs($fp, $file);
		fclose($fp);
		
		$fh = $this->insertToFileman($path);
		
		return $fh;		
	}
	
	
	/**
     * Да сваля имейлите
     */
    function act_DownloadEmails()
    {		
		$mailInfo = $this->getMailInfo();
		
		return $mailInfo;
    }
    
	
	/**
     * Изпълнява се след създаването на модела
     */
	function on_AfterSetupMVC($mvc, $res)
    {
    	$res .= "<p><i>Нагласяне на Cron</i></p>";
        
        $rec->systemId = 'DownloadEmails';
        $rec->description = 'Сваля и-мейлите в модела';
        $rec->controller = $this->className;
        $rec->action = 'DownloadEmails';
        $rec->period = 1;
        $rec->offset = 0;
        $rec->delay = 0;
     // $rec->timeLimit = 200;
        
        $Cron = cls::get('core_Cron');
        
        if ($Cron->addOnce($rec)) {
            $res .= "<li><font color='green'>Задаване на крон да сваля имейлите в модела.</font></li>";
        } else {
            $res .= "<li>Отпреди Cron е бил нагласен да сваля имейлите.</li>";
        }
    	
        if(!is_dir(IMAP_TEMP_PATH)) {
            if( !mkdir(IMAP_TEMP_PATH, 0777, TRUE) ) {
                $res .= '<li><font color=red>' . tr('Не може да се създаде директорията') . ' "' . IMAP_TEMP_PATH . '</font>';
            } else {
                $res .= '<li>' . tr('Създадена е директорията') . ' <font color=green>"' . IMAP_TEMP_PATH . '"</font>';
            }
        } else {
        	$res .= '<li>' . tr('Директорията съществува: ') . ' <font color=black>"' . IMAP_TEMP_PATH . '"</font>';
        }
        
        return $res;
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     */
    function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row->title = $this->getVerbal($rec, 'subject');

        $row->author = $this->getVerbal($rec, 'fromName');
 
        $row->authorEmail  = $rec->from;

        $row->state  = $rec->state;


        return $row;
    }



}
