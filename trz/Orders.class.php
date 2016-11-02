<?php



/**
 * Мениджър на заповеди за отпуски
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заповеди за отпуски
 */
class trz_Orders extends core_Master
{
    
	
	/**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Заповеди';
    
    
     /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Заповед за отпуск";
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, trz_Wrapper, 
    				 doc_DocumentPlg, acc_plg_DocumentSummary, doc_ActivatePlg,
    				 plg_Printing,bgerp_plg_Blank,change_Plugin';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id,personId, leaveFrom, leaveTo, note, useDaysFromYear, isPaid, amount';

    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    public $rowToolsSingleField = 'personId';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, trz';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,trz';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,trz';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, trz';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo, trz';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, trz';
    
    
    /**
     * Кой има право да прави начисления
     */
    public $canChangerec = 'ceo,trz';
    
  
    /**
     * За плъгина acc_plg_DocumentSummary
     */
    public $filterFieldDateFrom = 'leaveFrom';
    public $filterFieldDateTo = 'leaveTo';
    
    
    /**
     * Enter description here ...
     */
    public $canOrders = 'ceo, trz';

    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'trz/tpl/SingleLayoutOrders.shtml';
    
    
    /**
     * Абревиатура
     */
    public $abbr = "Tor";
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "5.3|Човешки ресурси"; 
    
    
    /**
     * Единична икона
     */
    public $singleIcon = 'img/16/ordering.png';

    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,allowEmpty)', 'caption=Служител,mandatory');
    	$this->FLD('leaveFrom', 'date', 'caption=Считано->От, mandatory');
    	$this->FLD('leaveTo', 'date', 'caption=Считано->До, mandatory');
    	$this->FLD('leaveDays', 'int', 'caption=Считано->Дни, input=none');
    	$this->FLD('note', 'richtext(rows=5, bucket=Notes)', 'caption=Информация->Бележки');
    	$this->FLD('useDaysFromYear', 'int(nowYear, nowYear-1)', 'caption=Информация->Ползване от,unit=година');
    	$this->FLD('isPaid', 'enum(paid=платен, unpaid=неплатен)', 'caption=Вид,maxRadio=2,columns=2,notNull,value=paid');
    	$this->FLD('amount', 'double', 'caption=Дневна компенсация,input=none, changable,recently');
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_BeforeSave($mvc, &$id, $rec)
    {
        if($rec->leaveFrom &&  $rec->leaveTo){
        	$state = hr_EmployeeContracts::getQuery();
	        $state->where("#personId='{$rec->personId}'");
	        
	        if($employeeContractDetails = $state->fetch()){
	           
	        	$employeeContract = $employeeContractDetails->id;
	        	$department = $employeeContractDetails->departmentId;
	        	
	        	$schedule = hr_EmployeeContracts::getWorkingSchedule($employeeContract);
	        	if($schedule == FALSE){
	        		$days = hr_WorkingCycles::calcLeaveDaysBySchedule($schedule, $department, $rec->leaveFrom, $rec->leaveTo);
	        	} else {
	        		$days = cal_Calendar::calcLeaveDays($rec->leaveFrom, $rec->leaveTo);
	        	}
	        } else{
        	
	    		$days = cal_Calendar::calcLeaveDays($rec->leaveFrom, $rec->leaveTo);
	        }
	    	$rec->leaveDays = $days->workDays;
        }
    }

    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
    	// Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields .= ',personId, isPaid';
        
        $data->listFilter->input('personId, isPaid', 'silent');
        
    	if($filterRec = $data->listFilter->rec){
        	if($filterRec->personId){
        		$data->query->where(array("#personId = '[#1#]'", $filterRec->personId));
        	}
    		if($filterRec->isPaid){
        		$data->query->where(array("#isPaid = '[#1#]'", $filterRec->isPaid));
        	}
    	}
    }

    
    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
    	$nowYear = dt::mysql2Verbal(dt::now(),'Y');
    	for($i = 0; $i < 5; $i++){
    		$years[$nowYear - $i] = $nowYear - $i;
    	}
    	$data->form->setSuggestions('useDaysFromYear', $years);
    	$data->form->setDefault('useDaysFromYear', $years[$nowYear]);

    	if($data->form->rec->originId){
			// Ако напомнянето е по  документ задача намираме кой е той
    		$doc = doc_Containers::getDocument($data->form->rec->originId);
    		$class = $doc->className;
    		$dId = $doc->that;
    		$rec = $class::fetch($dId);
    		
    		// Извличаме каквато информация можем от оригиналния документ
    		
    		$data->form->setDefault('personId', $rec->personId);
    		$data->form->setDefault('leaveFrom', $rec->leaveFrom);
    		$data->form->setDefault('leaveTo', $rec->leaveTo);
    		$data->form->setDefault('leaveDays', $rec->leaveDays);
    		$data->form->setDefault('note', $rec->note);
    		$data->form->setDefault('useDaysFromYear', $rec->useDaysFromYear);
    		$data->form->setDefault('isPaid', $rec->paid);
    

		}
		
        $rec = $data->form->rec;
        if($rec->folderId){
	        $data->form->setDefault('personId', doc_Folders::fetchCoverId($rec->folderId));
	        $data->form->setReadonly('personId');
        }
    }

    
	/**
     * След подготовка на тулбара на единичен изглед.
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if(doc_Threads::haveRightFor('single', $data->rec->threadId) == FALSE){
	    	$data->toolbar->removeBtn('Коментар');
	    }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;

        if($rec->amount) {
            $row->amount = $Double->toVerbal($rec->amount);
            
            $row->baseCurrencyId = acc_Periods::getBaseCurrencyCode($rec->leaveFrom);
        }
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if(!empty($data->toolbar->buttons['btnAdd'])){
            $data->toolbar->removeBtn('btnAdd');
        }
    }
    
    
    public static function act_Print()
    {
        $originId = Request::get('originId');
        
        // и е по  документ фактура намираме кой е той
        $doc = doc_Containers::getDocument($originId);
        
        $class = $doc->className;
        $dId = $doc->that;
        $recOrigin = $class::fetch($dId);
        
        $rec = new stdClass();
        $rec->personId = $recOrigin->personId;
        $rec->leaveFrom = $recOrigin->leaveFrom;
        $rec->leaveTo = $recOrigin->leaveTo;
        $rec->leaveDays = $recOrigin->leaveDays;
        $rec->note = $recOrigin->note;
        $rec->useDaysFromYear = $recOrigin->useDaysFromYear;
        $rec->isPaid = $recOrigin->paid;
        $rec->state = 'active';
        
        self::save($rec);

        $printUrl = array('trz_Orders', 'single', 'id' => $rec->id, 'Printing' => 'yes');

        return new Redirect($printUrl);
    }
    
    
    /**
     * Обновява информацията за командировките в Персонални работни графици
     */
    public static function updateOrdersToCustomSchedules($id)
    {
        $rec = static::fetch($id);
    
        $events = array();
    
        // Годината на датата от преди 30 дни е начална
        $cYear = date('Y', time() - 30 * 24 * 60 * 60);
    
        // Начална дата
        $fromDate = "{$cYear}-01-01";
    
        // Крайна дата
        $toDate = ($cYear + 2) . '-12-31';
    
        // Префикс на ключовете за записите персонални работни цикли
        $prefix = "ORDER-{$id}";
    
        $curDate = $rec->leaveFrom;
         
        while($curDate < dt::addDays(1, $rec->leaveTo)){
            // Подготвяме запис за началната дата
            if($curDate && $curDate >= $fromDate && $curDate <= $toDate && $rec->state == 'active') {
                 
                $customRec = new stdClass();
                 
                // Ключ на събитието
                $customRec->key = $prefix . "-{$curDate}";
                 
                // Дата на събитието
                $customRec->date = $curDate;
    
                // За човек или департамент е
                $customRec->strukture  = 'personId';
    
                // Тип на събитието
                $customRec->typePerson = 'leave';
    
                // За кого се отнася
                $customRec->personId = $rec->personId;
    
                // Документа
                $customRec->docId = $rec->id;
    
                // Класа ан документа
                $customRec->docClass = core_Classes::getId("trz_Orders");
    
                $events[] = $customRec;
            }
    
            $curDate = dt::addDays(1, $curDate);
        }
    
        return hr_CustomSchedules::updateEvents($events, $fromDate, $toDate, $prefix);
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        $mvc->updateOrdersToCustomSchedules($rec->id);
    }

    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     *
     * @param int $id
     * @return stdClass $row
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        //Заглавие
        $row->title = "Заповед за отпуск  №{$rec->id}";
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //Състояние
        $row->state = $rec->state;
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        $row->recTitle = $this->getRecTitle($rec, FALSE);
        
        return $row;
    }

    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
        $me = cls::get(get_called_class());
         
        $title = tr('Молба за отпуска  №|*'. $rec->id . ' на|* ') . $me->getVerbal($rec, 'personId');
         
        return $title;
    }
}