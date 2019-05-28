<?php


namespace App\lib;


use App\model\Customer;
use App\model\Language;

class Cart
{
    /**
     * @var Database
     */
    private $Database;
    /**
     * @var Customer
     */
    private $Customer;
    /**
     * @var Config
     */
    private $Config;
    /**
     * @var Language
     */
    private $Language;

    public function __construct(Registry $registry, $old_session = false)
    {
        $this->Database = $registry->Database;
        $this->Customer = $registry->Customer;
        $this->Config = $registry->Config;
        $this->Language = $registry->Language;
        // Cart Refresh

    }


}