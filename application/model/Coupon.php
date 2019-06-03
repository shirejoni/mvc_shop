<?php


namespace App\model;


use App\Lib\Database;
use App\system\Model;

/**
 * @property Database Database
 */
class Coupon extends Model
{
    public function getCoupon($coupon_id) {
        $this->Database->query("SELECT * FROM coupon WHERE coupon_id = :cID", array(
            'cID'   => $coupon_id,
        ));
        if($this->Database->hasRows()) {
            $row = $this->Database->getRow();
            $this->Database->query("SELECT * FROM coupon_product WHERE coupon_id = :cID", array(
                'cID'   => $coupon_id
            ));
            $row['products_id'] = [];
            foreach ($this->Database->getRows() as $r) {
                $row['products_id'][] = $r['product_id'];
            }
            $this->Database->query("SELECT * FROM coupon_category WHERE coupon_id = :cID", array(
                'cID'   => $coupon_id
            ));
            $row['categories_id'] = [];
            foreach ($this->Database->getRows() as $r) {
                $row['categories_id'][] = $r['category_id'];
            }
            return $row;
        }
        return false;
    }

    public function getCouponByKey($coupon_key) {
        $this->Database->query("SELECT * FROM coupon WHERE code = :cCode", array(
            'cCode'   => $coupon_key,
        ));

        if($this->Database->hasRows()) {
            $row = $this->Database->getRow();
            $this->Database->query("SELECT * FROM coupon_product WHERE coupon_id = :cID", array(
                'cID'   => $row['coupon_id']
            ));
            $row['products_id'] = [];
            foreach ($this->Database->getRows() as $r) {
                $row['products_id'][] = $r['product_id'];
            }
            $this->Database->query("SELECT * FROM coupon_category WHERE coupon_id = :cID", array(
                'cID'   => $row['coupon_id']
            ));
            $row['categories_id'] = [];
            foreach ($this->Database->getRows() as $r) {
                $row['categories_id'][] = $r['category_id'];
            }
            return $row;
        }
        return false;
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

    public function getCoupons($option = []) {
        $option['sort_order'] = isset($option['sort_order']) ? $option['sort_order'] : '';
        $option['order']   = isset($option['order']) ? $option['order'] : 'ASC';
        $option['language_id'] = isset($option['language_id']) ? $option['language_id'] : $this->Language->getLanguageID();

        $sql = "SELECT * FROM coupon ";

        $sort_order = array(
            'name',
            'coupon_id'
        );
        if(isset($option['name']) && in_array($option['name'], $sort_order)) {
            $sql .= " ORDER BY " . $option['name'];
        }else {
            $sql .= " ORDER BY coupon_id";
        }

        if(isset($option['order']) && $option['order'] == "DESC") {
            $sql .= " DESC";
        }else {
            $sql .= " ASC";
        }

        if(isset($option['start']) || isset($option['limit'])) {
            if(!isset($option['start']) || $option['start'] < 0) {
                $option['start'] = 0;
            }
            if(!isset($option['limit']) || $option['limit'] == 0) {
                $option['limit'] = 20;
            }
            $sql .= " LIMIT " . (int) $option['start'] . ',' . (int) $option['limit'];
        }

        $params = array(
            'lID'   => $option['language_id']
        );
        $this->Database->query($sql, $params);
        $this->rows = $this->Database->getRows();
        return $this->rows;
    }

    public function editCoupon($coupon_id, $data) {
        $sql = "UPDATE coupon SET ";
        $query = [];
        $params = [];
        if(isset($data['name'])) {
            $query[] = 'name = :cName';
            $params['cName'] = $data['name'];
        }
        if(isset($data['code'])) {
            $query[] = 'code = :cCode';
            $params['cCode'] = $data['code'];
        }

        if(isset($data['discount'])) {
            $query[] = 'discount = :cDiscount';
            $params['cDiscount'] = $data['discount'];
        }
        if(isset($data['type'])) {
            $query[] = 'type = :cType';
            $params['cType'] = $data['type'];
        }
        if(isset($data['minimum_price'])) {
            $query[] = 'minimum_price = :cMinimumPrice';
            $params['cMinimumPrice'] = $data['minimum_price'];
        }
        if(isset($data['date_start'])) {
            $query[] = 'date_start = :cDStarted';
            $params['cDStarted'] = $data['date_start'];
        }
        if(isset($data['date_end'])) {
            $query[] = 'date_end = :cDEnd';
            $params['cDEnd'] = $data['date_end'];
        }
        if(isset($data['status'])) {
            $query[] = 'status = :cStatus';
            $params['cStatus'] = $data['status'];
        }
        if(isset($data['date_added'])) {
            $query[] = 'date_added = :cDAdded';
            $params['cDAdded'] = $data['date_added'];
        }
        if(isset($data['count'])) {
            $query[] = 'count = :cCount';
            $params['cCount'] = $data['count'];
        }

        $sql .= implode(' , ', $query);
        $sql .= " WHERE coupon_id = :cID ";
        $params['cID'] = $coupon_id;
        if(count($query) > 0) {
            $this->Database->query($sql, $params);
        }
        if(isset($data['products_id'])) {
            $this->Database->query("DELETE FROM coupon_product WHERE coupon_id = :cID", array(
                'cID'   => $coupon_id,
            ));
            foreach ($data['products_id'] as $product_id) {
                $this->Database->query("INSERT INTO coupon_product (coupon_id, product_id) VALUES (:cID, :pID)", array(
                    'pID' => $product_id,
                    'cID'  => $coupon_id,
                ));
            }

        }
        if(isset($data['categories_id'])) {
            $this->Database->query("DELETE FROM coupon_category WHERE coupon_id = :cID", array(
                'cID'   => $coupon_id,
            ));
            foreach ($data['categories_id'] as $category_id) {
                $this->Database->query("INSERT INTO coupon_category (coupon_id, category_id) VALUES (:cID, :cCategoryID)", array(
                    'cCategoryID' => $category_id,
                    'cID'  => $coupon_id,
                ));
            }

        }
        if($this->Database->numRows() > 0) {
            return true;
        }else {
            return false;
        }
    }

    public function deleteCoupon($coupon_id) {
        $this->Database->query("DELETE FROM coupon WHERE coupon_id = :cID", array(
            'cID'   => $coupon_id
        ));
        return $this->Database->numRows();
    }

}