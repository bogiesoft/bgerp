<?php



/**
 * Мениджър за "Бързи бутони" 
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class pos_Favourites extends core_Manager {
    
	
    /**
     * Заглавие
     */
    var $title = "Продукти за бързи бутони";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_Rejected, plg_Sorting,
    				 plg_Printing, pos_Wrapper, plg_State2';

    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'tools=Пулт, productId, pack=Мярка/Опаковка, pointId, catId, image, createdOn, createdBy, state';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
	
	/**
     * Кой може да го прочете?
     */
    var $canRead = 'ceo, pos';
    
    
    /**
     * Кой може да променя?
     */
    var $canAdd = 'ceo, pos';
    
    
    /**
     * Кой може да променя?
     */
    var $canEdit = 'pos, ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,pos';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,pos';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'ceo, pos';
    
	
	/**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('productId', 'key(mvc=cat_Products, select=name)', 'caption=Продукт, mandatory, silent,refreshForm');
    	$this->FLD('packagingId', 'key(mvc=cat_Packagings, select=name, allowEmpty)', 'caption=Опаковка');
    	$this->FLD('catId', 'keylist(mvc=pos_FavouritesCategories, select=name)', 'caption=Категория, mandatory');
    	$this->FLD('pointId', 'keylist(mvc=pos_Points, select=name, makeLinks)', 'caption=Точка на продажба');
    	$this->FLD('image', 'fileman_FileType(bucket=pos_ProductsImages)', 'caption=Картинка');
    	
    	$this->setDbUnique('productId, packagingId');
    }
    
    
    /**
     * Извиква се след подготовката на формата
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	$ProductMan = cls::get('cat_Products');
    	
    	$form->setOptions('productId', array('' => '') + $ProductMan->getByProperty('canSell'));
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
    	if(isset($form->rec->productId)){
    		$ProductMan = cls::get('cat_Products');
    		
    		$packs = $ProductMan->getPacks($form->rec->productId);
    		if(count($packs)){
    			$form->setOptions('packagingId', $packs);
    		} else {
    			$form->setReadOnly('packagingId');
    		}
    	
    		// Само при рефреш слагаме основната опаковка за дефолт
    		if($form->cmd == 'refresh'){
    			$baseInfo = $ProductMan->getBasePackInfo($form->rec->productId);
    	
    			if($baseInfo->classId == 'cat_Packagings'){
    				$form->rec->packagingId = $baseInfo->id;
    			}
    		}
    	}
    	
    	if($form->isSubmitted()) {
    		if(!$productInfo = cat_Products::getProductInfo($form->rec->productId, $form->rec->packagingId)) {
    			$form->setError('productId', 'Избрания продукт не поддържа посочената опаковка');
    		}
    	}
    }
    
    
    /**
     * Метод подготвящ продуктите и формата за филтриране
     * 
     * @return stdClass - обект съдържащ пос продуктите за текущата
     * точка и формата за филтриране
     */
    public static function prepareProducts()
    {
    	$self = cls::get(get_called_class());
    	$productsArr = $self->preparePosProducts();
    	$categoriesArr = pos_FavouritesCategories::prepareAll();
    	
    	return (object)array('arr' => $productsArr, 'categories' => $categoriesArr);
    }
    
    
    /**
     * Подготвя продуктите за показване в пос терминала
     * 
     * @return array $arr - масив от всички позволени продукти
     */
    public function preparePosProducts()
    {
    	$varchar = cls::get('type_Varchar');
    	$double = cls::get('type_Double');
    	$double->params['decimals'] = 2;
    	
    	// Коя е текущата точка на продажба и нейния дефолт контрагент
    	$posRec = pos_Points::fetch(pos_Points::getCurrent('id'));
    	$cache = core_Cache::get('pos_Favourites', "products{$posRec->id}");
    	if(!$cache){
    		$query = static::getQuery();
	    	$query->where("#pointId IS NULL");
	    	$query->orWhere("#pointId LIKE '%{$posRec->id}%'");
	    	$query->where("#state = 'active'");
	    	while($rec = $query->fetch()){
	    		$obj = $this->prepareProductObject($rec);
		    	$obj->name = $varchar->toVerbal($obj->name);
		    	$obj->productId = $rec->productId;
		    	$obj->packagingId = $rec->packagingId;
		    	$cache[$rec->id] = $obj;
	    	}
	    	core_Cache::set('pos_Favourites', "products{$posRec->id}", $cache, 1440, array('cat_Products'));
	    }
    	
	    if($cache){
	    	$date = dt::verbal2mysql();
	    	foreach($cache as $obj){
	    		
	    		// За всеки обект от кеша, изчисляваме актуалната му цена
	    		$price = price_ListRules::getPrice($posRec->policyId, $obj->productId, $obj->packagingId, $date);
	    		$vat = cat_Products::getVat($obj->productId, $date);
	    		$obj->price = $double->toVerbal($price * (1 + $vat));
	    	}
	    }
	    
    	return $cache;
    }
    
    
    /**
     * След запис в модела
     */
	public static function on_AfterSave($mvc, &$id, $rec)
    {
    	// Инвалидираме кеша
    	$cPoint = pos_Points::getCurrent('id', NULL, FALSE);
    	core_Cache::remove('pos_Favourites', "products{$cPoint}");
    }
    
    
    /**
     * Подготвяме единичен продукт от модела
     * 
     * @param stdClass $rec - запис от модела
     * @return stdClass $obj - обект с информацията за продукта
     */
    public function prepareProductObject($rec)
    {
    	// Информацията за продукта с неговата опаковка
    	$info = cat_Products::getProductInfo($rec->productId, $rec->packagingId);
    	$productRec = $info->productRec;
    	$packRec = $info->packagingRec;
    	$arr['name'] = $productRec->name;
    	$arr['catId'] = $rec->catId;
        $obj = new stdClass();
    	$obj->quantity = ($packRec) ? $packRec->quantity : 1;
    	
    	if(empty($rec->image)){
    		$rec->image = cat_Products::getProductImage($rec->productId);
    	}
    	
    	$arr['image'] = $rec->image;
    	
    	return (object)$arr;
    }
    
    
    /**
     * Рендираме Продуктите и техните категории в подходящ вид
     * 
     * @param stdClass $data - обект съдържащ масивите с продуктите,
     * категориите и темата по подразбиране
     * @return core_ET $tpl - шаблона с продуктите
     */
    public static function renderPosProducts($data)
    {
    	$conf = core_Packs::getConfig('pos');
        $ThemeClass = cls::get($conf->POS_PRODUCTS_DEFAULT_THEME);
    	$tpl = $ThemeClass->getFavouritesTpl();
    	$self = cls::get(get_called_class());
    	if($data->arr){
    		$self->renderProducts($data->arr, $tpl);
    	}
    	if($data->categories){
    		$self->renderCategories($data->categories, $tpl);
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Рендира категориите на продуктите в удобен вид
     * 
     * @param array $categories - Масив от продуктовите категории
     * @param core_ET $tpl - шаблона в който ще поставяме категориите
     */
    public function renderCategories($categories, &$tpl)
    {
    	$blockTpl = $tpl->getBlock('CAT');
    	foreach($categories as $cat) {
    		$rowTpl = clone($blockTpl);
    		$rowTpl->placeObject($cat);
    		$rowTpl->removeBlocks();
    		$rowTpl->append2master();
    	}
    }
    
    
    /**
     * Рендира продуктите във вид подходящ за пос терминала
     * 
     * @param array $products - масив от продукти с информацията за тях
     * @return core_ET $tpl - шаблон с продуктите
     */
	public function renderProducts($products, &$tpl)
	{
    	$blockTpl = $tpl->getBlock('ITEM');
		
    	foreach($products as $row) {
    		$row->url = toUrl(array('pos_Receipts', 'addProduct'), 'local');
    		if($row->image){
    		    
    	        $img = new thumb_Img(array($row->image, 80, 80, 'fileman', 'isAbsolute' => FALSE, 'mode' => 'large-no-change'));
    	        $imageURL = $img->getUrl('forced');
    		    
    			$row->image = ht::createElement('img', array('src' => $imageURL, 'width'=>'90px', 'height'=>'90px'));
    		}
    			
    		$rowTpl = clone($blockTpl);
    		$rowTpl->placeObject($row);
    		$rowTpl->removeBlocks();
    		$rowTpl->append2master();
    	}
	}
    
	
    /**
     * Вербална обработка на продуктите
     */
    protected static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
    	$varchar = cls::get('type_Varchar');
    	if($rec->image) {
    		$Fancybox = cls::get('fancybox_Fancybox');
			$row->image = $Fancybox->getImage($rec->image, array(30, 30), array(400, 400));
    	}
    	
    	// До името на продукта показваме неговата основна мярка и ако
    	// има зададена опаковка - колко броя в опаковката има.
    	$info = cat_Products::getProductInfo($rec->productId, $rec->packagingId);
    	
    	if($info->packagingRec) {
    		$measureRow = cat_UoM::getShortName($info->productRec->measureId);
    		$packName = cat_Packagings::getTitleById($rec->packagingId);
    		$quantity = $info->packagingRec->quantity;
    		$row->pack = "{$quantity} {$measureRow} " . tr('в') . " {$packName}";
    	} else {
    		$row->pack = cat_UoM::getTitleById($info->productRec->measureId);
    	}
    	
    	if(!$rec->pointId) {
    		$row->pointId = tr('Всички');
    	}
    	
    	$row->productId = cat_Products::getHyperLink($rec->productId, TRUE);
    }
}