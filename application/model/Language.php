<?php


namespace App\model;


use App\Lib\Database;
use App\Lib\Registry;
use App\system\Model;

/**
 * @property Database Database
 */
class Language extends Model
{
    private $defaultLanguageDIR = DEFAULT_LANGUAGE_DIR;
    private $defaultLanguageCode = DEFAULT_LANGUAGE_CODE;
    private $defaultLanguageID;
    private $languageID;
    private $languageCode;
    private $languageDIR;
    private $languages = [];
    private $data = [];

    public function __construct(Registry $registry)
    {
        parent::__construct($registry);
        $languageRows = $this->Database->getRows("SELECT * FROM language");
        foreach ($languageRows as $languageRow) {
            $this->languages[$languageRow['code']] = $languageRow;
        }
        $this->defaultLanguageID = $this->languages[$this->defaultLanguageCode]['language_id'];
        $this->languageID = $this->languages[$this->defaultLanguageCode]['language_id'];
        $this->languageCode = $this->languages[$this->defaultLanguageCode]['code'];
        $this->languageDIR = $this->languages[$this->defaultLanguageCode]['code'];
    }

    public function load($file_name, $key = '') {
        if(!$key) {
            $_ = array();
            $file = LANGUAGE_PATH . DS . $this->defaultLanguageDIR . DS . $file_name . '.php';
            if(is_file($file)) {
               require $file;
            }
            $file = LANGUAGE_PATH . DS . $this->languageCode . DS . $file_name . '.php';
            if(is_file($file)) {
                require $file;
            }
            $this->data = array_merge($this->data, $_);

        }else {
            $this->data[$key] = new Language($this->registry);
            $this->data[$key]->load($file_name);
        }
        return $this->data;
    }

    public function get($key) {
        return isset($this->data[$key]) ? $this->data[$key] : $key;
    }

    public function set($key, $value) {
        $this->data[$key] = $value;
    }

    public function all() {
        return $this->data;
    }

    public function setLanguageByID($language_id) {
        foreach ($this->languages as $language) {
            if($language['language_id'] == $language_id) {
                $this->languageID = $language['language_id'];
                $this->languageCode = $language['code'];
                $this->languageDIR = $language['code'];
                break;
            }
        }
    }

    /**
     * @return string
     */
    public function getDefaultLanguageDIR(): string
    {
        return $this->defaultLanguageDIR;
    }

    /**
     * @return string
     */
    public function getDefaultLanguageCode(): string
    {
        return $this->defaultLanguageCode;
    }

    /**
     * @return mixed
     */
    public function getDefaultLanguageID()
    {
        return $this->defaultLanguageID;
    }

    /**
     * @return mixed
     */
    public function getLanguageID()
    {
        return $this->languageID;
    }

    /**
     * @return mixed
     */
    public function getLanguageCode()
    {
        return $this->languageCode;
    }

    /**
     * @return mixed
     */
    public function getLanguageDIR()
    {
        return $this->languageDIR;
    }

    /**
     * @return array
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }



}