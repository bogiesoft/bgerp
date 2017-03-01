<?php



/**
 * Мениджър на заплати
 *
 *
 * @category  bgerp
 * @package   hr
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Заплати
 */
class hr_Payroll extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Ведомост за заплати';
    
    
     
    /**
     * Заглавието в единично число
     */
    public $singleTitle = 'Фиш';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Rejected,  plg_SaveAndNew, hr_Wrapper';
                    
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,hr';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,hr';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,hr';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,hr';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo,hr';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'periodId,personId,salary,data=@Данни';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'id';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
         // Ключ към мастъра
    	 $this->FLD('periodId',    'key(mvc=acc_Periods, select=title, where=#state !\\= \\\'closed\\\', allowEmpty=true)', 'caption=Период');
    	 $this->FLD('personId',    'key(mvc=crm_Persons,select=name,group=employees)', 'caption=Лице');
    	 $this->FLD('indicators',    'blob', 'caption=Индикатори');
    	 $this->FLD('formula',    'text', 'caption=Формула');
    	 $this->FLD('salary',    'double', 'caption=Заплата,width=100%');
   	 
    	 $this->setDbUnique('periodId,personId');
    }
    
}