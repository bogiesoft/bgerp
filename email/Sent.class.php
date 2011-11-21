<?php
/**
 * Мениджър на изпратените писма
 * 
 * @category   BGERP
 * @package    email
 * @author	   Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @since      v 0.1
 * @see https://github.com/bgerp/bgerp/issues/108
 */
class email_Sent extends core_Manager
{   
    var $loadList = 'plg_Created,email_Wrapper';

    var $title    = "Изпратени писма";

    var $listFields = 'id, to, threadId, containerId, threadHnd, receivedOn, receivedIp, returnedOn';

    var $canRead   = 'admin,email';
    var $canWrite  = 'admin,email';
    var $canReject = 'admin,email';
    

    function description()
    {
        $this->FLD('to' , 'varchar', 'caption=Изпратен до');
        $this->FLD('threadId' , 'key(mvc=doc_Threads)', 'caption=Нишка');
        $this->FLD('containerId' , 'key(mvc=doc_Containers)', 'caption=Документ,oldFieldName=threadDocumentId');
        $this->FLD('threadHnd' , 'varchar', 'caption=Манипулатор');
        $this->FLD('receivedOn' , 'date', 'caption=Получено->На');
        $this->FLD('receivedIp' , 'varchar', 'caption=Получено->IP');
        $this->FLD('returnedOn' , 'date', 'caption=Върнато на');
    }
    
}