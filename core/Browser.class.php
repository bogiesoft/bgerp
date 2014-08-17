<?php


/**
 * Клас 'core_Browser' - Определя параметрите на потребителския браузър
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg> и Yusein Yuseinova <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Browser extends core_Manager
{
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Потребителски браузър';
    
    
    /**
     * 
     */
    const HASH_LENGTH = 6;
    
    
    /**
     * 
     */
    const BRID_NAME = 'brid';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'no_one';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Името на полито, по което плъгина GroupByDate ще групира редовете
     */
    var $groupByDateField = 'createdOn';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_SystemWrapper, plg_Created, plg_GroupByDate, plg_RowTools';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('brid', 'varchar(8)', 'caption=BRID');
        $this->FLD('userAgent', 'text', 'caption=User agent');
        
        $this->setDbUnique('brid');
    }
    

    /**
     * След преобразуването към вербални стойности, проказваме OS и Browser, като
     * скриваме USER_AGENT стринга зад отварящ се блок
     */
    function on_AfterRecToVerbal($mvc, $row, $rec, $fields)
    {
        if($row->userAgent) {
            $os = static::getUserAgentOsName($rec->userAgent);
            $browser = static::getUserAgentBrowserName($rec->userAgent);
            $row->userAgent = str_replace('[', '&#91;', $row->userAgent);

            $rt = core_Type::getByName('richtext');
            $row->userAgent = $rt->toVerbal("[hide={$browser} / {$os}]{$row->userAgent}[/hide]");
        }

        $row->brid = str::coloring($row->brid);
    }
    

    /**
     * Връща уникален BRID
     * 1. Търси в сесията
     * 2. Търси в кукутата
     * 3. Ако няма открит BRID, и флага е вдигнат генерира и записва BRID
     * 
     * @param boolean $generate
     * 
     * @return string
     */
    static function getBrid($generate = TRUE)
    {   
        // brid от сесията
        $brid = Mode::get(static::BRID_NAME);
        
        if ($brid) return $brid;
        
        // brid от кукитата
        if ($bridC = $_COOKIE[static::BRID_NAME]) {
            
            // Допълнителна сол за brid
            $bridSalt = static::getBridSalt();
            
            // Проверяваме хеша дали е верене
            $brid = str::checkHash($bridC, static::HASH_LENGTH, $bridSalt);
            
            if ($brid) {
                
                // Записваме в сесията
                Mode::setPermanent(static::BRID_NAME, $brid);
                
                // Добавяме в модела
                static::add($brid);
                
                return $brid;
            } else {
                
                // Ако не отговаря на хеша
                
                static::log('Грешен хеш за BRID: ' . $bridC);
                
//                return FALSE;
            }
        }
        
        // Ако е зададено да се генерира brid
        if ($generate) {
            
            // Генерира brid
            $brid = static::generateBrid();
            
            // Записваме в сесията
            Mode::setPermanent(static::BRID_NAME, $brid);
            
            // Записваме кукито
            static::setBridCookie($brid);
            
            // Добавяме в модела
            static::add($brid);
            
            return $brid;
        }
    }
    
    
    /**
     * Добавя BRID, OS и браузъра в модела
     * 
     * @param string $brid
     */
    static function add($brid)
    {
        // Ако е бот, да не се добавя
        if (static::detectBot()) return ;
        
        $rec = new stdClass();
        $rec->brid = $brid;
        $rec->userAgent = static::getUserAgent();
         
        static::save($rec, NULL, REPLACE);
    }
    
    
    /**
     * Записва куки за brid с определено време на живот
     * 
     * @param string $brid
     * @param string $bridSalt
     */
    static function setBridCookie($brid)
    {
        $conf = core_Packs::getConfig('core');
        
        // Допълнителна сол за brid
        $bridSalt = static::getBridSalt();
        
        // Добавяме хеш към brid и записваме в кукитата
        $bridHash = str::addHash($brid, static::HASH_LENGTH, $bridSalt);
        setcookie(static::BRID_NAME, $bridHash, time() + $conf->CORE_COOKIE_LIFETIME);
    }
    
    
    /**
     * Обновява времето до когато ще е активен brid
     */
    static function updateBridCookieLifetime()
    {
        $brid = static::getBrid(FALSE);
        
        if (!$brid) return FALSE;
        
        static::setBridCookie($brid);
    }
    
    
    /**
     * Допълнителна сол за brid
     * 
     * @return string
     */
    static function getBridSalt_()
    {
        $os = static::getUserAgentOsName();
        $browser = static::getUserAgentBrowserName();
        $bridSalt = $os . '_' . $browser;
        
        return $bridSalt;
    }
    
    
    /**
     * Генерира brid
     * 
     * @return string
     */
    static function generateBrid_()
    {
        $brid = str::getRand();
        
        return $brid;
    }
    
    
    /**
     * Връща името на браузъра от HTTP_USER_AGENT
     * 
     * @return string
     */
    static function getUserAgentBrowserName($userAgent = NULL)
    {
        if(!$userAgent) {
            // Вземаме ОС от HTTP_USER_AGENT
            $userAgent = static::getUserAgent();
        }

        $browser = "Unknown Browser";
    
        $browserArray = array(  '/mobile/i' => 'Mobile Browser',
                                '/opera mobi/i' => 'Opera Mobi',
                                '/opera mini/i' => 'Opera Mini',
                                '/opera/i' => 'Opera',
                                '/msie|trident/i' => 'Internet Explorer',
                                '/firefox/i' => 'Firefox',
                                '/chrome/i' => 'Chrome',
                                '/safari/i' => 'Safari',
                                '/netscape/i' => 'Netscape',
                                '/maxthon/i' => 'Maxthon',
                                '/konqueror/i' => 'Konqueror',
                                
                            );
    
        foreach ($browserArray as $regex => $value) { 
            if (preg_match($regex, $userAgent)) {
                $browser = $value;
                break;
            }
        }
    
        return $browser;
    }
    
 
     
    /**
     * Връща името на ОС от HTTP_USER_AGENT
     * 
     * @return string
     */
    static function getUserAgentOsName($userAgent = NULL)
    {
        if(!$userAgent) {
            // Вземаме ОС от HTTP_USER_AGENT
            $userAgent = static::getUserAgent();
        }
        
        $osPlatform = "Unknown OS";
        
        $osArray = array(
                            '/iphone/i'             =>  'iPhone',
                            '/ipod/i'               =>  'iPod',
                            '/ipad/i'               =>  'iPad',
                            '/android/i'            =>  'Android',
                            '/blackberry/i'         =>  'BlackBerry',
                            '/webos/i'              =>  'Mobile',
                            '/windows nt 6.3/i'     =>  'Windows 8.1',
                            '/windows nt 6.2/i'     =>  'Windows 8',
                            '/windows nt 6.1/i'     =>  'Windows 7',
                            '/windows nt 6.0/i'     =>  'Windows Vista',
                            '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
                            '/windows nt 5.1/i'     =>  'Windows XP',
                            '/windows xp/i'         =>  'Windows XP',
                            '/windows nt 5.0/i'     =>  'Windows 2000',
                            '/windows me/i'         =>  'Windows ME',
                            '/win[a-z_ ]{0,6}98/i'  =>  'Windows 98',
                            '/win[a-z_ ]{0,6}95/i'  =>  'Windows 95',
                            '/win16/i'              =>  'Windows 3.11',
                            '/windows/i'            =>  'Windows',
                            '/macintosh|mac os x/i' =>  'Mac OS X',
                            '/mac_powerpc/i'        =>  'Mac OS 9',
                            '/ubuntu/i'             =>  'Ubuntu',
                            '/linux/i'              =>  'Linux',
                        );
        
        // Проверяваме регулярните изрази
        foreach ($osArray as $regex => $value) { 
    
            if (preg_match($regex, $userAgent)) {
                $osPlatform = $value;
                break;
            }
        }

        return $osPlatform;
    }
    

    function act_Test()
    {

        return self::getUserAgentOsName();
    }
    
    /**
     * Връща $_SERVER['HTTP_USER_AGENT']
     */
    static function getUserAgent()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        
        return $userAgent;
    }
    
    
    /**
     * Връща резултатата от get_browser();
     * Трябва да е зададен пътя до browscap.ini файла в php.ini
     */
    static function getBrowserCap()
    {
        static $browser='';
        
        if (!$browser) {
            
            // Ако функцията съществува и е зададен пътя до browscap.ini файла в php.ini
            if (function_exists('get_browser') && ini_get('browscap')) {
                $browser = get_browser();
            }
        }
 
        return $browser;
    }
    
    
    /**
     * Стандартния page_Footer извиква този екшън,
     * ако браузърът поддържа JS
     */
    function act_JS()
    {
        Mode::setPermanent('javascript', 'yes');
        Mode::setPermanent('screenWidth', Request::get('w', 'int'));
        Mode::setPermanent('screenHeight', Request::get('h', 'int'));
        Mode::setPermanent('windowWidth', $w = Request::get('winW', 'int'));
        Mode::setPermanent('windowHeight', Request::get('winH', 'int'));
        Mode::setPermanent('checkNativeSupport', Request::get('scroll', 'int'));
        Mode::setPermanent('getUserAgent', Request::get('browserCheck'));
   
           
        if($w > 1000 && !Mode::is("ScreenModeFromScreenSize")) {
            Mode::setPermanent('screenMode', 'wide');
            Mode::setPermanent("ScreenModeFromScreenSize");
        }

        $this->render1x1gif();

        die;
    }
    
    
    /**
     * Стандартния page_Footer извиква този екшън,
     * ако браузърът не поддържа JS
     */
    function act_NoJS()
    {
        Mode::setPermanent('javascript', 'no');

        $this->render1x1gif();
        
        die;
    }
    
    
    /**
     * Предизвиква затваряне на браузъра
     */
    function act_Close()
    {
        return "<script> opener.focus(); self.close (); </script>";
    }
    
    
    /**
     * Задава широк режим на екрана
     */
    function act_SetWideScreen()
    {
        Mode::setPermanent('screenMode', 'wide');

        followRetUrl();
    }
    
    
    /**
     * Задава тесен режим на екрана
     */
    function act_SetNarrowScreen()
    {
        Mode::setPermanent('screenMode', 'narrow');

        followRetUrl();
    }
    
    
    /**
     * Връща HTML кода за разпознаване параметрите на браузъра
     * В частност се разпознава дали браузърът поддържа Javascript
     */
    function renderBrowserDetectingCode_()
    {
        $code = '';

        if(!self::detectBot()) {
            if (!Mode::is('javascript', 'no')) {
                $url = toUrl(array(
                        $this,
                        'noJs',
                        rand(1, 1000000000)
                    ));
                $code .= '<noscript><span class="checkBrowser"><img src="' . $url . '" width="1" height="1" alt="cb"></span></noscript>';
            }
            
            if (!Mode::is('javascript', 'yes')) {
                $url = toUrl(array(
                        $this,
                        'js',
                        rand(1, 1000000000)
                    ));
                $code .= '<span class="checkBrowser"><img id="brdet" src="" width="1" height="1"></span><script src=' . sbf("js/overthrow-detect.js") . ' type="text/javascript"></script><script type="text/javascript"><!-- 
                var winW = 630, winH = 460; if (document.body && document.body.offsetWidth) { winW = document.body.offsetWidth;
                winH = document.body.offsetHeight; } if (document.compatMode=="CSS1Compat" && document.documentElement && 
                document.documentElement.offsetWidth ) { winW = document.documentElement.offsetWidth;
                winH = document.documentElement.offsetHeight; } if (window.innerWidth && window.innerHeight) {
                winW = window.innerWidth; winH = window.innerHeight;}  var brdet=document.getElementById("brdet"); 
                brdet.src="' . $url . '?w=" + screen.width + "&h=" + screen.height + "&winH=" + winH + "&winW=" + winW + "&scroll=" + checkNativeSupport() + "&browserCheck=" + getUserAgent();
                //--> </script>';
            }
        }
        
        return $code;
    }
    
    
    /**
     * Изпраща към клиента едно пикселен gif
     */
    function render1x1gif()
    {   
        header("X-Robots-Tag: noindex", TRUE);
        header("Content-Type:  image/gif");
        header("Expires: Wed, 11 Nov 1998 11:11:11 GMT");
        header("Cache-Control: no-cache");
        header("Cache-Control: must-revalidate");
        
        // Отпечатва бинарен код, със съдържание едно пикселен gif
        printf("%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c",
            71, 73, 70, 56, 57, 97, 1, 0, 1, 0, 128, 255, 0, 192, 192, 192, 0, 0, 0, 33, 249, 4, 1,
            0, 0, 0, 0, 44, 0, 0, 0, 0, 1, 0, 1, 0, 0, 2, 2, 68, 1, 0, 59);
    }
    
    
    /**
     * Проверява дали браузъра е мобилен
     * Базирано на: http://detectmobilebrowsers.com/
     */
    static function detectMobile()
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        
        if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))) {
            
            return TRUE;
        }
    }
 

    /**
     * Намира името на бота, ако той е клиента
     */
    static function detectBot($userAgent = NULL)
    {
        setIfNot($userAgent, $_SERVER['HTTP_USER_AGENT']);

        $bots = 'Google|GoogleBot|Googlebot|msnbot|Bingbot|Teoma|80legs|xenon|baidu|Charlotte|DotBot|Sosospider|Rambler|Yahoo|' .
            'AbachoBOT|Acoon|appie|Fluffy|ia_archiver|MantraAgent|Openbot|accoona|AcioRobot|ASPSeek|CocoCrawler|Dumbot|' . 
            'FAST-WebCrawler|GeonaBot|Gigabot|Lycos|MSRBOT|Scooter|AltaVista|IDBot|eStyle|Scrubby';

        $crawlers = explode("|", $bots);
 
        foreach ($crawlers as $botName)
        {
            if (stristr($userAgent, $botName) !== FALSE) {
            
                return $botName;
            }
        }
     
        return FALSE;
    }
    
    
    /**
     * 
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, &$data)
    {
        // В хоризонтален вид
        $data->listFilter->view = 'horizontal';
        
        // Добавяме бутон
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Кои полета да се показват
        $data->listFilter->showFields = 'brid';
        
        // Инпутваме заявката
        $data->listFilter->input('brid', 'silent');
        
        // Ако има филтър
        if($filter = $data->listFilter->rec) {
            if ($filter->brid) {
                $data->query->where(array("#brid = '[#1#]'", $filter->brid));
            }
        }
        
        // Сортиране на записите по създаване
        $data->query->orderBy('createdOn', 'DESC');
    }
}