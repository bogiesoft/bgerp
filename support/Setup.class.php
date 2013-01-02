<?php


/**
 * Инсталиране/Деинсталиране на мениджъри свързани с support модула
 *
 * @category  bgerp
 * @package   support
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class support_Setup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'support_Issues';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Сигнали";
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        // Инсталиране на мениджърите
        $managers = array(
            'support_Issues',
            'support_Components',
            'support_Systems',
            'support_IssueTypes',
        );
        
        $instances = array();
        
        foreach ($managers as $manager) {
            $instances[$manager] = &cls::get($manager);
            $html .= $instances[$manager]->setupMVC();
        }

        // Добавяме менюто
        $Menu = cls::get('bgerp_Menu');
        $html .= $Menu->addItem(2.14, 'Обслужване', 'Поддръжка', 'support_Issues', 'default', "user");
        
        // Зареждаме всички данни след инсталацията
        $html .= support_IssueTypes::loadData();
        
        // Добавяме роля за поддръжка на модула support
        $role = 'support';
        $html .= core_Roles::addRole($role) ? "<li style='color:green'>Добавена е роля <b>$role</b></li>" : '';
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}