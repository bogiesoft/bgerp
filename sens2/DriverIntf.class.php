<?php

/**
 * Интерфейс за комуникация с входно-изходен хардуерен контролер
 *
 *
 * @category  bgerp
 * @package   sens2
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс на драйвер на I/O контролер
 */
class sens2_DriverIntf
{

    /**
     *  Информация за входните портове на устройството
     *
     * @return  array   Масив с ключове - системните имена на входовете и стойности - обекти със следното описание:
     *                      о ->uom      препоръчителна мярка или списък с мерки за дадената физ. величина
     *                      о ->caption  заглавие на входната величнина
     */
    function getInputPorts()
    {
        return $this->class->getInputPorts();
    }

    
    /**
     * Информация за изходните портове на устройството
     *
     * @return  array   Mасив с ключове - системните имена на изходите и стойности - обекти със следното описание:
     *                      о ->uom      препоръчителна мярка или списък с мерки за дадената физ. величина
     *                      о ->caption  заглавие на входната величнина
     */
    function getOutputPorts()
    {
        return $this->class->getOutputPorts();
    }


    /**
     * Прочита стойностите от сензорните входове
     *
     * @param   array   $inputs             масив със системните имена на входовете, които трябва да бъдат прочетени
     * @param   array   $config             конфигурациони параметри
     * @param   array   $persistentState    персистентно състояние, от базата данни
     * 
     * @return  mixed                       Mасив със системните имена на входовете и стойностите, които са се 
     *                                      получили при прочитането им. В случайн на грешка, стойността започва с #, 
     *                                      последван от текстово описание на проблема
     */
    function readInputs($inputs, $config, &$persistentState)
    {
        return $this->class->readInputs($inputs, $config, $persistentState);
    }


    /**
     * Записва стойностите на изходите на контролера
     *
     * @param   array   $outputs            масив със системните имена на изходите и стойностите, които трябва да бъдат записани
     * @param   array   $config             конфигурациони параметри
     * @param   array   $persistentState    персистентно състояние на контролера от базата данни
     *
     * @return  array                       Mасив със системните имена на изходите и статус (TRUE/FALSE) на операцията с него
     */
    function writeOutputs($outputs, $config, &$persistentState)
    {
         return $this->class->writeOutputs($outputs, $config, $persistentState);
    }
    

    /**
     * Подготвя форма с настройки на контролера, като добавя полета с $form->FLD(....)
     *
     * @param core_Form форма на която трябва да се поставят полетата с конфигурацията на контролера (IP, port, pass, ...)
     */
    function prepareConfigForm($form)
    {
        return $this->class->prepareConfigForm($form);
    }
   
    
    /**
     * Проверява след  субмитване формата с настройки на контролера
     * Тук контролера може да зададе грешки и предупреждения, в случай на 
     * некоректни конфигурационни данни използвайки $form->setError() и $form->setWarning()
     *
     * @param   core_Form   форма с въведени данни от заявката (след $form->input)
     */
    function checkConfigForm($form)
    {
        return $this->class->checkConfigForm($form);
    }
}