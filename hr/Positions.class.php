<?php 


/**
 * Позиции
 * Детайли, които определят в един отдел, какви длъжности
 * и на какви условия могат да бъдат назначавани
 *
 * @category  bgerp
 * @package   hr
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hr_Positions extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Длъжности";
    
    
    /**
     * Заглавие в единствено число
     */
    var $singleTitle = "Длъжност";
    
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_RowTools2, hr_Wrapper, plg_Printing, plg_Created';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'ceo,hr';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,hr';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'ceo,hr';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'ceo,hr';
    
      
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'name,nkpd';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование');
        
        // Към кое звено на организацията е тази позиция
        $this->FLD('nkpd', 'key(mvc=bglocal_NKPD, select=title)', 'caption=НКПД, hint=Номер по НКПД');
        
        
        // Възнаграждения
        $this->FLD('salaryBase', 'double(decimals=2)', 'caption=Възнаграждение->Основно');
        $this->FLD('forYearsOfService', 'percent(decimals=2)', 'caption=Възнаграждение->За стаж');
        $this->FLD('compensations', 'double(decimals=2)', 'caption=Възнаграждение->За вредности');
        $this->FLD('frequensity', 'enum(mountly=Ежемесечно, weekly=Ежеседмично, daily=Ежедневно)', 'caption=Възнаграждение->Периодичност');
        $this->FLD('downpayment', 'enum(yes=Да,no=Не)', 'caption=Възнаграждение->Аванс');
        $this->FLD('formula', 'text(rows=3)', 'caption=Възнаграждение->Формула');

        // Срокове
        $this->FLD('probation', 'time(suggestions=1 мес|2 мес|3 мес|6 мес|9 мес|12 мес,uom=month)', "caption=Срокове->Изпитателен срок,unit=месеца,width=6em");
        $this->FLD('annualLeave', 'time(suggestions=10 дни|15 дни|20 дни|22 дни|25 дни,uom=days)', "caption=Срокове->Годишен отпуск,unit=дни,width=6em");
        $this->FLD('notice', 'time(suggestions=10 дни|15 дни|20 дни|30 дни,uom=days)', "caption=Срокове->Предизвестие,unit=дни,width=6em");
        
        // Други условия
        $this->FLD('descriptions', 'richtext(bucket=humanResources)', 'caption=Условия->Допълнителни');
    }
    

    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;

        $names = hr_Indicators::getIndicatorNames();

        foreach($names as $class => $nArr) {
            foreach($nArr as $n) {
                $n = '$' . $n;
                $sugg[$n] = $n;
            }
        }

        $form->setSuggestions('formula', $sugg);
    }


    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec, $userId = NULL)
    {
        // Ако методък е "изтриване"       
        if($action == 'delete' && isset($rec)){
            
            // и имаме активиран договор с тази позиция
            if(hr_EmployeeContracts::fetch(array("#positionId = '[#1#]' AND #state = 'active'", $rec->id))){
                
                // никой не може да изтрие позицията
                $requiredRoles = 'no_one';
            }
        }
    }
    

 
    
    /**
     * @todo Чака за документация...
     */
    function preparePositions($data)
    {
        $data->TabCaption = tr('Позиции');
        
        if($this->haveRightFor('add', (object)array('departmentId' => $data->masterId))){
        	$data->addUrl = array($this, 'add', 'departmentId' => $data->masterId, 'ret_url' => TRUE);
        }

        $data->listFields = 'id,professionId,departmentId,employmentTotal,employmentOccupied';
        
        self::prepareDetail($data);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function renderPositions($data)
    {
    	$tpl = getTplFromFile('crm/tpl/ContragentDetail.shtml');
    	
    	$tpl->append(tr('Позиции'), 'title');
    	
    	if ($data->addUrl) {
    		$addBtn = ht::createLink("<img src=" . sbf('img/16/add.png') . " style='vertical-align: bottom; margin-left:5px;'>", $data->addUrl, FALSE, 'title=Добавяне на нова позиция');
    		$tpl->append($addBtn, 'title');
    	}
    	
    	$table = cls::get('core_TableView', array('mvc' => $this));
    	$tpl->append($table->get($data->rows, $data->listFields), 'content');
        
    	return $tpl;
    }


    /**
     *
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        if($mvc->haveRightFor('single', $rec)) {
            $row->id = ht::createLink($row->id, array($mvc, 'list', $rec->id));
        }
    }
}