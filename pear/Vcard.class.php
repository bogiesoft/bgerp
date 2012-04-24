<?php

require "File/IMC.php";

ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . __DIR__);

class pear_Vcard
{

    protected static $partMaps = array(

        'ADR' => array(
            'pobox'    => 0,
            'ext'      => 1,
            'street'   => 2,
            'locality' => 3,
            'region'   => 4,
            'code'     => 5,
            'country'  => 6,
        ),

        'N' => array(
            'surname'    => 0,
            'given'      => 1,
            'additional' => 2,
            'prefix'     => 3,
            'suffix'     => 4,
        ),

    );


    protected static $typesMap = array(

        'TEL' => array(
            'home',
            'msg',
            'work',
            'pref',
            'voice',
            'fax',
            'cell',
            'video',
            'pager',
            'bbs',
            'modem',
            'car',
            'isdn',
            'pcs',
        ),

        'ADR' => array(
            'dom',
            'intl',
            'postal',
            'parcel',
            'home',
            'work',
            'pref',
        ),

        'LABEL' => array(
            'dom',
            'intl',
            'postal',
            'parcel',
            'home',
            'work',
            'pref',
        ),

    );


    /**
     * Една парсирана до масив визитна картичка
     *
     * @var array
     */
    protected $data = array();


    /**
     * Версията на vCard формата - 2.1, 3.0, 4.0
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->data['VERSION'][0]['value'][0][0];
    }


    /**
     * Версия на визитката
     *
     * @return int UNIX TIMESTAMP
     */
    public function getRevision()
    {
        $rev = $this->getScalarProp('REV');

        if ($rev) {
            $rev = static::toTimestamp($rev[0][0]);
        }

        return $rev;
    }


    /**
     * Форматиран текст отговарящ на името на лицето
     *
     * @return string
     */
    public function getFormattedName()
    {
        $fn = $this->getScalarProp('FN');

        if ($fn) {
            $fn = $fn[0][0];
        }

        return $fn;
    }


    /**
     * Структурирано име на лицето
     *
     * @param string $part 'surname' | 'given' | 'additional' | 'prefix' | 'suffix' | NULL
     *                 Ако е NULL връща всички полета на структурата
     * @return string|array Ако $part е NULL - масив от всички полета; иначе стринг със
     *                         стойността на полето $part
     */
    public function getName($part = NULL)
    {
        $name = $this->getStructProp('N', $part);

        if ($name) {
            $name = $name[0][0]; // Допуска се само едно име (N) и не се допускат типове
        }

        return $name;
    }


    /**
     * URL-и на снимки на лицето, съдържащи се във визитката
     *
     * @return array
     */
    public function getPhotoUrl()
    {
        $urls = array();

        if (isset($this->data['PHOTO'])) {
            foreach ($this->data['PHOTO'] as $photo) {
                if (isset($photo['param']['VALUE']) && $photo['param']['VALUE'][0] == 'URL') {
                    $urls = array_merge($urls, $photo['value'][0]);
                }
            }
        }

        return $urls;
    }


    /**
     * Дата на раждане
     *
     * @return int UNIX TIMESTAMP
     */
    public function getBday()
    {
        $bday = $this->getScalarProp('BDAY');

        if ($bday) {
            $bday = $bday[0][0];
            if (substr($bday, -1, 1) == 'Z') {
                $bday = substr($bday, 0, -1);
            }

            $bday = strtotime($bday);
        } else {
            $bday = NULL;
        }

        return $bday;
    }


    /**
     * Телефоните на лицето групирани по тип
     *
     * @param array|string $types списък от типове телефони, които се търсят.
     *                             Възможните стойности за тип са 'home', 'msg', 'work', 'pref',
     *                             'voice', 'fax', 'cell', 'video', 'pager', 'bbs', 'modem',
     *                             'car', 'isdn', 'pcs'
     *                             Ако е NULL - метода връща телефоните от всички типове налични
     *                             във визитката.
     * @link http://tools.ietf.org/html/rfc2426#section-3.3.1
     * @return array ако е зададен точно един тип, масива съдържа всички налични телефони от
     *                 този тип; в противен случай - масив с ключ тип и стойност - масив от
     *                 телефони от този тип.
     */
    public function getTel($types = NULL)
    {
        return $this->getScalarProp('TEL', $types);
    }


    /**
     * Имейлите на лицето групирани по типове
     *
     * Имейлите, за които не е посочен тип във визитката влизат в масива който е с ключ 0 в резултата
     *
     * @return array
     */
    public function getEmails()
    {
        $emails = $this->getScalarProp('EMAIL');

        if (!$emails) {
            $emails = array();
        }

        return $emails;
    }


    /**
     * Части от или целите структурирани адреси, групирани по тип
     *
     * @param string $part - коя част от структурата? Възможностите са
     *                         'pobox' | 'ext' | 'street' | 'locality' | 'region' | 'code' |
     *                         'country' | NULL
     * @param array|string $types
     * @return Ambigous <NULL, multitype:, mixed>
     */
    public function getAddress($part = NULL, $types = NULL)
    {
        return $this->getStructProp('ADR', $part, $types);
    }


    /**
     * Адресите на лицето в свободен текст, групирани по тип
     *
     * @param array|string $types
     * @return array
     */
    public function getAddressLabel($types = NULL)
    {
        return $this->getScalarProp('LABEL', $types);
    }


    /**
     * Организацията (фирмата) на лицето.
     *
     * @param boolean $bFull
     * @return string|array ако $bFull == FALSE - връща стринг с името на организацията;
     *                      ако $bFull == TRUE  - връща масив с името и евентуални подразделения
     */
    public function getOrganisation($bFull = FALSE)
    {
        $org = $this->getStructProp('ORG');

        if ($org) {
            $org = $org[0][0];
            if (!$bFull) {
                $org = $org[0];
            }
        }

        return $org;
    }


    /**
     * Позиция на лицето в организацията
     *
     * @link http://tools.ietf.org/html/rfc2426#section-3.5.1
     * @return string
     */
    public function getJobTitle()
    {
        $title = $this->getScalarProp('TITLE');

        if ($title) {
            $title = $title[0][0];
        }

        return $title;
    }


    /**
     * Длъжност
     *
     * @return string
     */
    public function getRole()
    {
        $title = $this->getScalarProp('ROLE');

        if ($title) {
            $title = $title[0][0];
        }

        return $title;
    }


    protected function getScalarProp($name, $types = NULL)
    {
        $name   = strtoupper($name);

        if (!isset($this->data[$name])) {
            return NULL;
        }

        $result = array();

        $types = arr::make($types, TRUE);

        foreach ($this->data[$name] as $entry) {
            $value  = implode(', ', $entry['value'][0]);

            if (empty($entry['param']['TYPE'])) {
                if (empty($types)) {
                    $result[0][] = $value;
                } else {
                    continue;
                }
            } else {
                foreach ($entry['param']['TYPE'] as $i=>$t) {
                    $t = strtolower($t);
                    if (empty($types) || isset($types[$t])) {
                        $result[$t][] = $value;
                    }
                }
            }
        }

        if (count($types) == 1 && !empty($result)) {
            $result = reset($result);
        }

        return $result;
    }


    protected function getStructProp($name, $part = NULL, $types = NULL)
    {
        $name = strtoupper($name);

        if (!isset($this->data[$name])) {
            return NULL;
        }

        // Или се искат всички части на структурата или часта, която се иска задължително
        // съществува
        expect(!isset($part) || isset(static::$partMaps[$name][$part]));

        $result = array();
        $types = arr::make($types, TRUE);

        foreach ($this->data[$name] as $i => $entry) {

            $vtypes = array();

            if (empty($entry['param']['TYPE'])) {
                if (!empty($types)) {
                    contunue;
                }

                $vtypes = array(0);
            } else {
                $vtypes = $entry['param']['TYPE'];
            }

            if (isset(static::$partMaps[$name])) {
                // Частите на параметъра $name са описани
                $partsMap = static::$partMaps[$name];
            } else {
                // Частите не са описани - приемаме, че частите са индексите на стойностите
                $partsMap = array_keys($entry['value']);
            }

            $values = $entry['value'];

            if (isset($part)) {
                $value = implode(',', $values[$partsMap[$part]]);
            } else {
                $value = array();
                $pm    = array_flip($partsMap);
                foreach ($values as $i=>$v) {
                    $value[$pm[$i]] = implode(',', $v);
                }
            }

            foreach ($vtypes as $i=>$t) {
                $t = strtolower($t);
                if (empty($types) || isset($types[$t])) {
                    $result[$t][] = $value;
                }
            }
        }


        if (count($types) == 1 && !empty($result)) {
            $result = reset($result);
        }

        return $result;
    }


    /**
     * Зарежда една или повече виз. карт. от файл
     *
     * @param string $fileName
     * @return array масив от pear_Vcard-обекти
     */
    public static function parseFile($fileName)
    {
        // create vCard parser
        $parse = File_IMC::parse('vCard');

        // parse a vCard file and store the data in $cardinfo
        try {
            $cardinfo = $parse->fromFile($fileName);
        } catch (Exception $e) {
            expect(FALSE, 'VCF файлът не може да бъде парсиран');
        }

        return static::initFromParsed($cardinfo);
    }


    /**
     * Зарежда една или повече виз. карт. от стринг
     *
     * @param string $str
     * @return array масив от pear_Vcard-обекти
     */
    public static function parseString($str)
    {
        // create vCard parser
        $parse = File_IMC::parse('vCard');

        // parse a vCard file and store the data in $cardinfo
        $cardinfo = $parse->fromText($str);


        return static::initFromParsed($cardinfo);
    }


    /**
     * @param array $cardinfo резултат от парсиране
     * @return array масив от pear_Vcard-обекти
     */
    protected static function initFromParsed($cardinfo)
    {
        $vcards = array();

        foreach ($cardinfo['VCARD'] as $c) {
            $vcard = new self();
            $vcard->data = $c;

            $vcards[] = $vcard;
        }

        return $vcards;
    }


    /**
     * Конвертира дата-време към TIMESTAMP
     *
     * @param string $str
     * @return int
     */
    protected static function toTimestamp($str)
    {
        if (substr($str, -1, 1) == 'Z') {
            $str = substr($str, 0, -1);
        }

        return strtotime($str);
    }
}