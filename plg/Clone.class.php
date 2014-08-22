<?php


/**
 * Клас 'plg_Clone' - Плъгин за клониране в листов и сингъл излгед
 *
 * @category  ef
 * @package   plg
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class plg_Clone extends core_Plugin
{
    
    
	/**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$invoker)
    {
        // Правата по подразбиране за екшъните
        setIfNot($invoker->canClonesysdata, 'admin, ceo');
        setIfNot($invoker->canCloneuserdata, 'user');
        setIfNot($invoker->canClonerec, 'user');
    }
    
    
    /**
     * Преди да се изпълни екшъна
     */
    function on_BeforeAction($mvc, &$res, $action)
    { 
        // Ако ще клонираме полетата
        if ($action != 'clonefields') return ;
        
        // id на записа, който ще клонираме
        $id = Request::get('id', 'int');
        
        // Вземаме записа
        $rec = $mvc->fetch($id);
        
        // Очакваме да има такъв запис
        expect($rec);
        
        // Права за работа с екшън-а
        $mvc->requireRightFor('clonerec', $rec);
        
        // Вземаме формата към този модел
        $form = $mvc->getForm();
        
        // Вземаме всички полета, които ще се инпутват
        $fields = $form->selectFields("#input == 'input' || #input ==''");
        
        // Масив с всички полета, които ще се записват
        $saveRecF = array();
        
        // Вземаме всички полета, които ще се клонират
        $mvc->invoke('getCloneFields', array(&$saveRecF));
        
        // Ако не са зададени
        if (!$saveRecF) {
            
            // Използваме всички видими полета
            $saveRecF = $fields;
        }
        
        // Обхождаме масива с полетата, които ще се клонират
        foreach((array)$saveRecF as $field => $dummy) {
            
            // Попълваме формата със стойностите от записа
            $form->rec->{$field} = $rec->$field;
//            $form->setDefault($field, $rec->$field);
        }
        
        // Въвеждаме полетата
        $form->input($fields);
        
        // Въвеждаме SILENT полетата
        $form->input(FALSE, TRUE);
        
        // Ако формата е изпратена без грешки
        if($form->isSubmitted()) {
            
            // Обекта, който ще се запишем
            $nRec = new stdClass();
            
            // Масив с всички данни, които ще записваме в
            $allSaveRecF = array_merge($saveRecF, $fields);
            
            // Обхождаме масива с полетата, които ще се добавят
            foreach ((array)$allSaveRecF as $field => $dummy) {
                
                // Ако е 'id', да не се добавя
                // За всеки случай
                if ($field == 'id') continue;
                
                // Добавяме към записа
                $nRec->$field = $form->rec->$field;
            }
            
            // Инвокваме фунцкцията, ако някой иска да променя нещо
            $mvc->invoke('BeforeSaveCloneRec', array($rec, &$nRec));
            
            // Ако няма проблем пи записа
            if ($mvc->save($nRec)) {
                
                // Ако е инстанция на core_Master
                if ($mvc instanceof core_Master) {
                    
                    // Редиректваме към сингъла
                    $redirectUrl = array($mvc, 'single', $nRec->id);
                } else {
                    
                    // Редиректваме към листовия излгед
                    $redirectUrl = array($mvc, 'list');
                }
                
                // За да се редиректне към съответната страница
                $res = new Redirect($redirectUrl);
                
                return FALSE;
            } else {
                
                // Показваме съобщение за грешка
                core_Statuses::newStatus(tr('Грешка при клониране на запис'), 'warning');
            }
        }
        
        // URL за бутона отказ
        $retUrl = getRetUrl();
        
        // Ако не зададено
        if (!$retUrl) {
            
            // Ако има сингъл
            if ($mvc instanceof core_Master) {
                $retUrl = array($mvc, 'single', $rec->id);
            } else {
                $retUrl = array($mvc, 'list');
            }
        }
        
        // Задаваме да се показват всички полета, които могат да се вкарат
        $form->showFields = $fields;
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close16.png');
        
        // Добавяме титлата на формата
        $form->title = 'Клониране на запис в|* "' . $mvc->getTitle() . '"';
        
        // Рендираме опаковката
        $res = $mvc->renderWrapping($form->renderHtml());
        
        return FALSE;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        // Ако има запис и има права
        if ($rec && $requiredRoles != 'no_one') {
        
            // Това също се проверява и в plg_Created, но там се изисква canEditsysdata и canDeletesysdata
            // Ако записа е на системен потребител
            if ($rec->createdBy == plg_Created::EF_SYS_USER_ID) {
                
                // Ако ще изтриваме или редактираме група
                if ($action == 'delete' || $action == 'edit') {
                    
                    // Да не можем да редактираме
                    $requiredRoles = 'no_one';
                }
            }
            
            // Ако ще се клонира
            if ($action == 'clonerec') {
                
                // Ако е създаден от системния потребител
                if ($rec->createdBy == plg_Created::EF_SYS_USER_ID) {
                    
                    // Проверява се дали има права да клонира системните данни
                    if (!$mvc->haveRightFor('clonesysdata', $rec)) {
                        $requiredRoles = 'no_one';
                    }
                } else {
                    
                    // Ако е създаден от потребител
                    
                    // Проверява се дали има права за клониране на данните
                    if (!$mvc->haveRightFor('cloneuserdata', $rec)) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
//            
//            // Ако ще се клонират данни на потребителя
//            if ($action == 'cloneuserdata') {
//                
//                // Трябва да има права за добавяне за да може да клонира
//                if (!$mvc->haveRightFor('add', $rec)) {
//                    $requiredRoles = 'no_one';
//                }
//            }
        }
    }
    
    
    /**
     * Изпълнява се след преобразуването към вербални стойности на полетата на записа
     */
    function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        // Ако се намираме в режим "печат", не показваме инструментите на реда
        if(Mode::is('printing')) return;
        
        // Ако листваме
        if(!arr::haveSection($fields, '-list')) return;
        
        // Ако нямаме права за клониране, да не се показва линка
        if (!$mvc->haveRightFor('clonerec', $rec)) return ;
        
        // Определяме в кое поле ще показваме инструментите
        $field = $mvc->rowToolsField ? $mvc->rowToolsField : 'id';
        
        // Съдържанието на полето
        $rowField = $row->$field;
        
        // Ако полето не обект
        if (!is_object($rowField)) {
            
            // Създаваме обекта
            $row->$field = new ET();
            
            // Добавяме номера на реда
            $row->$field->prepend($rowField, 'ROWTOOLS_CAPTION');
        }
        
        // Добавяме линк, който води до промяна на записа
        $row->$field->prepend($mvc->getCloneLink($rec), 'TOOLS');
    }
    
    
    /**
     * Връща линк за клониране
     * 
     * @param core_Mvc $mvc
     * @param core_ET $res
     * @param object $rec
     */
    function on_AfterGetCloneLink($mvc, &$res, $rec)
    {
        // URL' то за клониране
        $cloneUrl = array($mvc, 'cloneFields', $rec->id, 'ret_url' => TRUE);
        
        // Иконата за промяна
        $cloneSbf = sbf("img/16/clone.png");
        
        // Ако не е подадено заглавиет, създаваме линк с иконата
        $res = ht::createLink('<img src=' . $cloneSbf . ' width="16" height="16">', $cloneUrl);
    }
    
    
    /**
     * След подготвяне на сингъл тулбара
     * 
     * @param core_Mvc $mvc
     * @param object $data
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
    {   
        // Ако имаме права за клониране, да се показва бутона
        if ($mvc->haveRightFor('clonerec', $data->rec)) {
            
            // Добавяме бутон за клониране в сингъл изгледа
            $data->toolbar->addBtn('Клониране', array($mvc, 'cloneFields', $data->rec->id, 'ret_url' => array($mvc, 'single', $data->rec->id)), 'ef_icon=img/16/clone.png,title=Клониране,row=2, order=40');
        }
    }
}
