<?php
/**
 * 
 * Мениджър на групи с продукти.
 *
 * @author Stefan Stefanov <stefan.bg@gmail.com>
 *
 */
class cat_Groups extends core_Manager
{
    /**
     *  @todo Чака за документация...
     */
    var $title = "Групи на продуктите";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $pageMenu = "Каталог";
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools, cat_Wrapper';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id,name,info';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,user';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,acc';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,acc,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,acc,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,acc';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar(64)', 'caption=Име, mandatory');
        $this->FLD('info', 'text', 'caption=Инфо');
        $this->FLD('productCnt', 'int', 'input=none');
    }
    

    /**
     *  Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    function on_AfterRecToVerbal ($mvc, $row, $rec)
    {
        //bp($rec);
    }
}