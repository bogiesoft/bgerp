<?php



/**
 * acc_Limists модел за определяне на счетоводни лимити
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg> и Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_Limits extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = "Лимити";
    
    
    /**
     * Заглавие
     */
    public $singleTitle = "Лимит";
    
    
    /**
     * Активен таб на менюто
     */
    public $menuPage = 'Счетоводство:Настройки';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools, acc_WrapperSettings, plg_State2, plg_AlignDecimals2, plg_Search';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'accountId,item1,item2,item3';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,acc';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'powerUser';
    
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'ceo,accMaster';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,acc';
    
    
    /**
     * Полета в списъчния изглед
     */
    public $listFields = 'id,accountId,startDate,limitDuration,side,type,limitQuantity,when,sharedUsers=Нотифициране,state';
    
    
    /**
     * Кой може да променя състоянието на валутата
     */
    public $canChangestate = 'ceo,accMaster';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('accountId', 'acc_type_Account(allowEmpty)', 'caption=Сметка, silent, mandatory,removeAndRefreshForm=limitQuantity|type|side|item1|item2|item3');
        $this->FLD('startDate', 'datetime(format=smartTime)', 'caption=Начална дата,mandatory');
        $this->FLD('limitDuration', 'time', 'caption=Продължителност');
        
        
        $this->FLD('side', 'enum(debit=Дебит,credit=Кредит)', 'mandatory,caption=Лимит->Салдо,input=none');
        $this->FLD('type', 'enum(minimum=Минимум,maximum=Максимум)', 'mandatory,caption=Лимит->Тип,input=none');
        $this->FLD('limitQuantity', 'double(min=0,decimals=2)', 'mandatory,caption=Лимит->Стойност,input=none');
    	
        $this->FLD('item1', 'acc_type_Item(select=titleLink,allowEmpty)', 'caption=Сметка->Перо 1, input=none');
        $this->FLD('item2', 'acc_type_Item(select=titleLink,allowEmpty)', 'caption=Сметка->Перо 2, input=none');
        $this->FLD('item3', 'acc_type_Item(select=titleLink,allowEmpty)', 'caption=Сметка->Перо 3, input=none');
        
        $this->FLD('sharedUsers', 'userList(roles=powerUser)', 'caption=Нотифициране->Потребители,mandatory');
        $this->FLD('when', 'date', 'caption=Надвишаване,input=none');
        $this->FLD('exceededAmount', 'double(decimals=2)', 'caption=Надвишаване,input=none');
        
        $this->FLD('state', 'enum(active=Активен,closed=Затворен,pending=Надвишен)', 'caption=Видимост,input=none,notSorting,notNull,value=active');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = &$data->form;
        $form->setDefault('startDate', dt::now());
        $rec = &$form->rec;
        
        // Ако е избрана сметка
    	if(isset($form->rec->accountId)){
    		$accInfo = acc_Accounts::getAccountInfo($form->rec->accountId);
    		
    		$form->setField('side', 'input');
    		$form->setField('type', 'input');
    		$form->setField('limitQuantity', 'input');
    		
    		// Показваме номенклатурите във сметката
    		foreach (range(1, 3) as $i){
    			if(isset($accInfo->groups[$i])){
    				$form->setField("item{$i}", "input,caption=Пера->{$accInfo->groups[$i]->rec->name}");
    				$form->setFieldTypeParams("item{$i}", array('lists' => $accInfo->groups[$i]->rec->num));
    			}
    		}
    		
    		if($accInfo->rec->type == 'passive'){
    			$form->setDefault('side', 'credit');
    			$form->setDefault('type', 'maximum');
    		}
    		
    		$classId = Request::get('classId', 'key(mvc=core_Classes)');
    		$objectId = Request::get('objectId');
    		
    		if(isset($classId) && isset($objectId)){
    			if(Request::get('accountId', 'key(mvc=acc_Accounts)')){
    				$form->setReadOnly('accountId');
    			}
    			 
    			$form->setField('side', 'input=hidden');
    			if($itemRec = acc_Items::fetchItem($classId, $objectId)){
    				foreach (range(1, 3) as $i){
    					if(isset($accInfo->groups[$i])){
    						if(cls::haveInterface($accInfo->groups[$i]->rec->regInterfaceId, $classId)){
    							$form->setDefault("item{$i}", $itemRec->id);
    							$form->setReadOnly("item{$i}");
    						}
    					}
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
    	$rec = &$form->rec;
    	if($form->isSubmitted()){
    		
    		// Зануляваме датата, на която лимита е бил нарушен
    		$form->rec->when = NULL;
    		$form->rec->exceededAmount = NULL;
    		$form->rec->state = 'active';
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$row->accountId = acc_Balances::getAccountLink($rec->accountId, NULL, TRUE, TRUE);
    	$itemsTpl = new ET("<ul style='margin-top:3px;margin-bottom:3px'>[#item#]</ul>");
    	
    	foreach (range(1, 3) as $i){
    		if(isset($rec->{"item{$i}"})){
    			$itemRow = acc_Items::getVerbal($rec->{"item{$i}"}, 'titleLink');
    			$row->{"item{$i}"} = $itemRow;
    			$itemsTpl->append("<li>{$itemRow}</li>", 'item');
    		}
    	}
    	
    	$row->accountId .= $itemsTpl->getContent();
    	
    	if(isset($rec->when)){
    		$row->when = "<span style='color:darkred'>{$row->when}</span>";
    		$exceededAmount = cls::get('type_Double', array('params' => array('decimals' => 2)))->toVerbal($rec->exceededAmount);
    		$row->when .= "<br>( {$exceededAmount} )";
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;
        $form->view = 'horizontal';
        
        $form->FNC('account', 'acc_type_Account(allowEmpty)', 'caption=Сметка,input');
        //$form->FNC('users', 'users(rolesForAll=support|ceo|admin)', 'caption=Потребители,silent,refreshForm');
        $form->FNC("state2", 'enum(all=Всички,active=Активни,closed=Затворени,pending=Надвишени)', 'caption=Вид,input');
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $form->showFields = 'search,account,state2';
        
        $form->input();
        
        if(isset($form->rec)){
        	
        	if($form->rec->account){
        		$data->query->where(array("#accountId = [#1#]", $form->rec->account));
        	}
        	
        	if($searchState = $form->rec->state2){
        		if($searchState != 'all'){
        			$data->query->where(array("#state = '[#1#]'", $searchState));
        		}
        	}
        }
        
        // Ceo и accMaster Може да вижда всички записи, иначе само тези до които е споделен
        if(!haveRole('ceo,accMaster')){
        	$cu = core_Users::getCurrent();
        	$data->query->like('sharedUsers', "|{$cu}|");
        }
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    protected static function on_AfterPrepareListFields($mvc, $data)
    {
    	$data->query->XPR('orderByState', 'int', "(CASE #state WHEN 'pending' THEN 1 WHEN 'active' THEN 2 ELSE 3 END)");
    	$data->query->orderBy('#orderByState=ASC');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
    	if($action == 'list'){
    		
    		// Ако няма ceo,accMaster проверяваме дали има споделени записи до потребителя
    		if(!core_Users::haveRole('ceo,accMaster', $userId)){
    			$query = static::getQuery();
    			$query->like('sharedUsers', "|{$userId}|");
    			
    			// Ако няма той няма достъп до лист изгледа
    			if(!$query->count()){
    				$requiredRoles = 'no_one';
    			}
    		}
    	}
    	
    	if(($action == 'changestate' || $action == 'delete') && isset($rec)){
    		if($rec->state == 'pending'){
    			$requiredRoles = 'no_one';
    		}
    	}
    }
    
    
    function act_Test()
    {
    	$this->cron_CheckAccLimits();
    }
    
    
    /**
     * Метод по разписание, който проверява дали поставените лимити са нарушени
     */
    function cron_CheckAccLimits()
    {
    	// Кой е последния баланс
    	$balanceId = acc_Balances::getLastBalance()->id;
    	
    	// Ако няма баланс не правим нищо
    	if(!$balanceId) return;
    	
    	// Кои сметки имат зададени лимити
    	$limitedAccounts = array();
    	$query = $this->getQuery();
    	$query->show('accountId');
    	$query->groupBy('accountId');
    	while($rec = $query->fetch()){
    		$sysId = acc_Accounts::fetchField($rec->accountId, 'systemId');
    		$limitedAccounts[$sysId] = $sysId;
    	}
    	
    	// Ако няма зададени лимити не правим нищо
    	if(!count($limitedAccounts)) return;
    	
    	// Взимаме данните от последния баланс, филтрирано по сметките по които има лимити
    	$bQuery = acc_BalanceDetails::getQuery();
    	acc_BalanceDetails::filterQuery($bQuery, $balanceId, $limitedAccounts);
    	$bQuery->where("#ent1Id IS NOT NULL || #ent2Id IS NOT NULL || #ent3Id IS NOT NULL");
    	$balanceArr = $bQuery->fetchAll();
    	
    	// Намираме всички активни ограничения
    	$newQuery = $this->getQuery();
    	$newQuery->where("#state != 'closed'");
    	while($rec = $newQuery->fetch()){
    		
    		// Ще групираме данните от баланса
    		$accInfo = acc_Accounts::getAccountInfo($rec->accountId);
    		$groupedRec = new stdClass();
    		$groupedRec->blQuantity = $groupedRec->blAmount = 0;
    		
    		// За всеки запис от баланса
    		if(count($balanceArr)){
    			foreach ($balanceArr as $bRec){
    				 
    				// Ако сметката е различна, пропускаме
    				if($bRec->accountId != $rec->accountId) continue;
    				 
    				// За перата от 1 до 3, ако са зададени и на записа са различни, записа не участва в групирането
    				if(isset($rec->item1) && $rec->item1 != $bRec->ent1Id) continue;
    				if(isset($rec->item2) && $rec->item2 != $bRec->ent2Id) continue;
    				if(isset($rec->item3) && $rec->item3 != $bRec->ent3Id) continue;
    				
    				// Сумираме количеството на дебита и на кредита
    				$groupedRec->blQuantity += $bRec->blQuantity;
    				$groupedRec->blAmount += $bRec->blAmount;
    			}
    		}
    		
    		$hasDimensionalItemSelected = FALSE;
    		foreach (range(1, 3) as $i){
    			if(isset($rec->{"item{$i}"})){
    				if($accInfo->groups[$i]->rec->isDimensional == 'yes'){
    					$hasDimensionalItemSelected = TRUE;
    				}
    			}
    		}
    		
    		$fieldToCompare = ($hasDimensionalItemSelected === TRUE) ? $groupedRec->blQuantity : $groupedRec->blAmount;
    		
    		if($rec->type == 'minimum'){
    			$sendNotification = abs($fieldToCompare) < $rec->limitQuantity;
    		} else {
    			$sendNotification = abs($fieldToCompare) > $rec->limitQuantity;
    		}
    		
    		// Ако има надвишаване изпращаме нотифификация
    		if($sendNotification === TRUE){
    			$oldWhen = $rec->when;
    			$rec->when = dt::today();
    			$rec->state = 'pending';
    			$rec->exceededAmount = abs($fieldToCompare - $sign * $rec->limitQuantity);
    			
    			$this->save($rec, 'when,state,exceededAmount');
    			$sharedUsers = keylist::toArray($rec->sharedUsers);
    			$urlArr = $customUrl = array($this, 'list');
    			
    			$whenVerbal = $this->getVerbal($rec->when, 'when');
    			$msg = "|Има надвишаване на ограничение на|* '{$whenVerbal}')";
    			
    			// Всеки споделен потребител, нотифицираме го
    			foreach ($sharedUsers as $userId){
    				bgerp_Notifications::add($msg, $urlArr, $userId, 'normal', $customUrl);
    			}
    		} else {
    			
    			// Ако е имало надвишаване но вече няма
    			if(isset($rec->when)){
    				$rec->state = 'active';
    				$rec->when = NULL;
    				$rec->exceededAmount = NULL;
    				$this->save($rec, 'when,exceededAmount,state');
    			}
    		}
    	}
    }
    
    
    /*function prepareLimits(&$data)
    {
    	$data->masterMvc->limitAccounts = '501';
    	
    	if(!isset($data->masterMvc->limitAccounts)) {
    		$data->render = FALSE;
    		return;
    	}
    	
    	$data->TabCaption = 'Лимити';
    	
    	if($itemRec = acc_Items::fetchItem($data->masterMvc->getClassId(), $data->masterId)){
    		$query = $this->getQuery();
    		$query->where("#item1 = {$itemRec->id} || #item2 = {$itemRec->id} || #item3 = {$itemRec->id}");
    		
    		$data->rows = array();
    		while($rec = $query->fetch()){
    			foreach (range(1, 3) as $i){
    				if($rec->{"item{$i}"} == $itemRec->id){
    					unset($rec->{"item{$i}"});
    					$unseted = "item{$i}";
    					break;
    				}
    			}
    			
    			if(empty($data->limits[$rec->accountId])){
    				$data->limits[$rec->accountId] = array('unseted' => $unseted, 'rows' => array());
    			}
    			
    			$row = $this->recToVerbal($rec);
    			$row->state = $this->getFieldType('state')->toVerbal($rec->state);
    			
    			$data->limits[$rec->accountId]['rows'][$rec->id] = $row;
    		}
    	}
    }
    
    
    function renderLimits(&$data)
    {
    	if($data->render === FALSE) return;
    	$tpl = getTplFromFile('acc/tpl/LimitDetail.shtml');
    	
    	$data->listFields = arr::make('item1=item1,item2=item2,item3=item3,side=Салдо,type=Вид,limitQuantity=Сума,createdBy=Създадено от,createdOn=Създадено на');
    	
    	$count = 1;
    	foreach ($data->rows as $accountId => $arr){
    		
    		// Името на сметката и нейните групи
    		$accNum = acc_Balances::getAccountLink($accountId);
    		$accInfo = acc_Accounts::getAccountInfo($accountId);
    		
    		// Името на сметката излиза над таблицата
    		$content = new ET("<span class='accTitle'>{$accNum}</span>");
    		$fields = $data->listFields;
    		
    		// Обикаляне на всички пера
    		foreach (range(1, 3) as $i){
    			if(isset($accInfo->groups[$i])){
    				if($arr['unseted'] != "item{$i}"){
    					$fields["item{$i}"] = $accInfo->groups[$i]->rec->name;
    				} else {
    					unset($fields["item{$i}"]);
    				}
    			} else {
    				unset($fields["item{$i}"]);
    			}
    		}
    		
    		$table = cls::get('core_TableView', array('mvc' => $this));
    		$content->append($table->get($arr['rows'], $fields));
    		
    		if($count != 1){
    			$content->append("<div style='margin-top:15px'></div>");
    		}
    		
    		$tpl->append($content, 'CONTENT');
    		
    		$count++;
    	}
    	
    	$url = array($this, 'add', 'classId' => $data->masterMvc->getClassId(), 'objectId' => $data->masterId);
    	$btn = ht::createBtn('Нов запис', $url, FALSE, FALSE, 'title=Добавяне на ново ограничение на перото');
    	$tpl->append($btn, 'BTNS');
    	
    	return $tpl;
    }*/
}