<?php


namespace App\model;


use App\Lib\Database;
use App\system\Model;

/**
 * @property Database Database
 */
class Coupon extends Model
{
    public function getCoupon() {

    }

    public function insertCoupon($data) {
        $this->Database->query("INSERT INTO coupon (name, code, discount, type, minimum_price, date_start, date_end, status, date_added, `count`) VALUES 
        (:cName, :cCode, :cDiscount, :cType, :cMinimumPrice, :cDStarted, :cDEnd, :cStatus, :cDAdded, :cCount)", array(
            'cName' => $data['name'],
            'cCode' => $data['code'],
            'cDiscount' => $data['discount'],
            'cType' => $data['type'],
            'cMinimumPrice' => $data['minimum_price'],
            'cDStarted' => $data['date_start'],
            'cDEnd'     => $data['date_end'],
            'cStatus'   => 0,
            'cDAdded'   => time(),
            'cCount'    => $data['count']
        ));
        $coupon_id = $this->Database->insertId();
        if(isset($data['products_id'])) {
            foreach ($data['products_id'] as $product_id) {
                $this->Database->query("INSERT INTO coupon_product (coupon_id, product_id) VALUES (:cID, :pID)", array(
                    'cID'   => $coupon_id,
                    'pID'   => $product_id
                ));
            }
        }
        if(isset($data['categories_id'])) {
            foreach ($data['categories_id'] as $category_id) {
                $this->Database->query("INSERT INTO coupon_category (coupon_id, category_id) VALUES (:cID, :cCategoryID)", array(
                    'cID'   => $coupon_id,
                    'cCategoryID'   => $category_id
                ));
            }
        }
        return $coupon_id;
    }

}