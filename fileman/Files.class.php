<?php


/**
 * Каква да е дължината на манипулатора на файла?
 */
defIfNot('FILEMAN_HANDLER_LEN', 6);


/**
 * Клас 'fileman_Files' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    fileman
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class fileman_Files extends core_Manager {
    
    
    /**
     *  Заглавие на модула
     */
    var $title = 'Файлове';
    
    
    /**
     *  Описание на модела (таблицата)
     */
    function description()
    {
        // Файлов манипулатор - уникален 8 символно/цифров низ, започващ с буква.
        // Генериран случайно, поради което е труден за налучкване
        $this->FLD( "fileHnd", "varchar(" . FILEMAN_HANDLER_LEN . ")",
        array('notNull' => TRUE, 'caption' => 'Манипулатор'));
        
        // Име на файла
        $this->FLD( "name", "varchar(255)",
        array('notNull' => TRUE, 'caption' => 'Файл'));
        
        // Данни (Съдържание) на файла
        $this->FLD( "dataId", "key(mvc=fileman_Data)",
        array('caption' => 'Данни Id'));
        
        // Клас - притежател на файла
        $this->FLD( "bucketId", "varchar(32)",
        array('caption' => 'Кофа') );
        
        // Състояние на файла
        $this->FLD( "state", "enum(draft=Чернова,active=Активен,rejected=Оттеглен)",
        array('caption' => 'Състояние'));
        
        // Плъгини за контрол на записа и модифицирането
        $this->load('plg_Created,plg_Modified,Data=fileman_Data,Buckets=fileman_Buckets,' .
        'Download=fileman_Download,Versions=fileman_Versions,fileman_Wrapper');
        
        // Индекси
        $this->setDbUnique('fileHnd');
        $this->setDbUnique('name,bucketId', 'uniqName');
    }
    
    
    /**
     * Преди да запишем, генерираме случаен манипулатор
     */
    function on_BeforeSave(&$mvc, &$id, &$rec)
    {
        // Ако липсва, създаваме нов уникален номер-държател
        if(!$rec->fileHnd) {
            do {
                
                if(16 < $i++) error('Unable to generate random file handler', $rec);
                
                $rec->fileHnd = $mvc->getUniqId(FILEMAN_HANDLER_LEN);

            } while($mvc->fetch("#fileHnd = '{$rec->fileHnd}'"));
        } elseif(!$rec->id) {
            
            $existingRec = $mvc->fetch("#fileHnd = '{$rec->fileHnd}'");
            
            $rec->id = $existingRec->id;
        }
    }
    
    
    /**
     * Задава файла с посоченото име в посочената кофа
     */
    function setFile($path, $bucket, $fname = NULL, $force = FALSE)
    {
        if($fname === NULL) $fname = basename($path);
        
        $Buckets = cls::get('fileman_Buckets');
        
        expect($bucketId = $Buckets->fetchByName($bucket));
        
        $fh = $this->fetchField(array("#name = '[#1#]' AND #bucketId = {$bucketId}",
            $fname,
        ), "fileHnd");
        
        if(!$fh) {
            $fh = $this->addNewFile($path, $bucket, $fname);
        } elseif($force) {
            $this->setContent($fh, $path);
        }
        
        return $fh;
    }
    
    
    /**
     * Добавя нов файл в посочената кофа
     */
    function addNewFile($path, $bucket, $fname = NULL)
    {
        if($fname === NULL) $fname = basename($path);
        
        $Buckets = cls::get('fileman_Buckets');
        
        $bucketId = $Buckets->fetchByName($bucket);
        
        $fh = $this->createDraftFile($fname, $bucketId);
        
        $this->setContent($fh, $path);
        
        return $fh;
    }
    

    /**
     * Добавя нов файл в посочената кофа от стринг
     */
    function addNewFileFromString($string, $bucket, $fname = NULL)
    {
        $me = cls::get('fileman_Files');

        if($fname === NULL) $fname = basename($path);
        
        $Buckets = cls::get('fileman_Buckets');
        
        $bucketId = $Buckets->fetchByName($bucket);
        
        $fh = $me->createDraftFile($fname, $bucketId);
        
        $me->setContentFromString($fh, $string);
        
        return $fh;
    }

    
    /**
     * Създаваме нов файл в посочената кофа
     */
    function createDraftFile($fname, $bucketId)
    {
        expect($bucketId, 'Очаква се валидна кофа');
        
        $rec->name = $this->getPossibleName($fname, $bucketId);
        $rec->bucketId = $bucketId;
        $rec->state = 'draft';
        
        $this->save($rec);
        
        return $rec->fileHnd;
    }
    
    
    /**
     * Връща първото възможно има, подобно на зададеното, така че в този
     * $bucketId да няма повторение на имената
     */
    function getPossibleName($fname, $bucketId)
    {
        // Конвертираме името към такова само с латински букви, цифри и знаците '-' и '_'
        $fname = STR::utf2ascii($fname);
        $fname = preg_replace('/[^a-zA-Z0-9\-_\.]+/', '_', $fname);

        // Циклим докато генерирме име, което не се среща до сега
        $fn = $fname;
        
        if( ($dotPos = strrpos($fname, '.')) !== FALSE ) {
            $firstName = substr($fname, 0, $dotPos);
            $ext = substr($fname, $dotPos);
        } else {
            $firstName = $fname;
            $ext = '';
        }
        
        // Двоично търсене за свободно име на файл
        $i = 1;
        while( $this->fetchField(array("#name = '[#1#]' AND #bucketId = '{$bucketId}'", $fn), 'id' ) ) {
            $fn = $firstName . '_' . $i. $ext;
            $i = $i * 2;
        }

        // Търсим първото незаето положение за $i в интервала $i/2 и $i
        if($i > 4) {
            $min = $i/4;
            $max = $i/2;
            
            do {
                $i =  ($max + $min) / 2;
                $fn = $firstName . '_' . $i. $ext;
                if($this->fetchField(array("#name = '[#1#]' AND #bucketId = '{$bucketId}'", $fn), 'id' )) {
                    $min = $i;
                } else {
                    $max = $i;
                }
            } while ($max - $min > 1);

 
            $i = $max;

            $fn = $firstName . '_' . $i. $ext;
        }

        
        return $fn;
    }
    
    
    /**
     * Ако имаме нови данни, които заменят стари
     * такива указваме, че старите са стара версия
     * на файла и ги разкачаме от файла
     */
    function setData($fileHnd, $newDataId)
    {
        $rec = $this->fetch("#fileHnd = '{$fileHnd}'");
        
        // Ако новите данни са същите, като старите 
        // нямаме смяна
        if($rec->dataId == $newDataId) return $rec->dataId;
        
        // Ако имаме стари данни, изпращаме ги в историята
        if( $rec->dataId) {
            $verRec->fileHnd = $fileHnd;
            $verRec->dataId = $rec->dataId;
            $verRec->from = $rec->modifiedOn;
            $verRec->to = dt::verbal2mysql();
            $this->Versions->save($verRec);
            // Намаляваме с 1 броя на линквете към старите данни
            $this->Data->decreaseLinks($rec->dataId);
        }
        
        // Записваме новите данни
        $rec->dataId = $newDataId;
        $rec->state = 'active';
        
        $this->save($rec);
        
        // Увеличаваме с 1 броя на линквете към новите данни
        $this->Data->increaseLinks($newDataId);
        
        return $rec->dataId;
    }
    
    
    /**
     * Задава данните на даден файл от съществуващ файл в ОС
     */
    function setContent($fileHnd, $osFile)
    {
        $dataId = $this->Data->absorbFile($osFile);
        
        return $this->setData($fileHnd, $dataId);
    }

    
    /**
     * Задава данните на даден файл от стринг
     */
    function setContentFromString($fileHnd, $string)
    {
        $dataId = $this->Data->absorbString($string);
        
        return $this->setData($fileHnd, $dataId);
    }


    /**
     * Връща данните на един файл като стринг
     */
    static function getContent($hnd)
    {
        expect($path = fileman_Files::fetchByFh($hnd, 'path'));

        return file_get_contents($path);
    }
    
    
    /**
     * Копира данните от един файл на друг файл
     */
    function copyContent($sHnd, $dHnd)
    {
        $sRec = $this->fetch("#fileHnd = '{$sHnd}'");
        
        if($sRec->state != 'active') return FALSE;
        
        return $this->setData($fileHnd, $sRec->dataId);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getUniqId($len = 8)
    {   
        list($m, $s) = explode(' ', microtime());

        srand( rand(-2000000000, 2000000000) ^ $m ^ $s );
        
        $simbols = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        
        while(strlen($res) < $len) {
            
            $res .= $simbols{rand(0, strlen($simbols)-1)};
        }
        
        return $res;
    }
    
    
    /**
     * Връща записа за посочения файл или негово поле, ако е указано.
     * Ако посоченото поле съществува в записа за даниите за файла,
     * връщаната стойност е от записа за данните на посочения файл
     */
    static function fetchByFh($fh, $field = NULL)
    {
        $Files = cls::get('fileman_Files');

        $rec = $Files->fetch("#fileHnd = '{$fh}'");
        
        if($field === NULL) return $rec;
        
        if(!isset($rec->{$field})) {
            $Data = cls::get('fileman_Data');

            $dataFields = $Data->selectFields("");
            
            if($dataFields[$field]) {
                $rec = $Data->fetch($rec->dataId);
            }
        }
        
        return $rec->{$field};
    }
    
    
    /**
     * Какви роли са необходими за качване или сваляне?
     */
    function on_BeforeGetRequiredRoles($mvc, &$roles, $action, $rec = NULL, $userId = NULL)
    {
        if($action == 'download' && is_object($rec)) {
            $roles = $mvc->Buckets->fetchField($rec->bucketId, 'rolesForDownload');
        } elseif($action == 'add' && is_object($rec)) {
            $roles = $mvc->Buckets->fetchField($rec->bucketId, 'rolesForAdding');
        } else {
            
            return;
        }
        
        return FALSE;
    }
    
    
    /**
     *  Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->name = $mvc->Download->getDownloadLink($rec->fileHnd);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function makeBtnToAddFile($title, $bucketId, $callback, $attr = array())
    {
        $function = $this->getJsFunctionForAddFile($bucketId, $callback);
        
        return ht::createFnBtn($title, $function, NULL, $attr);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function makeLinkToAddFile($title, $bucketId, $callback, $attr = array())
    {
        $attr['onclick'] = $this->getJsFunctionForAddFile($bucketId, $callback);
        $attr['href'] = $this->getUrLForAddFile($bucketId, $callback);
        $attr['target'] = 'addFileDialog';
        
        return ht::createElement('a', $attr, $title);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getUrLForAddFile($bucketId, $callback)
    {
        Request::setProtected('bucketId,callback');
        $url = array('fileman_Upload', 'dialog', 'bucketId' => $bucketId, 'callback' => $callback );
        
        return toUrl($url);
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function getJsFunctionForAddFile($bucketId, $callback)
    {
        $url = $this->getUrLForAddFile($bucketId, $callback);
        
        $windowName = 'addFileDialog';
        
        if(Mode::is('screenMode', 'narrow')) {
            $args = 'resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
        } else {
            $args = 'width=400,height=320,resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
        }
        
        return "openWindow('{$url}', '{$windowName}', '{$args}'); return false;";
    }
    
    
    /**
     * Връща линк за сваляне на посоченото id
     */
    function getSingleLink($id)
    {
    	$fh = fileman_Files::fetchField("#id='{$id}'", 'fileHnd');
		if (self::haveRightFor('single', $id)) {
			$downloads = cls::get('fileman_Download');
			$link = $downloads->getDownloadLink($fh) . "<br />";
		} else {
			$Fileman = cls::get('Fileman_files');
			$link = $Fileman->fetchByFh($fh, 'name') . "<br />";
		}
		
		return $link;
    }
    
}