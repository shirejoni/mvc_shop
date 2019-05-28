<?php


namespace App\lib;


use App\model\Customer;
use App\model\Language;
use App\model\Product;

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

    public function getProducts(Product $Product) {
        $customer_id = $this->Customer && $this->Customer->getCustomerId() ? $this->Customer->getCustomerId() : 0;
        $this->Database->query("SELECT * FROM cart WHERE customer_id = :cID AND session_id = :sID", array(
            'cID'   => $customer_id,
            'sID'   => session_id()
        ));
        $cart_products = [];
        if($this->Database->hasRows()) {
            foreach ($this->Database->getRows() as $cart_row) {
                $product = $Product->getProduct($cart_row['product_id'], $this->Language->getLanguageID());
                if(!$product) {
                    $product = $Product->getProduct($cart_row['product_id'], $this->Language->getDefaultLanguageID());
                }
                if($product && $cart_row['quantity'] > 0) {
                    $option_price = 0;
                    $option_weight = 0;
                    $option_data = [];
                    $stock = true;
                    foreach (json_decode($cart_row['product_option']) as $product_option_id => $product_option_value_id) {
                        $this->Database->query("SELECT * FROM product_option po LEFT JOIN option_group og on po.option_group_id = og.option_group_id
                        LEFT JOIN option_group_language ogl on og.option_group_id = ogl.option_group_id WHERE po.product_option_id = :pOID
                        AND po.product_id = :pID AND ogl.language_id = :lID", array(
                            'pOID'  => $product_option_id,
                            'pID'   => $product['product_id'],
                            'lID'   => $this->Language->getLanguageID()
                        ));
                        if(!$this->Database->hasRows()) {
                            $this->Database->query("SELECT * FROM product_option po LEFT JOIN option_group og on po.option_group_id = og.option_group_id
                        LEFT JOIN option_group_language ogl on og.option_group_id = ogl.option_group_id WHERE po.product_option_id = :pOID
                        AND po.product_id = :pID AND ogl.language_id = :lID", array(
                                'pOID'  => $product_option_id,
                                'pID'   => $product['product_id'],
                                'lID'   => $this->Language->getDefaultLanguageID()
                            ));
                        }
                        if($this->Database->hasRows()) {
                            $option_group = $this->Database->getRow();
                            $this->Database->query("SELECT * FROM product_option_value pov LEFT JOIN option_item oi on pov.option_item_id = oi.option_item_id
                            LEFT JOIN option_item_language oil ON oil.option_item_id = oi.option_item_id WHERE pov.product_option_value_id = :pOVID AND 
                            pov.prodct_option_id = :pOID AND oil.language_id = :lID", array(
                                'pOVID' => $product_option_value_id,
                                'pOID'  => $product_option_id,
                                'lID'   => $this->Language->getLanguageID()
                            ));
                            if(!$this->Database->hasRows()) {
                                $this->Database->query("SELECT * FROM product_option_value pov LEFT JOIN option_item oi on pov.option_item_id = oi.option_item_id
                            LEFT JOIN option_item_language oil ON oil.option_item_id = oi.option_item_id WHERE pov.product_option_value_id = :pOVID AND 
                            pov.prodct_option_id = :pOID AND oil.language_id = :lID", array(
                                    'pOVID' => $product_option_value_id,
                                    'pOID'  => $product_option_id,
                                    'lID'   => $this->Language->getDefaultLanguageID()
                                ));
                            }
                            if($this->Database->hasRows()) {
                                $product_option_value = $this->Database->getRow();
                                if($product_option_value['price_sign'] == "+") {
                                    $option_price += $product_option_value['price'];
                                }else {
                                    $option_price -= $product_option_value['price'];

                                }
                                if($product_option_value['weight_sign'] == "+") {
                                    $option_weight += $product_option_value['weight'];
                                }else {
                                    $option_weight -= $product_option_value['weight'];
                                }
                                if($product_option_value['subtract'] && (!$product_option_value['quantity'] || $product_option_value['quantity'] < $cart_row['quantity'])) {
                                    $stock = false;
                                }
                                $option_data[] = array(
                                    'product_option_id' => $product_option_id,
                                    'product_option_value_id'   => $product_option_value_id,
                                    'option_group_id'       => $option_group['option_group_id'],
                                    'option_item_id'        => $product_option_value['option_item_id'],
                                    'name'                  => $product_option_value['name'],
                                    'price'                 => $product_option_value['price'],
                                    'weight'                => $product_option_value['weight'],
                                    'price_sign'            => $product_option_value['price_sign'],
                                    'weight_sign'            => $product_option_value['weight_sign'],
                                    'subtract'            => $product_option_value['subtract'],
                                    'quantity'            => $product_option_value['quantity'],
                                    'language_id'            => $product_option_value['language_id'],

                                );
                            }
                        }
                    }
                    if(!$product['quantity'] || $product['quantity'] < $cart_row['quantity']) {
                        $stock = false;
                    }
                    $special = '';
                    $product_specials = $Product->getProductSpecials($product['product_id']);
                    foreach ($product_specials as $product_special) {
                            if($product_special['date_start'] < time() AND $product_special['date_end'] > time()) {
                                $special = $product_special['price'];
                            }
                    }
                    if($special) {
                        $price_per_unit = $special + $option_price;
                    }else {
                        $price_per_unit = $product['price'] + $option_price;
                    }
                    $cart_products[] = array(
                        'cart_id'   => $cart_row['cart_id'],
                        'quantity'  => $cart_row['quantity'],
                        'option'   => $option_data,
                        'name'      => $product['name'],
                        'product_id'=> $product['product_id'],
                        'image'     => $product['image'],
                        'minimum'   => $product['minimum'],
                        'stock'     => $stock,
                        'price'     => $product['price'],
                        'weight_per_unit'    => $product['weight'] + $option_weight,
                        'total_price_per_unit'  => $price_per_unit,
                        'total'     => $price_per_unit * $cart_row['quantity'],
                        'weight'    => ($product['weight'] + $option_weight) * $cart_row['quantity'],
                        'weight_id' => $product['weight_id'],
                        'length'    => $product['length'],
                        'length_id' => $product['length_id'],
                        'width'     => $product['width'],
                        'height'    => $product['height'],
                        'total_formatted'   => number_format($price_per_unit * $cart_row['quantity']),
                        'total_price_per_unit_formatted'    => number_format($price_per_unit),
                    );
                }

            }
        }
        return $cart_products;
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