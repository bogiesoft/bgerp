<?php


/**
 * Мениджър на документ за обиране на счетоводна разлика
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_BalanceRepairs extends core_Master
{
   
	
   /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'acc_TransactionSourceIntf=acc_transaction_BalanceRepair';
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Корекция на грешки от закръгляния";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, plg_Printing,acc_Wrapper, plg_Sorting, acc_plg_Contable,
                     doc_DocumentPlg, acc_plg_DocumentSummary';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт,valior,balanceId";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'acc_BalanceRepairDetails';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Корекция на грешки от закръгляне';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/blog.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Brp";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'acc,ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'acc,ceo';
    
    
    /**
     * Кой може да го контира?
     */
    var $canConto = 'acc,ceo';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'acc,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'ceo,acc';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'ceo,acc';
    
    
    /**
     * Можели да се контира въпреки че има приключени пера в транзакцията
     */
    public $canUseClosedItems = TRUE;
    
    
    /**
     * Дали при възстановяване/контиране/оттегляне да се заключва баланса
     *
     * @var boolean TRUE/FALSE
     */
    public $lockBalances = TRUE;
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    //var $singleLayoutFile = 'acc/tpl/SingleArticle.shtml';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "6.4|Счетоводни";
    
    
    /**
     * Документи заопашени за обновяване
     */
    protected $updated = array();
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('valior', 'date', 'caption=Вальор,mandatory');
    	$this->FLD('balanceId', 'key(mvc=acc_Balances,select=periodId)', 'caption=Баланс,mandatory');
    }
    
    
    /**
     * След промяна в детайлите на обект от този клас
     */
    public static function on_AfterUpdateDetail(core_Manager $mvc, $id, core_Manager $detailMvc)
    {
    	// Запомняне кои документи трябва да се обновят
    	if(!empty($id)){
    		$mvc->updated[$id] = $mvc->fetchRec($id);
    	}
    }
    
    
    /**
     * След изпълнение на скрипта, обновява записите, които са за ъпдейт
     */
    public static function on_Shutdown($mvc)
    {
    	if(count($mvc->updated)){
    		foreach ($mvc->updated as $rec) {
    			
    			// Обновяваме променените записи, за да се преизчисли дали може да се контира
    			$mvc->save($rec);
    		}
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$form->setDefault('valior', dt::today());
    	
    	if(!empty($form->rec->threadId)){
    		if($origin = doc_Threads::getFirstDocument($form->rec->threadId)){
    			if($origin->getInstance() instanceof acc_ClosePeriods){
    				$periodId = $origin->fetchField('periodId');
    				$bId = acc_Balances::fetchField("#periodId = {$periodId}");
    				$form->setDefault('balanceId', $bId);
    			}
    		}
    	}
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
    	$folderClass = doc_Folders::fetchCoverClassName($folderId);
    
    	return $folderClass == 'doc_UnsortedFolders';
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    
    	// Може да се добавя само към нишка с начало документ 'Приключване на период'
    	if($firstDoc->getInstance() instanceof acc_ClosePeriods){
    			
    		return TRUE;
    	}
    
    	return FALSE;
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(get_called_class());
    	
    	return tr($self->singleTitle) . " №{$rec->id}";
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    
    	$row = new stdClass();
    
    	$row->title = $this->getRecTitle($rec);
    
    	$row->authorId = $rec->createdBy;
    	$row->author = $this->getVerbal($rec, 'createdBy');
    	$row->recTitle = $row->title;
    	$row->state = $rec->state;
    
    	return $row;
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
    	if(acc_Balances::haveRightFor('single', $rec->balanceId)){
    		$row->balanceId = ht::createLink($row->balanceId, array('acc_Balances', 'single', $rec->balanceId), NULL, 'ef_icon=img/16/table_sum.png');
    	}
    	
    	$row->header = $mvc->singleTitle . " №<b>{$row->id}</b> ({$row->state})";
    }
}