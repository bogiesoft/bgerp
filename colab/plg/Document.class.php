<?php



/**
 * 
 * 
 * @category  bgerp
 * @package   colab
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class colab_plg_Document extends core_Plugin
{
    
    
    /**
     * След пдоготовка на сингъла
     */
    public static  function on_AfterPrepareSingle($mvc, $res, $data)
    {
        // Ако е контрактор, маркираме документа като видян
        if (core_Users::haveRole('partner')) {
            colab_DocumentLog::markAsViewed($data->rec->containerId);
        }
    }
    
    
    /**
     * След подготовка на вербалното показване
     */
    public static function on_AfterRecToVerbal(&$invoker, &$row, &$rec, $fields = array())
    {
        if ($fields && $fields['-single']) {
            
            if (!Mode::is('text', 'xhtml') && !Mode::is('printing') && core_Users::isPowerUser() && colab_FolderToPartners::fetch("#folderId = '{$rec->folderId}'")) {
                
                $isVisible = FALSE;
                if ($rec->containerId) {
                    $cRec = doc_Containers::fetch($rec->containerId);
                    if ($cRec->visibleForPartners == 'yes') {
                        $isVisible = TRUE;
                    }
                } else {
                    if ($invoker->isVisibleForPartners($rec)) {
                        $isVisible = TRUE;
                    }
                }
                
                if ($isVisible) {
                    
                    // Може и да се провери стойноста на `visibleForPartners` в `doc_Containers`
                    
                    $link = colab_DocumentLog::renderViewedLink($rec->containerId);
                    
                    $row->DocumentSettings = new ET($row->DocumentSettings);
                    
                    $row->DocumentSettings->append($link);
                    
                    jquery_Jquery::runAfterAjax($row->DocumentSettings, 'showTooltip');
                }
            }
        }
    }
}
