<?php



/**
 * Клас 'cal_Setup' - Инаталиране на пакета "Календар"
 *
 *
 * @category  bgerp
 * @package   cal
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cal_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'cal_Calendar';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Календар за задачи, събития, напомняния и празници";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'cal_Calendar',
            'cal_Tasks',
            'cal_TaskProgresses',
            'cal_Holidays',
        	'cal_Reminders',
            'cal_ReminderSnoozes',
    		'cal_TaskConditions',
    		'cal_TaskDocuments',
            //'migrate::reCalcNextStart'
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'user';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(1.33, 'Указател', 'Календар', 'cal_Calendar', 'default', "powerUser, admin"),
        );



    /**
     * Настройки за Cron
     */
    var $cronSettings = array(
        array(
            'systemId' => "StartReminders",
            'description' => "Известяване за стартирани напомняния",
            'controller' => "cal_Reminders",
            'action' =>"SendNotifications",
            'period' => 1,
            'offset' => 0,
        ),
        
        array(
            'systemId' => "UpdateRemindersToCal",
            'description' => "Обновяване на напомнянията в календара",
            'controller' => "cal_Reminders",
            'action' => "UpdateCalendarEvents",
            'period' => 90,
            'offset' => 0,
        )
    );
    
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
    
        //Създаваме, кофа, където ще държим всички прикачени файлове на напомнянията
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('calReminders', 'Прикачени файлове в напомнянията', NULL, '104857600', 'user', 'user');
    
        return $html;
    }
    
   
    /**
     * Деинсталиране
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    function reCalcNextStart()
    {

        $query = cal_Reminders::getQuery();
        $next12months = dt::addMonths(12, dt::today());
        $now = dt::now();
        $query->where("#state = 'active' AND (#nextStartTime <= '{$now}' OR  #nextStartTime IS NULL OR #nextStartTime >= '{$next12months}') AND #notifySent = 'no'");

        $class = cls::get('cal_Reminders');
        while($rec = $query->fetch()) {
            
            $rec->nextStartTime = $class->calcNextStartTime($rec);
            // Ако изчисленото ново време, не е по-голямо от сега или от началната дата,
            // то продължаваме да го търсим
                while(dt::mysql2timestamp($rec->nextStartTime) < dt::mysql2timestamp(dt::now())) {
                    $rec->timeStart = $rec->nextStartTime;
                    $rec->nextStartTime = $class->calcNextStartTime($rec);
                }

            cal_Reminders::save($rec, 'nextStartTime');
        }
    }
}