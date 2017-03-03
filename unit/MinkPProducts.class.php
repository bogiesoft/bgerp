<?php


/**
 *  Клас  'unit_MinkPProducts' - PHP тестове за артикули
 *
 * @category  bgerp
 * @package   tests
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class unit_MinkPProducts extends core_Manager {
     
    /**
     * Стартира последователно тестовете от MinkPProducts 
     */
    //http://localhost/unit_MinkPProducts/Run/
    public function act_Run()
    {
        if (!TEST_MODE) {
            return;
        }
        
        $res = '';
        $res .= "<br>".'MinkPProducts';
        $res .= "  1.".$this->act_EditProduct();
        $res .= "  2.".$this->act_AddProductPrice();
        $res .= "  3.".$this->act_CreateProductBom();
        $res .= "  4.".$this->act_CreateBom();
        $res .= "  5.".$this->act_CreatePlanningJob();
        $res .= "  6.".$this->act_CreateCloning();
        $res .= "  7.".$this->act_CreateTemplate();
        return $res;
    }
    
    /**
     * Логване
     */
    public function SetUp()
    {
        $browser = cls::get('unit_Browser');
        //$browser->start('http://localhost/');
        $host = unit_Setup::get('DEFAULT_HOST');
        $browser->start($host);
        //Потребител DEFAULT_USER (bgerp)
        $browser->click('Вход');
        $browser->setValue('nick', unit_Setup::get('DEFAULT_USER'));
        $browser->setValue('pass', unit_Setup::get('DEFAULT_USER_PASS'));
        $browser->press('Вход');
        return $browser;
    }
  
    /**
     * 1. Редакция на артикул
     */
    //http://localhost/unit_MinkPProducts/EditProduct/
    function act_EditProduct()
    {
        // Логване
        $browser = $this->SetUp();
    
        $browser->click('Каталог');
        // търсене
        $browser->setValue('search', 'Чувал');
        $browser->press('Филтрирай');
        //$browser->click('Продукти');
        $browser->click('Чувал голям 50 L');
        $browser->press('Редакция');
        $browser->setValue('info', 'прозрачен');
        $browser->setValue('Ценова група » 0', 12);
        $browser->press('Запис');
       
        //return $browser->getHtml();
    }
    
    /**
     * 2. Добавяне ценова група на артикул, опаковка/мярка, лимит, себестойност и влагане 
     */
    //http://localhost/unit_MinkPProducts/AddProductPrice/
    function act_AddProductPrice()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Избиране на артикул
        $browser->click('Каталог');
        $browser->click('Продукти');
        $Item = "Чувал голям 50 L";
    
        if(strpos($browser->gettext(), $Item)) {
            $browser->click($Item);
            
            //Добавяне на група
            $browser->click('Промяна на групите на артикула');
            $browser->setValue('Ценова група » A', 13);
            $browser->press('Промяна');
            
            //Добавяне на нова опаковка/мярка
            $browser->click('Добавяне на нова опаковка/мярка');
            $browser->setValue('packagingId', 'стек');
            $browser->setValue('quantity', '100');
            $browser->setValue('netWeight[lP]', '0.015');
            $browser->setValue('tareWeight[lP]', '0.002');
            $browser->setValue('sizeWidth[lP]', '0.27');
            $browser->setValue('sizeHeight[lP]', '0.10');
            $browser->setValue('sizeDepth[lP]', '0.51');
            $browser->setValue('eanCode', '1234567893341');
            $browser->press('Запис');
            
            //Добавяне на лимит
            $browser->click('Добавяне на ново ограничение на перото');
            $browser->setValue('accountId', '321. Суровини, материали, продукция, стоки');
            //$browser->refresh('Запис');
            $browser->press('Refresh');
            $browser->setValue('limitDuration', '1 год.');
            $browser->setValue('limitQuantity', '100');
            $browser->setValue('item1', 'Склад 1 (1 st)');
            $browser->setValue('Bgerp', True);
            $browser->press('Запис');
            
            //Добавяне на себестойност
            $browser->click('Цени');
            $browser->click('Добавяне на нова мениджърска себестойност');
            //$browser->refresh('Запис');
            $browser->setValue('price', '0,024');
            $browser->press('Запис');
            
            //Влагане
            $browser->click('Влагане');
            $browser->press('Добави');
            //Добавяне на заместващ артикул към
            $browser->setValue('likeProductId', 'Други продукти (products)');
            $browser->press('Запис');
            
        } else {
            return unit_MinkPbgERP::reportErr('Няма такъв артикул', 'info');
        }
        //return $browser->getHtml();
    }
    
    /**
     * 3. Създаване на артикул - продукт през папката. Добавяне на рецепта.
     */
    //http://localhost/unit_MinkPProducts/CreateProductBom/
    function act_CreateProductBom()
    {
        // Логване
        $browser = $this->SetUp();
         
        // Създаване на нов артикул - продукт
        $browser->click('Каталог');
        $browser->press('Нов запис');
        $browser->setValue('catcategorieId', 'Продукти');
        $browser->press('Напред');
        $browser->setValue('name', 'Плик 7 л');
        $browser->setValue('code', 'plik7');
        $browser->setValue('measureId', 'брой');
        $browser->setValue('Ценова група » Промоция', 15);
        $browser->press('Запис');
        if (strpos($browser->getText(),"Вече съществува запис със същите данни")){
            $browser->press('Отказ');
            $browser->click('Плик 7 л');
        }
        
        // Добавяне рецепта
        $browser->click('Рецепти');
        $browser->click('Добавяне на нова търговска технологична рецепта');
        //$browser->hasText('Добавяне на търговска рецепта към');
        $browser->setValue('expenses', '3');
        $browser->setValue('quantityForPrice', '100');
        $browser->press('Чернова');
        $browser->press('Влагане');
        $browser->setValue('resourceId', 'Друг труд');
        $browser->setValue('propQuantity', '6');
        $browser->refresh('Запис');
        // refresh('Запис') е нужен, когато мярката не излиза като отделно поле, напр. на труд, услуги
        $browser->press('Запис и Нов');
        $browser->setValue('resourceId', 'Други суровини и материали');
        $browser->setValue('propQuantity', '1 + $Начално= 10');
        $browser->refresh('Запис');
        $browser->press('Запис');
        $browser->press('Активиране');
        //return $browser->getHtml();
        
        //Добавяне на опаковка - 
    }
    
    /**
     * 4. Създаване на рецепта
     */
    //http://localhost/unit_MinkPProducts/CreateBom/
    function act_CreateBom()
    {
        // Логване
        $browser = $this->SetUp();
         
        $browser->click('Каталог');
        $browser->click('Продукти');
        $browser->click('Чувал голям 50 L');
        $browser->click('Рецепти');
        $browser->click('Добавяне на нова търговска технологична рецепта');
        //$browser->hasText('Добавяне на търговска рецепта към');
        $browser->setValue('notes', 'CreateBom');
        $browser->setValue('expenses', '8');
        $browser->setValue('quantityForPrice', '100');
        $browser->press('Чернова');
        $browser->press('Влагане');
        $browser->setValue('resourceId', 'Други суровини и материали');
        $browser->setValue('propQuantity', '1,6');
        $browser->refresh('Запис');
        // refresh('Запис') е нужен, когато мярката не излиза като отделно поле, напр. на труд, услуги
        $browser->press('Запис и Нов');
        //$browser->setValue('resourceId', 'Други консумативи');
        $browser->setValue('resourceId', 'Други заготовки');
        $browser->setValue('propQuantity', '1,2634');
        $browser->refresh('Запис');
        // refresh('Запис') е нужен, когато мярката не излиза като отделно поле, напр. на труд, услуги
        $browser->press('Запис и Нов');
        $browser->setValue('resourceId', 'Друг труд');
        $browser->setValue('propQuantity', '1 + $Начално= 10');
        $browser->refresh('Запис');
        $browser->press('Запис');
        $browser->press('Активиране');
        //return $browser->getHtml();
    }
    
    /**
     * 5. Създава задание за производство
     * (Ако има предишно задание, трябва да се приключи)
     */
    //http://localhost/unit_MinkPProducts/CreatePlanningJob/
    function act_CreatePlanningJob()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Избиране на артикул
        $browser->click('Каталог');
        $browser->click('Продукти');
        $Item = "Чувал голям 50 L";
    
        if(strpos($browser->gettext(), $Item)) {
            $browser->click($Item);
            //Добавяне на задание
            $browser->click('Задания');
            //Проверка дали може да се добави - не работи
            //if(strpos($browser->gettext(), 'Добавяне на ново задание за производство')) {
            $browser->click('Добавяне на ново задание за производство');
            $valior=strtotime("+1 Day");
            $browser->setValue('dueDate', date('d-m-Y', $valior));
            $browser->setValue('quantity', '1000');
            $browser->setValue('notes', 'CreatePlanningJob');
    
            $browser->press('Чернова');
            $browser->press('Активиране');
            //Добавяне на задача
            $browser->click('Добавяне на нова задача за производство');
            $browser->setValue('hrdepartmentId', 'Производство');
            $browser->press('Напред');
            $browser->setValue('storeId', 'Склад 1');
            $browser->press('Чернова');
            $browser->press('Активиране');
            //Произвеждане и влагане
            $browser->press('Произвеждане'); 
            //$browser->press('Добавяне на произведен артикул');
            $browser->setValue('quantity', '1000');
            $browser->setValue('employees[4]', '4');
            $browser->press('Запис');
            $browser->press('Влагане');
            $browser->setValue('taskProductId', 'Други суровини и материали');
            $browser->setValue('quantity', '1600');
            $browser->press('Запис и Нов');
            $browser->setValue('taskProductId', 'Други заготовки');
            $browser->setValue('quantity', '1263,4');
            $browser->press('Запис и Нов');
            $browser->setValue('taskProductId', 'Друг труд');
            $browser->setValue('quantity', '1010');
            $browser->press('Запис');
            // Приключване на задачата - когато са в една нишка, разпознава бутона за приключване на заданието, защото са с еднакви имена
            $browser->press('Приключване');
            //Протокол за производство - в заданието
            $browser->click('Задание за производство №');
            
            //$browser->press('Създаване на протокол за производство от заданието');
            $browser->press('Произвеждане');
            $browser->setValue('storeId', 'Склад 1');
            $browser->setValue('note', 'Test');
            $browser->press('Чернова');
            $browser->press('Контиране');
            $browser->press('Приключване');
        } else {
        return unit_MinkPbgERP::reportErr('Няма такъв артикул', 'info');
        }
        //return $browser->getHtml();
    }
 
    /**
     * 6. Клониране на артикул
     */
    //http://localhost/unit_MinkPProducts/CreateCloning/
    function act_CreateCloning()
    {
        // Логване
        $browser = $this->SetUp();
    
        $browser->click('Каталог');
        // търсене
        $browser->setValue('search', 'Чувал');
        $browser->press('Филтрирай');
        //$browser->click('Продукти');
        $browser->click('Чувал голям 50 L');
        $browser->press('Клониране');
        $browser->setValue('code', 'smet40');
        $browser->setValue('name', 'Чувал голям 40 L');
        $browser->setValue('paramcat1', '40');
        $browser->press('Запис');
        if(strpos($browser->gettext(), 'Чувал голям 40 L')) {
        } else {
            return unit_MinkPbgERP::reportErr('Неуспешно клониране', 'warning');
        } 
        //return $browser->getHtml();
    }
   
    /**
     * 7. Създаване на шаблон и артикул от него
     */
    //http://localhost/unit_MinkPProducts/CreateTemplate/
    function act_CreateTemplate()
    {
        // Логване
        $browser = $this->SetUp();
         
        // Създаване на нов артикул - шаблон
        $browser->click('Каталог');
        $browser->press('Нов запис');
        $browser->setValue('catcategorieId', 'Шаблони');
        $browser->press('Напред');
        $browser->setValue('name', 'Артикул - шаблон');
        $browser->setValue('code', 'template');
        $browser->setValue('measureId', 'брой');
        $browser->setValue('info', 'шаблон');
        $browser->setValue('Ценова група » 0', 12);
        $browser->press('Запис');
        
        // Създаване на нов артикул от шаблона
        $browser->click('Каталог');
        $browser->press('Нов запис');
        $browser->setValue('catcategorieId', 'Продукти');
        $browser->press('Напред');
        $browser->setValue('proto', 'Артикул - шаблон');
        $browser->setValue('name', 'Артикул от шаблон');
        $browser->setValue('code', 'fromtemplate');
        $browser->setValue('measureId', 'брой');
        $browser->setValue('info', 'от шаблон');
        $browser->press('Запис');
        //return $browser->getHtml();
    }
    
}