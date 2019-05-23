<?php

namespace App\Web\Controller;

use App\lib\Config;
use App\Lib\Database;
use App\model\Customer;
use App\system\Controller;

/**
 * @property Database Database
 * @property Config Config
 */
class ControllerInitStartup extends Controller {
    public function init() {
        $configs = $this->Database->getRows("SELECT * FROM config");

        foreach ($configs as $config) {
            if($config['serialized'] == 1) {
                $this->Config->set($config['key'], unserialize($config['value']));
            }else {
                $this->Config->set($config['key'], $config['value']);
            }
        }
    }

    public function customer() {
        if(isset($_SESSION['customer']) && isset($_SESSION['customer']['customer_id'])) {
            $customer_id = $_SESSION['customer']['customer_id'];
            /** @var Customer $Customer */
            $Customer = $this->load("Customer", $this->registry);
            if($customer_id && $Customer->getCustomerByID($customer_id)) {
                $this->registry->Customer = $Customer;
                return array(
                    'Customer'  => $Customer,
                );
            }

        }
    }
}
