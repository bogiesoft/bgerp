<?php



/**
 * Мениджър на отчети от Задание за производство
 *
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cat_reports_BomsRep extends frame_BaseDriver
{                  
	
    /**
     * Заглавие
     */
    public $title = 'Артикули » Задание за производство';

    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';


    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;
    
    
    /**
     * Работен кеш
     */
    protected $cache = array();
    
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'cat,ceo,sales,purchase';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'cat,ceo,sales,purchase';
    
    
    /**
     * Права за писане
     */
    public $canEdit = 'cat,ceo,sales,purchase';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'cat,ceo,sales,purchase';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'cat,ceo,sales,purchase';

    
    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Fieldset $fieldset
     */
    public function addEmbeddedFields(core_FieldSet &$form)
    {
    	$form->FLD('saleId', 'keylist(mvc=sales_Sales, select=id)', 'caption=Договор за продажба');
    }
      

    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     *
     * @param core_Form $form
     */
    public function prepareEmbeddedForm(core_Form &$form)
    {
        $opt = $this->prepareOptions();

        $form->setSuggestions('saleId', array('' => '') + $opt);
    	
    	$this->invoke('AfterPrepareEmbeddedForm', array($form));
    }
    
    
    /**
     * Проверява въведените данни
     *
     * @param core_Form $form
     */
    public function checkEmbeddedForm(core_Form &$form)
    {
    	    	 
    }  

    
    /**
     * Подготвя вътрешното състояние, на база въведените данни
     *
     * @param core_Form $innerForm
     */
    public function prepareInnerState()
    {
    	$data = new stdClass();
        $data->articleCnt = array();
        $data->recs = array();
        $fRec = $data->fRec = $this->innerForm;

        $this->prepareListFields($data);
        
        $salesArr = arr::make($fRec->saleId,TRUE);
        $salesArr = implode(',', $salesArr);

        $query = planning_Jobs::getQuery();
        $query->where("#saleId IN ('{$salesArr}') AND #state = 'active'");


        // за всяко едно активно Задания за производство
        while($rec = $query->fetch()) { 

            // Намираме рецептата за артикула (ако има)
            $bomId = cat_Products::getLastActiveBom($rec->productId, 'production')->id;
            if(!$bomId) {
                $bomId = cat_Products::getLastActiveBom($rec->productId, 'sales')->id;
            }
            
            if (isset($bomId)) {
                $queryDetail = cat_BomDetails::getQuery();
                $queryDetail->where("#bomId = '{$bomId}'");
                
                $products = array();
                $materials = array();
                $mArr = cat_Products::getMaterialsForProduction($rec->productId,$rec->quantity, TRUE); 
                
                while($recDetail = $queryDetail->fetch()) {
                 $a[]=$recDetail;
                    $index = $recDetail->resourceId;
                    if(!array_key_exists($index, $data->recs)){
                         
                        if(!$recDetail->parentId || $recDetail->type == 'stage') {
                            unset($mArr[$index]);
                            $data->recs[$index] =
                            (object) array ('id' => $recDetail->id,
                                'article' => $recDetail->resourceId,
                                'articleCnt'	=> $rec->quantity * $recDetail->propQuantity,
                                'params' => $recDetail->params,
                                'materials' => 0,);
                        }
                    } else {
                    
                        $obj = &$data->recs[$index];
                        $obj->quantity += $quantity;
       
                    }

                    if($recDetail->type == 'input' && isset($recDetail->parentId)) $materials[$recDetail->parentId][] = $recDetail->resourceId;
                }
            }
        }
        
        foreach ($data->recs as $rec){
            if(is_array($materials)){
                $rec->materials = $materials[$rec->id];
            }
        }

        return $data;
    }
    
    
    /**
     * След подготовката на показването на информацията
     */
    public function on_AfterPrepareEmbeddedData($mvc, &$res)
    {

        // Подготвяме страницирането
    	$data = $res;
        
        $pager = cls::get('core_Pager',  array('itemsPerPage' => $mvc->listItemsPerPage));
        $pager->setPageVar($mvc->EmbedderRec->className, $mvc->EmbedderRec->that);
        $pager->addToUrl = array('#' => $mvc->EmbedderRec->instance->getHandle($mvc->EmbedderRec->that));

        $pager->itemsCount = count($data->recs);
        $data->pager = $pager;
        
        $recs = array();
        foreach($data->recs as $rec){
            $recs[] = $rec;
        }

        if(count($recs)){
     
            foreach ($recs as $id => $r){ 
                
                $r->num = $id +1;

				if(!$pager->isOnPage()) continue;
				
				$row = new stdClass();
                $row = $mvc->getVerbal($r);
                $data->rows[$id] = $row;
                
            }
        }

        $res = $data;
    }
    
    
    /**
     * Вербалното представяне на ред от таблицата
     */
    protected function getVerbal_($rec)
    {

        $RichtextType = cls::get('type_Richtext');
        $Blob = cls::get('type_Blob');
        $Int = cls::get('type_Int');
        $Double = cls::get('type_Double', array('params' => array('decimals' => 2)));
        
        $row = new stdClass();
        
        $row->num = $Int->toVerbal($rec->num);
        $row->article = cat_Products::getShortHyperlink($rec->article);
        $row->articleCnt = $Int->toVerbal($rec->articleCnt);
        
        if(is_array($rec->params)) {
            unset($rec->params['$T']);
            
            foreach($rec->params as $name=>$val) {
                $name = str_replace("$", "", $name);
                $name = str_replace("_", " ", $name);
                
                $row->params .= $name . ": " . $val . "<br/>";
   
            }
        }
        
        if(is_array($rec->materials)) {
            foreach ($rec->materials as $material) {
                $row->materials .= cat_Products::getShortHyperlink($material) . "<br/>";
            }
        }

        return $row;
    }
    

    /**
     * Връща шаблона на репорта
     *
     * @return core_ET $tpl - шаблона
     */
    public function getReportLayout_()
    {
        $tpl = getTplFromFile('cat/tpl/BomRepLayout.shtml');
         
        return $tpl;
    }
    
    
    /**
     * Полетата, които се
     * показват в табличния изглед
     *
     * @return array
     */
    protected function prepareListFields_(&$data)
    {
        // Кои полета ще се показват
        $data->listFields = arr::make("num=№,
                             article=Детайл,
    					     articleCnt=Брой,
    					     params=Параметри,
                             materials=Материали", TRUE);
  
    }
    
    
    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData(&$embedderTpl, $data)
    {
    	
    	if(empty($data)) return;
    	 
    	$tpl = $this->getReportLayout();
    	
    	$title = explode(" » ", $this->title);
    	
    	$tpl->replace($title[1], 'TITLE');
    	
    	$opt = self::prepareOptions();
    	$saleId = substr($data->fRec->saleId, 1, strlen($data->fRec->saleId)-2);

    	$tpl->replace($opt[$saleId], 'saleId');
   
    	$tpl->placeObject($data->rec);

    	$f = cls::get('core_FieldSet');
    	
    	$f->FLD('num', 'varchar');
    	$f->FLD('article', 'varchar');
    	$f->FLD('articleCnt', 'int', 'tdClass=accItemClass,smartCenter');
    	$f->FLD('params', 'varchar');
    	$f->FLD('materials', 'varchar');

    	$table = cls::get('core_TableView', array('mvc' => $f));

    	$tpl->append($table->get($data->rows, $data->listFields), 'CONTENT');

    	if($data->pager){
    	     $tpl->append($data->pager->getHtml(), 'PAGER');
    	}

    	$embedderTpl->append($tpl, 'data');
    } 
    
    
    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     * @todo да се замести в кода по-горе
     */
    protected function getFields_()
    {
        // Кои полета ще се показват
        $f = new core_FieldSet;
        $f->FLD('num', 'int');
        $f->FLD('article', 'varchar');
        $f->FLD('articleCnt', 'int');
        $f->FLD('params', 'varchar');
        $f->FLD('materials', 'varchar');

    
        return $f;
    }
    
    
    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     */
    protected function getExportFields_()
    {
        // Кои полета ще се показват
        $fields = arr::make("num=№,
                             article=Детайл,
    					     articleCnt=Брой,
                             params=Параметри,
    					     materials=Материали", TRUE);
        
        return $fields;
    }
    
    
    /**
     * Създаваме csv файл с данните
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    public function exportCsv()
    {
        $exportFields = $this->getExportFields();
        $fields = $this->getFields();

        $dataRec = array();
    
        $csv = csv_Lib::createCsv($this->prepareEmbeddedData()->rows, $fields, $exportFields);
         
        return $csv;
    }
     
    
    /**
     * Скрива полетата, които потребител с ниски права не може да вижда
     *
     * @param stdClass $data
     */
    public function hidePriceFields()
    {
    }
    
    
    /**
     * Коя е най-ранната дата на която може да се активира документа
     */
    public function getEarlyActivation()
    {
    	//return $this->innerForm->to;
    }
    
    
    /**
     * Подготвя опциите според състояние и производимост.
     *
     */
    public function prepareOptions()
    {
        // Всички договори/поръчки
        $query = sales_Sales::getQuery();
        // активен ли е?
        $query->where("#state = 'active'");
        
        $options = array();
        
        while($recSale = $query->fetch()){
            // детайла
            $queryDetail = sales_SalesDetails::getQuery();
            $queryDetail->where("#saleId = '{$recSale->id}'");
            while($recDetail = $queryDetail->fetch()){
                // производим ли е?
                $canManifacture = cat_Products::fetchField($recDetail->productId, 'canManifacture');
                // ако е
                if($canManifacture == "yes") {
                    // хендлър
                    $handle = sales_Sales::getHandle($recSale->id);
                    // дата
                    $valior = dt::mysql2verbal($recSale->valior, "d.m.y");
                    // контрагент
                    $Contragent = cls::get($recSale->contragentClassId);
                    $contragent = $Contragent->getTitleById($recSale->contragentId);
                    
                    $string = $handle . "/" . $valior . " " . $contragent;
                    // правим масив с опции
                    $options[$recSale->id] = $string;
                }
            }
        }
    
        return $options;
    }
}