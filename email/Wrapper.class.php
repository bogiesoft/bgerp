<?php



/**
 * Имейли - опаковка
 *
 *
 * @category  bgerp
 * @package   email
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class email_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на опаковката
     */
    function description()
    {
        //Показва таба за постинги, само ако имаме права за листване
        $this->TAB('email_Outgoings', 'Изходящи', 'ceo, admin, user');
        
        // Ако имаме права ceo
        // Да е първия таб
        if (haveRole('ceo')) {
            $this->TAB('email_Incomings', 'Входящи->Съобщения', 'ceo');
        } 
        if (haveRole('admin,email')) {
            $this->TAB('email_Returned', 'Входящи->Върнати', 'admin,email');
            $this->TAB('email_Receipts', 'Входящи->Разписки', 'admin,email');
            $this->TAB('email_Spam', 'Входящи->Спам', 'admin,email');
            $this->TAB('email_Unparsable', 'Входящи->Непарсируеми', 'admin,email');
            $this->TAB('email_Fingerprints', 'Входящи->Отпечатъци', 'admin,email');
        }
        
        $this->TAB('email_Inboxes', 'Кутии', 'ceo, admin, user');
        $this->TAB('email_Accounts', 'Сметки', 'admin');
        $this->TAB('email_Filters', 'Рутиране', 'admin, debug');
        $this->TAB('email_Salutations', 'Обръщения', 'debug');
        $this->TAB('email_ThreadHandles', 'Манипулатори', 'debug');

        $this->title = 'Имейли';
    }
}
