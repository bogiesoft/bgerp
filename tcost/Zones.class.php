<?php


/**
 * Модел "Транспортни зони"
 *
 *
 * @category  bgerp
 * @package   tcost
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class tcost_Zones extends core_Detail
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'trans_Zones';
	
	
    /**
     * Заглавие
     */
    public $title = "Транспортни зони";


    /**
     * Плъгини за зареждане
     */
    public $loadList = "plg_Created, plg_Sorting, plg_RowTools2, tcost_Wrapper";


    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = "countryId, pCode, createdOn, createdBy";


    /**
     * Ключ към core_Master
     */
    public $masterKey = 'zoneId';


    /**
     * Единично заглавие
     */
    public $singleTitle = "Зона";


    /**
     * Време за опресняване информацията при лист на събитията
     */
    public $refreshRowsTime = 5000;


    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,tcost';


    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,tcost';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,tcost';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,tcost';


    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,tcost';


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('zoneId', 'key(mvc=tcost_FeeZones, select=name)', 'caption=Зона, recently, mandatory,smartCenter');
        $this->FLD('countryId', 'key(mvc = drdata_Countries, select = letterCode2)', 'caption=Държава, mandatory,smartCenter');
        $this->FLD('pCode', 'varchar(16)', 'caption=П. код,recently,class=pCode,smartCenter, notNull');
        $this->setDbUnique("countryId, pCode");
    }


    /**
     * Връща името на транспортната зона според държавата, усложието на доставката и п.Код
     * 
     * @param int $deliveryTermId - ид на условието на доставка
     * @param int $countryId - id на съотверната държава
     * @param string $pCode - пощенски код
     * 
     * @return array
     * ['zoneId'] - id на намерената зона
     * ['zoneName'] - име на намерената зона
     */
    public static function getZoneIdAndDeliveryTerm($deliveryTermId, $countryId, $pCode = "")
    {
        $query = self::getQuery();
        $query->EXT('deliveryTermId', 'tcost_FeeZones', 'externalName=deliveryTermId,externalKey=zoneId');
        $query->where(array("#deliveryTermId = '[#1#]'", $deliveryTermId));
        
        if(empty($pCode)){
            $query->where(array("#countryId = [#1#] AND #pCode = '[#2#]'", $countryId, $pCode));
            $rec = $query->fetch();
            $bestZone = $rec;
        } else{
        	// Обхождане на tcost_zones базата и намиране на най-подходящата зона
        	$query->where(array('#countryId = [#1#]', $countryId));
        	$bestSimilarityCount = 0;
        	while($rec = $query->fetch()) {
            	$similarityCount = self::strNearPCode((string)$pCode, $rec->pCode);
                	if($similarityCount > $bestSimilarityCount) {
                    	$bestSimilarityCount = $similarityCount;
                    	$bestZone = $rec;
                }
            }
        }
        
        // Ако няма зона NULL
        if(empty($bestZone)) return NULL;
        
        // Намиране на името на намерената зона
        $zoneName = tcost_FeeZones::getVerbal($bestZone->zoneId, 'name');

        return array('zoneId' => $bestZone->zoneId, 'zoneName' => $zoneName);
    }

    
    /**
     * Сравнява колко близо са два пощенски кода
     * 
     * @param   $pc1    Първи данни за сравнение
     * @param   $pc2    Втори данни за сравнение
     * @return  int     Брой съвпадения
     */
    private static function strNearPCode($pc1, $pc2)
    {
        if(strlen($pc1) > strlen($pc2)) {
        	list($pc1, $pc2) = array($pc2, $pc1);
        }
    	
        // Връща стринга с най-малък код
        $cycleNumber = min(strlen($pc1), strlen($pc2));

        for($i= 0; $i < $cycleNumber; $i++)
        {
            if($pc1{$i} != $pc2{$i}) {
                return $i;
            }
        }
        
        return strlen($pc1);
    }
}