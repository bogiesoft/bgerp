<?php



/**
 * Мениджър на бонуси
 *
 *
 * @category  bgerp
 * @package   trz
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Бонуси
 */
class hr_Bonuses extends core_Master
{

    /**
     * Старото име на класа
     */
    public $oldClassName = 'trz_Bonuses';

    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'hr_IndicatorsSourceIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Премии';
    
     
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = "Премия";
    

    /**
     * Абривиатура на документа
     */
    public $abbr = 'Bns';
    
    
    /**
    * Активен таб на менюто
    */
    public $menuPage = 'Персонал:Документи';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_State, plg_SaveAndNew, doc_plg_TransferDoc, bgerp_plg_Blank,
    				 doc_DocumentPlg, doc_ActivatePlg,hr_Wrapper,acc_plg_DocumentSummary';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,hr';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,hr';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,hr';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,hr';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,hr';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,hr';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,hr';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, date, personId, type, sum';
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "5.5|Човешки ресурси"; 
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'type';
    
    
    /**
     * За плъгина acc_plg_DocumentSummary
     */
    public $filterFieldDateFrom = 'date';
    public $filterFieldDateTo = 'date';
    
    
    /**
     * Единична икона
     */
    public $singleIcon = 'img/16/bonuses.png';


    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'trz/tpl/SingleLayoutBonuses.shtml';

    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('date', 'date',     'caption=Дата,oldFieldName=periodId');
    	$this->FLD('personId', 'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Служител');
    	$this->FLD('type', 'varchar',     'caption=Произход на бонуса');
    	$this->FLD('sum', 'double',     'caption=Сума');
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
        // Ако имаме права да видим визитката
    	if(crm_Persons::haveRightFor('single', $rec->personId)){
    		$name = crm_Persons::fetchField("#id = '{$rec->personId}'", 'name');
    		$row->personId = ht::createLink($name, array ('crm_Persons', 'single', 'id' => $rec->personId), NULL, 'ef_icon = img/16/vcard.png');
    	}
    	//bp($rec, date, );
    	$Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
    	$row->baseCurrencyId = acc_Periods::getBaseCurrencyCode($rec->from);
    	
    	if($rec->sum) {
    	    $row->sum = $Double->toVerbal($rec->sum);
    	    $row->sum .= " <span class='cCode'>{$row->baseCurrencyId}</span>";
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
        $data->listFilter->showFields = 'personId,date';
        $data->listFilter->view = 'vertical';
        $data->listFilter->input('personId, date', 'silent');
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
        
        if($data->listFilter->rec->personId) {
            $data->query->where("#personId = '{$data->listFilter->rec->personId}'");
        }
        
        if($data->listFilter->rec->date) {
            $data->query->where("#date = '{$data->listFilter->rec->date}'");
        }
    }
    
    
    public static function act_Test()
    {
    	$date = '2016-03-01';
    	self::getSalaryIndicators($date);
    }
    
    
    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     * 
     * @param date $date
     * @return array $result
     */
    public static function getIndicatorValues($timeline)
    {
    	$query = self::getQuery();
        $query->where("#modifiedOn  >= '{$timeline}' AND #state != 'draft' AND #state != 'template' AND #state != 'pending'");
 
    	while($rec = $query->fetch()){
 
    		$result[] = (object)array(
    		    'date' => $rec->date,
	    		'personId' => $rec->personId, 
	    		'docId'  => $rec->id, 
	    	    'docClass' => core_Classes::getId('hr_Bonuses'),
	    		'indicatorId' => 1, 
	    		'value' => $rec->sum,
                'isRejected' => $rec->state == 'rejected',
	    	);
    	}

    	return $result;
    }
    
    
    
    
    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     * 
     * @param date $date
     * @return array $result
     */
    public static function getIndicatorNames()
    {
        return array(1 => 'Бонус');
    }


    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка 
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        $Cover = doc_Folders::getCover($folderId);
        
        // Трябва да е в папка на лице или на проект
        if ($Cover->className != 'crm_Persons' && $Cover->className != 'doc_UnsortedFolders') return FALSE;
        
        // Ако е в папка на лице, лицето трябва да е в група служители
        if($Cover->className == 'crm_Persons'){
        	$emplGroupId = crm_Groups::getIdFromSysId('employees');
        	$personGroups = $Cover->fetchField('groupList');
        	if(!keylist::isIn($emplGroupId, $personGroups)) return FALSE;
        }
        
        if($Cover->className == 'doc_UnsortedFolders') {
            $cu = core_Users::getCurrent();
            if(!haveRole('ceo,hr', $cu)) return FALSE;
        }
        
        return TRUE;
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
        $row->title = "Премия  №{$rec->id}";
    
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
         
        $title = tr('Премия  №|*'. $rec->id . ' за|* ') . $me->getVerbal($rec, 'personId');
         
        return $title;
    }

}