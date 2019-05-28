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
        $this->Database->query("DELETE FROM cart WHERE customer_id = 0 AND date_added < :cTime", array(
            'cTime' => time() - $this->Config->get('max_active_cart_session')
        ));
        if($this->Customer && $this->Customer->getCustomerId()) {
            $this->Database->query("UPDATE cart SET session_id = :sID WHERE customer_id = :cID", array(
                'cID'   => session_id()
            ));
            if($old_session) {
                $this->Database->query("SELECT * FROM cart WHERE customer_id = 0 AND session_id = :sID", array(
                    'sID'   => $old_session
                ));
                foreach ($this->Database->getRows() as $row) {
                    $this->Database->query("DELETE FROM cart WHRER cart_id = :cID", array(
                        'cID'   => $row['cart_id']
                    ));
                    $this->add($row['product_id'], $row['quantity'], $row['product_option']);
                }
            }
            $this->Database->query("SELECT * FROM cart WHERE customer_id = 0 AND session_id = :sID", array(
                'sID'   => session_id()
            ));
            foreach ($this->Database->getRows() as $row) {
                $this->Database->query("DELETE FROM cart WHRER cart_id = :cID", array(
                    'cID'   => $row['cart_id']
                ));
                $this->add($row['product_id'], $row['quantity'], json_decode($row['product_option']));
            }
        }

    }
    public function add($product_id, $quantity = 1, $product_option = array()) {
        $customer_id = $this->Customer && $this->Customer->getCustomerId() ? $this->Customer->getCustomerId() : 0;
        $this->Database->query("SELECT COUNT(*) as `total` FROM cart WHERE customer_id = :cID AND product_id = :pID AND 
        product_option = :cPO AND session_id = :sID", array(
            'cID'   => $customer_id,
            'pID'   => $product_id,
            'cPO'   => json_encode($product_option),
            'sID'   => session_id()
        ));
        $row = $this->Database->getRow();
        if(!$row['total']) {
            $this->Database->query("INSERT INTO cart (customer_id, product_id, quantity, session_id, product_option, date_added) 
            VALUES (:cID, :pID, :cQuantity, :sID, :cPO, :cDAdded)", array(
                'cID'   => $customer_id,
                'pID'   => $product_id,
                'cQuantity' => $quantity,
                'sID'   => session_id(),
                'cPO'   => json_encode($product_option),
                'cDAdded'   => time()
            ));
        }else {
            $this->Database->query("UPDATE cart SET quantity = quantity + :cQuantity WHERE customer_id = :cID AND product_id = :pID
            AND product_option = :cPO AND session_id = :sID", array(
                'cID'   => $customer_id,
                'pID'   => $product_id,
                'cQuantity' => $quantity,
                'sID'   => session_id(),
                'cPO'   => json_encode($product_option),
            ));
        }
        return true;
    }


}