<?php
/**
 * Клас 'purchase_Services'
 *
 * Мениджър на Приемателен протокол за услуги
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class purchase_Services extends core_Master
{
    /**
     * Заглавие
     */
    public $title = 'Приемателни протоколи за услуги';


    /**
     * Абревиатура
     */
    public $abbr = 'Pss';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, bgerp_DealIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, purchase_Wrapper, plg_Sorting, plg_Printing, acc_plg_Contable,
                    doc_DocumentPlg, plg_ExportCsv, acc_plg_DocumentSummary,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_HidePrices,
                    doc_plg_BusinessDoc2, plg_LastUsedKeys, plg_Search';

    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, purchase';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, purchase';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, purchase';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo, purchase';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, valior, folderId, amountDelivered, amountDeliveredVat, createdOn, createdBy';


    /**
     * Детайла, на модела
     */
    public $details = 'purchase_ServicesDetails';
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Приемателен протокол за услуги';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'purchase/tpl/SingleLayoutServices.shtml';

   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.4|Логистика";
   
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amountDelivered';
    
    
    /**
     * Опашка от записи за записване в on_Shutdown
     */
    protected $updated = array();
    
      
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'valior, contragentClassId, contragentId, locationId, deliveryTime, folderId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('valior', 'date', 'caption=Дата, mandatory,oldFieldName=date');
        $this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'input=none,caption=Плащане->Валута');
        $this->FLD('currencyRate', 'double(decimals=2)', 'caption=Валута->Курс,width=6em,input=hidden'); 
        $this->FLD('chargeVat', 'enum(yes=Включено, separate=Отделно, exempt=Oсвободено, no=Без начисляване)', 'caption=ДДС,input=hidden');
        
        $this->FLD('amountDelivered', 'double(decimals=2)', 'caption=Доставено->Сума,input=none,summary=amount'); // Сумата на доставената стока
        $this->FLD('amountDeliveredVat', 'double(decimals=2)', 'caption=Доставено->ДДС,summary=amount,input=none');
        $this->FLD('amountDiscount', 'double(decimals=2)', 'input=none');
        
        // Контрагент
        $this->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $this->FLD('contragentId', 'int', 'input=hidden');
        
        // Доставка
        $this->FLD('locationId', 'key(mvc=crm_Locations, select=title)', 'caption=Обект до,silent');
        $this->FLD('deliveryTime', 'datetime', 'caption=Срок до');
        
        // Допълнително
        $this->FLD('note', 'richtext(bucket=Notes,rows=3)', 'caption=Допълнително->Бележки');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Контиран, rejected=Сторнирана)', 
            'caption=Статус, input=none'
        );
        
        $this->FLD('isFull', 'enum(yes,no)', 'input=none,caption=Тегло,notNull,default=yes');
    }


    /**
     * След промяна в детайлите на обект от този клас
     */
    public static function on_AfterUpdateDetail(core_Manager $mvc, $id, core_Manager $detailMvc)
    {
        // Запомняне кои документи трябва да се обновят
    	$mvc->updated[$id] = $id;
    }
    
    
    /**
     * Обновява информацията на документа
     * @param int $id - ид на документа
     */
    public function updateMaster($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	$query = $this->purchase_ServicesDetails->getQuery();
        $query->where("#shipmentId = '{$id}'");
        
        price_Helper::fillRecs($query->fetchAll(), $rec);
        
        // ДДС-т е отделно amountDeal  е сумата без ддс + ддс-то, иначе самата сума си е с включено ддс
        $amount = ($rec->chargeVat == 'separate') ? $rec->_total->amount + $rec->_total->vat : $rec->_total->amount;
        $amount -= $rec->_total->discount;
        $rec->amountDelivered = $amount * $rec->currencyRate;
        $rec->amountDeliveredVat = $rec->_total->vat * $rec->currencyRate;
        $rec->amountDiscount = $rec->_total->discount * $rec->currencyRate;
        
        // Записване в кеш полето дали има още продукти за добавяне
        $origin = $this->getOrigin($rec);
		$dealAspect = $origin->getAggregateDealInfo()->agreed;
		$invProducts = $this->getDealInfo($rec->id)->shipped;
		$rec->isFull = (!bgerp_iface_DealAspect::buildProductOptions($dealAspect, $invProducts, 'services')) ? 'yes' : 'no';
        $this->save($rec);
    }
    
    
    /**
     * След изпълнение на скрипта, обновява записите, които са за ъпдейт
     */
    public static function on_Shutdown($mvc)
    {
        if(count($mvc->updated)){
        	foreach ($mvc->updated as $id) {
	        	$mvc->updateMaster($id);
	        }
        }
    }
    
    
    /**
     * След създаване на запис в модела
     */
    public static function on_AfterCreate($mvc, $rec)
    {
        $origin = static::getOrigin($rec);
        
        // Ако новосъздадения документ има origin, който поддържа bgerp_AggregateDealIntf,
        // използваме го за автоматично попълване на детайлите на протокола
        expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
        
        /* @var $aggregatedDealInfo bgerp_iface_DealResponse */
        $aggregatedDealInfo = $origin->getAggregateDealInfo();
            
        $remainingToShip = clone $aggregatedDealInfo->agreed;
        $remainingToShip->pop($aggregatedDealInfo->shipped);
            
        /* @var $product bgerp_iface_DealProduct */
        foreach ($remainingToShip->products as $product) {
            $info = cls::get($product->classId)->getProductInfo($product->productId, $product->packagingId);
                
            // Пропускат се експедираните и складируемите артикули
            if (isset($info->meta['canStore']) || $product->quantity <= 0) continue;
            
            $shipProduct = new stdClass();
            $shipProduct->shipmentId  = $rec->id;
            $shipProduct->classId     = $product->classId;
            $shipProduct->productId   = $product->productId;
            $shipProduct->packagingId = $product->packagingId;
            $shipProduct->quantity    = $product->quantity;
            $shipProduct->price       = $product->price;
            $shipProduct->uomId       = $product->uomId;
            $shipProduct->discount    = $product->discount;
            $shipProduct->quantityInPack = ($product->packagingId) ? $info->packagingRec->quantity : 1;
                
            $mvc->purchase_ServicesDetails->save($shipProduct);
        }
    }


    /**
     * След оттегляне на документа
     */
    public static function on_AfterReject($mvc, &$res, $id)
    {
        // Нотифициране на origin-документа, че някой от веригата му се е променил
        if ($origin = $mvc->getOrigin($id)) {
            $ref = new core_ObjectReference($mvc, $id);
            $origin->getInstance()->invoke('DescendantChanged', array($origin, $ref));
        }
    }
    
    
	/**
     * Подготвя вербалните данни на моята фирма
     */
    private function prepareHeaderInfo(&$row, $rec)
    {
    	$ownCompanyData = crm_Companies::fetchOwnCompany();
        $row->MyCompany = $ownCompanyData->company;
        $row->MyAddress = cls::get('crm_Companies')->getFullAdress($ownCompanyData->companyId);
        
        $uic = drdata_Vats::getUicByVatNo($ownCompanyData->vatNo);
        if($uic != $ownCompanyData->vatNo){
    		$row->MyCompanyVatNo = $ownCompanyData->vatNo;
    	}
    	$row->uicId = $uic;
    	
    	// Данните на клиента
        $ContragentClass = cls::get($rec->contragentClassId);
    	$row->contragentName = $ContragentClass->getTitleById($rec->contragentId);
        $row->contragentAddress = $ContragentClass->getFullAdress($rec->contragentId);
    }
    
    
    /**
     * След рендиране на сингъла
     */
    function on_AfterRenderSingle($mvc, $tpl, $data)
    {
    	if(Mode::is('printing') || Mode::is('text', 'xhtml')){
    		$tpl->removeBlock('header');
    	}
    }
    
    
	/**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    public function prepareSingle_($data)
    {
    	parent::prepareSingle_($data);
    	
    	$rec = &$data->rec;
    	if(empty($data->noTotal)){
    		$data->summary = price_Helper::prepareSummary($rec->_total, $rec->valior, $rec->currencyRate, $rec->currencyId, $rec->chargeVat);
    		$data->row = (object)((array)$data->row + (array)$data->summary);
    	}
    }
    
    
    /**
     * След подготовка на единичния изглед
     */
    public static function on_AfterPrepareSingle($mvc, &$res, &$data)
    {
    	$rec = &$data->rec;
    	$data->row->header = $mvc->singleTitle . " №<b>{$data->row->id}</b> ({$data->row->state})";
    	
    	// Бутон за отпечатване с цени
        $data->toolbar->addBtn('Печат (с цени)', array($mvc, 'single', $rec->id, 'Printing' => 'yes', 'showPrices' => TRUE), 'id=btnPrintP,target=_blank,row=2', 'ef_icon = img/16/printer.png,title=Печат на страницата');
    	
    	if(haveRole('debug')){
    		$data->toolbar->addBtn("Бизнес инфо", array($mvc, 'DealInfo', $rec->id), 'ef_icon=img/16/bug.png,title=Дебъг');
    	}
    	
    	if($data->rec->state == 'active' && sales_Invoices::haveRightFor('add')){
    		$originId = doc_Threads::getFirstContainerId($rec->threadId);
	    	$data->toolbar->addBtn("Фактура", array('sales_Invoices', 'add', 'originId' => $originId), 'ef_icon=img/16/invoice.png,title=Създаване на фактура,order=9.9993');
	    }
	}
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
        // Задаване на стойности на полетата на формата по подразбиране
        $form = &$data->form;
        $rec  = &$form->rec;
        
        $form->setDefault('valior', dt::now());
        
        $rec->contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
        $rec->contragentId = doc_Folders::fetchCoverId($rec->folderId);
        
        // Поле за избор на локация - само локациите на контрагента по покупката
        $form->getField('locationId')->type->options = 
            array('' => '') + crm_Locations::getContragentOptions($rec->contragentClassId, $rec->contragentId);
        
        // Ако създаваме нов запис и то базиран на предхождащ документ ...
        if (empty($form->rec->id)) {
        	
            // ... проверяваме предхождащия за bgerp_DealIntf
            $origin = ($form->rec->originId) ? doc_Containers::getDocument($form->rec->originId) : doc_Threads::getFirstDocument($form->rec->threadId);
            expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
            	
            /* @var $dealInfo bgerp_iface_DealResponse */
            $dealInfo = $origin->getAggregateDealInfo();
            $form->rec->currencyId = $dealInfo->agreed->currency;
            $form->rec->currencyRate = $dealInfo->agreed->rate;
            $form->rec->locationId = $dealInfo->agreed->delivery->location;
            $form->rec->deliveryTime = $dealInfo->agreed->delivery->time;
            $form->rec->chargeVat = $dealInfo->agreed->vatType;
        }
    }
    
    
	/**
     * След преобразуване на записа в четим за хора вид
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	if(isset($fields['-list'])){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    		if($rec->amountDeliveredVat || $rec->amountDelivered){
    			$row->amountDeliveredVat = "<span class='cCode' style='float:left'>{$rec->currencyId}</span> &nbsp;{$row->amountDeliveredVat}";
    			$row->amountDelivered = "<span class='cCode' style='float:left'>{$rec->currencyId}</span> &nbsp;{$row->amountDelivered}";
    		} else {
    			$row->amountDeliveredVat = "<span class='quiet'>0.00</span>";
    		}
    	}
    	
    	if(isset($fields['-single'])){
    		$mvc->prepareHeaderInfo($row, $rec);
    	}
    }


    /**
     * Протокола не може да бъде начало на нишка; може да се създава само в съществуващи нишки
     *
     * @param $folderId int ид на папката
     * @return boolean
     */
    public static function canAddToFolder($folderId)
    {
        return FALSE;
    }
    
    
    /**
     * Може ли протокол да се добави в посочената нишка?
     * Експедиционните нареждания могат да се добавят само в нишки с начало - документ-покупка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
    public static function canAddToThread($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
    	$docState = $firstDoc->fetchField('state');
    
    	// Ако началото на треда е активирана покупка
    	if(($firstDoc->instance() instanceof purchase_Purchases) && $docState == 'active'){
    		
    		// Ако има поне един нескладируем продукт в покупката
    		return $firstDoc->hasStorableProducts(FALSE);
    	}
    	
    	return FALSE;
    }
        
    
    /**
     * @param int $id key(mvc=purchase_Purchases)
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
        expect($rec = $this->fetch($id));
        $title = "Протокол за покупка на услуги №{$rec->id} / " . $this->getVerbal($rec, 'valior');
        $row = (object)array(
            'title'    => $title,
            'authorId' => $rec->createdBy,
            'author'   => $this->getVerbal($rec, 'createdBy'),
            'state'    => $rec->state,
            'recTitle' => $title
        );
        
        return $row;
    }
    
    
	/**
     * Връща масив от използваните нестандартни артикули в протоколa
     * @param int $id - ид на протоколa
     * @return param $res - масив с използваните документи
     * 					['class'] - инстанция на документа
     * 					['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
    	$res = array();
    	$dQuery = $this->purchase_ServicesDetails->getQuery();
    	$dQuery->EXT('state', 'purchase_Services', 'externalKey=shipmentId');
    	$dQuery->where("#shipmentId = '{$id}'");
    	$dQuery->groupBy('productId,classId');
    	while($dRec = $dQuery->fetch()){
    		$productMan = cls::get($dRec->classId);
    		if(cls::haveInterface('doc_DocumentIntf', $productMan)){
    			$res[] = (object)array('class' => $productMan, 'id' => $dRec->productId);
    		}
    	}
    	return $res;
    }
    

    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     * 
     * @param int|object $id
     * @return bgerp_iface_DealResponse
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function getDealInfo($id)
    {
        $rec = new purchase_model_Service($id);
        $result = new bgerp_iface_DealResponse();
        
        $result->dealType = bgerp_iface_DealResponse::TYPE_PURCHASE;
        
        // Конвертираме данъчната основа към валутата идваща от покупката
        $result->shipped->amount             = $rec->amountDelivered;
        $result->shipped->currency		 	 = $rec->currencyId;
        $result->shipped->rate		         = $rec->currencyRate;
        $result->shipped->valior 			 = $rec->valior;
        $result->shipped->vatType            = $rec->chargeVat;
        $result->shipped->delivery->location = $rec->locationId;
        $result->shipped->delivery->time     = $rec->deliveryTime;
        
        /* @var $dRec purchase_model_Service */
        foreach ($rec->getDetails('purchase_ServicesDetails') as $dRec) {
            $p = new bgerp_iface_DealProduct();
            
            $p->classId     = $dRec->classId;
            $p->productId   = $dRec->productId;
            $p->packagingId = $dRec->packagingId;
            $p->discount    = $dRec->discount;
            $p->quantity    = $dRec->quantity;
            $p->price       = $dRec->price;
            $p->uomId       = $dRec->uomId;
            
            $result->shipped->products[] = $p;
        }
        
        return $result;
    }
    
    
	/**
     * В кои корици може да се вкарва документа
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getAllowedFolders()
    {
    	return array('doc_ContragentDataIntf');
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    	}
    }
    
    
 	/**
     * Дебъг екшън показващ агрегираните бизнес данни
     */
    function act_DealInfo()
    {
    	requireRole('debug');
    	expect($id = Request::get('id', 'int'));
    	$info = $this->getDealInfo($id);
    	bp($info->shipped, $this->fetch($id));
    }
    
    
    /**
     * Финализиране на транзакцията
     * @param int $id
     */
	public function finalizeTransaction($id)
    {
        $rec = $this->fetchRec($id);
        $rec->state = 'active';
        
        if ($this->save($rec)) {
            $this->invoke('Activation', array($rec));
        }
        
        // Нотификация към пораждащия документ, че нещо във веригата му от породени документи се е променило.
        if ($origin = $this->getOrigin($rec)) {
            $rec = new core_ObjectReference($this, $rec);
            $origin->getInstance()->invoke('DescendantChanged', array($origin, $rec));
        }
    }
    
    
    /**
     * Транзакция за запис в журнала
     * @param int $id
     */
	public function getTransaction($id)
    {
        $entries = array();
        $rec = new purchase_model_Service($id);
        $currencyId = currency_Currencies::getIdByCode($rec->currencyId);
        
        $detailsRec = $rec->getDetails('purchase_ServicesDetails');
        if(count($detailsRec)){
        	price_Helper::fillRecs($detailsRec, $rec);
        	
	        foreach ($detailsRec as $dRec) {
	        	$amount = ($dRec->discount) ?  $dRec->amount * (1 - $dRec->discount) : $dRec->amount;
	        	
	        	$entries[] = array(
	                'amount' => currency_Currencies::round($amount * $rec->currencyRate), // В основна валута
	                
	                'debit' => array(
	                    '602', // Сметка "602. Разходи за външни услуги"
                        	array($dRec->classId, $dRec->productId), // Перо 1 - Артикул
                    	'quantity' => $dRec->quantity, // Количество продукт в основната му мярка
	                ),
	                
	                'credit' => array(
	                    '401', // Сметка "401. Задължения към доставчици (Доставчик, Валути)"
                       		array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Доставчик
                       		array('currency_Currencies', $currencyId),          // Перо 2 - Валута
                    	'quantity' => currency_Currencies::round($amount, $rec->currencyId), // "брой пари" във валутата на покупката
	                ),
            	);
	        }
        }
        
        $transaction = (object)array(
            'reason'  => 'Протокол за покупка на услуги #' . $rec->id,
            'valior'  => $rec->valior,
            'entries' => $entries, 
        );
        
        return $transaction;
    }
    
    
	/**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашия протокол за покупка на услуги") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
     /**
      * Добавя ключови думи за пълнотекстово търсене, това са името на
      * документа или папката
      */
     function on_AfterGetSearchKeywords($mvc, &$res, $rec)
     {
     	// Тук ще генерираме всички ключови думи
     	$detailsKeywords = '';

     	// заявка към детайлите
     	$query = purchase_ServicesDetails::getQuery();
     	// точно на тази фактура детайлите търсим
     	$query->where("#shipmentId = '{$rec->id}'");
     	
	        while ($recDetails = $query->fetch()){
	        	// взимаме заглавията на продуктите
	        	$productTitle = cls::get($recDetails->classId)->getTitleById($recDetails->productId);
	        	// и ги нормализираме
	        	$detailsKeywords .= " " . plg_Search::normalizeText($productTitle);
	        }
	        
    	// добавяме новите ключови думи към основните
    	$res = " " . $res . " " . $detailsKeywords;
     }
     
     
	/**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        return tr("|Приемателен протокол за услуги|* №") . $rec->id;
    }
    
    
	/**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->isSubmitted()) {
        	
        	if(empty($rec->isFull)){
        		
        		// Сетване на кеш полето че ЕН-то не е запълнено
        		$rec->isFull = 'no';
        	}
        }
    }
    
    
	/**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
        // Ако резултата е 'no_one' пропускане
    	if($res == 'no_one') return;
    	
    	// Документа не може да се контира, ако ориджина му е в състояние 'closed'
    	if($action == 'conto' && isset($rec)){
	    	$originState = $mvc->getOrigin($rec)->fetchField('state');
	        if($originState === 'closed'){
	        	$res = 'no_one';
	        }
        }
    }
}