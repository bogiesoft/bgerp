<?php



/**
 * Клас acc_type_Accounts
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_type_Accounts extends type_Keylist
{
    
    
    /**
     * Максимум предложения
     */
    const MAX_SUGGESTIONS = 1000;
    
    
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        $params['params']['mvc'] = 'acc_Accounts';
        
        setIfNot($params['params']['select'], 'title');
        setIfNot($params['params']['root'], '');
        setIfNot($params['params']['regInterfaces'], '');
        
        setIfNot($params['params']['maxSuggestions'], self::MAX_SUGGESTIONS);
        
        parent::init($params);
    }
    
    
    /**
     * Подготвя опциите според зададените параметри.
     *
     * `$this->params['root']` е префикс, който трябва да имат номерата на всички сметки-опции
     */
    private function prepareOptions()
    {
        if (isset($this->options)) {
            return;
        }
        $mvc = cls::get($this->params['mvc']);
        $root = $this->params['root'];
        $select = $this->params['select'];
        $regInterfaces = $this->params['regInterfaces'];
        
        $suggestions = $mvc->makeArray4Select($select, array("#num LIKE '[#1#]%' AND state NOT IN ('closed')", $root));
    	
        // Ако има зададени интерфейси на аналитичностите
        if($regInterfaces){
    		$this->filterSuggestions($regInterfaces, $suggestions);
    	}
    	
    	$this->suggestions = $suggestions;
    }
    
    
    /**
     * Помощна ф-я филтрираща опциите на модела, така че аналитичностите на
     * сметките да отговарят на някакви интерфейси. Подредбата на итнерфейсите
     * трябва да отговаря на тази на аналитичностите
     * 
     * @param string $list - имената на интерфейсите разделени с "|"
     * @param array $suggestions - подадените предложения
     */
    private function filterSuggestions($list, &$suggestions)
    {
    	$arr = explode('|', $list);
    	expect(count($arr) <= 3, 'Най-много могат да са зададени 3 итнерфейса');
    	foreach ($arr as $index => $el){
    		expect($arr[$index] = core_Interfaces::fetchField("#name = '{$el}'", 'id'), "Няма интерфейс '{$el}'");
    	}
    	
    	if(count($suggestions)){
    		
    		// За всяка сметка
    		foreach ($suggestions as $id => $sug){
    			
    			// Извличане на записа на сметката
    			$rec = acc_Accounts::fetch($id);
    			
    			// За всеки итнерфейс
    			foreach ($arr as $index => $el){
    				
    				// Ако съответния запис няма аналитичност се премахва
    				$fld = "groupId" . ++$index;
    				if(!isset($rec->$fld)) {
    					unset($suggestions[$id]);
    					break;
    				}
    				
    				// Ако има аналитичност, се извлича интерфейса, който поддържа
    				$listIntf = acc_Lists::fetchField($rec->$fld, 'regInterfaceId');
    				
    				// Ако интерфейса не съвпада с подадения, записа се премахва
    				if($listIntf != $el){
    					unset($suggestions[$id]);
    				}
    			}
    		}
    	}
    }
    
    
    /**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = "", &$attr = array())
    {
        $this->prepareOptions();
        
        return parent::renderInput_($name, $value, $attr);
    }
    
    
    /**
     * Конвертира стойността от вербална към (int) - ключ към core_Interfaces
     */
    function fromVerbal_($value)
    {
        $this->prepareOptions();
        
        return parent::fromVerbal_($value);
    }
}