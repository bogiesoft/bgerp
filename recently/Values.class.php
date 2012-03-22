<?php



/**
 * Клас 'recently_Values'
 *
 * Поддържа база данни с дефолти за комбо-боксовете
 * дефолтите са въведените данни от потребителите
 * при предишни сесии
 *
 *
 * @category  all
 * @package   recently
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class recently_Values extends core_Manager
{
    
    
    /**
     * Заглавие
     */
    var $title = 'Опции';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Име');
        $this->FLD('value', 'varchar(255)', 'caption=Стойност');
        
        $this->load('plg_Created,plg_RowTools,recently_Wrapper');
    }
    
    
    /**
     * Извиква се след подготовката на формата за редактиране/добавяне $data->form
     */
    function on_AfterPrepareEditForm($invoker, $data)
    {
        if (Request::get('id', 'int')) {
            $data->form->title = 'Редактиране на опция';
        } else {
            $data->form->title = 'Добавяне на опция';
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getSuggestions($name)
    {
        $query = $this->getQuery();
        
        $query->orderBy("#createdOn=DESC");
        
        $opt = array('' => '');
        
        while ($rec = $query->fetch("#name = '{$name}'")) {
            
            $value = $rec->value;
            
            $opt[$value] = $value;
        }
        
        return count($opt) > 1 ? $opt : array();
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function add($name, $value)
    {
        if (!$value)
        return;
        
        $option = addslashes($option);
        $rec = $this->fetch(array(
                "#name = '[#1#]' AND #value = '[#2#]'",
                $name,
                $value
            ));
        
        if ($rec) {
            $fields = "createdOn,createdBy";
        } else {
            $fields = "createdOn,createdBy,name,value";
            $rec->name = $name;
            $rec->value = $value;
        }
        
        $this->save($rec);
    }
    
    
    /**
     * Преди да се извлекат записите за листови изглед,
     * задава подреждане от най-новите към по-старите
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy("#createdOn=DESC");
    }
}