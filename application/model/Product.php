<?php


namespace App\model;


use App\Lib\Database;
use App\system\Model;

/**
 * @property Database Database
 * @property Language Language
 */
class Product extends Model
{
    /**
     * @var array
     */
    private $rows = [];

    public function getProducts($option = [])
    {
        $option['sort_order'] = isset($option['sort_order']) ? $option['sort_order'] : '';
        $option['order'] = isset($option['order']) ? $option['order'] : 'ASC';
        $option['language_id'] = isset($option['language_id']) ? $option['language_id'] : $this->Language->getLanguageID();

        $sql = "SELECT * FROM product p LEFT JOIN product_language pl on p.product_id = pl.product_id WHERE
         pl.language_id = :lID";
        if (isset($option['filter_name'])) {
            $sql .= " AND pl.name LIKE :fName ";
        }
        $sort_order = array(
            'name',
            'sort_order'
        );
        if (isset($option['sort_order']) && in_array($option['sort_order'], $sort_order)) {
            $sql .= " ORDER BY " . $option['sort_order'];
        } else {
            $sql .= " ORDER BY p.product_id";
        }

        if (isset($option['order']) && $option['order'] == "DESC") {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($option['start']) || isset($option['limit'])) {
            if (!isset($option['start']) || $option['start'] < 0) {
                $option['start'] = 0;
            }
            if (!isset($option['limit']) || $option['limit'] == 0) {
                $option['limit'] = 20;
            }
            $sql .= " LIMIT " . (int)$option['start'] . ',' . (int)$option['limit'];
        }

        $params = array(
            'lID' => $option['language_id']
        );
        if (isset($option['filter_name'])) {
            $params['fName'] = '%' . $option['filter_name'] . '%';
        }
        $this->Database->query($sql, $params);
        $this->rows = $this->Database->getRows();
        return $this->rows;
    }

    public function insertProduct($data, $product_id = null)
    {
        if (!$product_id) {
            $this->Database->query("INSERT INTO product (quantity, manufacturer_id, image, stock_status_id, price, 
        date_available, date_added, date_updated, weight, weight_id, length, height, width, length_id, minimum, sort_order,
         views, status) VALUES (:pQuantity, :mID, :pImage, :sSID, :pPrice, :pDAvailable, :pDAdded, :pDUpdated, :pWeight, :wID,
        :pLength, :pHeight, :pWidth, :lengthID, :pMinimum, :pSortOrder, :pViews, :pStatus)", array(
                "pQuantity" => $data['product-quantity'],
                "mID" => $data['manufacturer_id'],
                "pImage" => $data['image'],
                "sSID" => $data['stock_status_id'],
                "pPrice" => $data['product-price'],
                "pDAvailable" => $data['product-date'],
                "pDAdded" => $data['time_added'],
                "pDUpdated" => $data['time_updated'],
                "pWeight" => $data['weight'],
                "wID" => $data['weight_id'],
                "pLength" => $data['length'],
                "pWidth" => $data['width'],
                "pHeight" => $data['height'],
                "lengthID" => $data['length_id'],
                "pMinimum" => $data['product-min-quantity-per-order'],
                "pSortOrder" => $data['sort_order'],
                "pViews" => 0,
                "pStatus" => 0,
            ));
            $product_id = $this->Database->insertId();
        }
        if (isset($data['product_descriptions'])) {
            foreach ($data['product_descriptions'] as $language_id => $product_description) {
                $this->Database->query("INSERT INTO product_language (product_id, language_id, name, description) VALUES 
                (:pID, :lID, :pName, :pDescription)", array(
                    'pID' => $product_id,
                    'lID' => $language_id,
                    'pName' => $product_description['name'],
                    'pDescription' => $product_description['description'],
                ));
            }
        }
        if (isset($data['categories_id'])) {
            foreach ($data['categories_id'] as $category_id) {
                $this->Database->query("INSERT INTO product_category (product_id, category_id) VALUES (:pID, :cID)", array(
                    'pID' => $product_id,
                    'cID' => $category_id
                ));
            }
        }
        if (isset($data['filters_id'])) {
            foreach ($data['filters_id'] as $filters_id) {
                $this->Database->query("INSERT INTO product_filter (product_id, filter_id) VALUES (:pID, :fID)", array(
                    'pID' => $product_id,
                    'fID' => $filters_id
                ));
            }
        }
        if (isset($data['attributes'])) {
            foreach ($data['attributes'] as $attribute) {
                foreach ($attribute['names'] as $language_id => $attribute_name) {
                    $this->Database->query("INSERT INTO product_attribute (product_id, attribute_id, language_id, `value`) VALUES 
                    (:pID, :aID, :lID, :pAValue)", array(
                        'pID' => $product_id,
                        'aID' => $attribute['attribute_id'],
                        'lID' => $language_id,
                        'pAValue' => $attribute_name
                    ));
                }
            }
        }
        if (isset($data['options'])) {
            foreach ($data['options'] as $option) {
                $this->Database->query("INSERT INTO product_option (option_group_id, product_id, required) VALUES
                (:oGID, :pID, :pORequired)", array(
                    'pID' => $product_id,
                    'oGID' => $option['option_group_id'],
                    'pORequired' => $option['required']
                ));
                $product_option_id = $this->Database->insertId();
                foreach ($option['option_items'] as $option_item) {
                    $this->Database->query("INSERT INTO product_option_value (prodct_option_id, option_group_id, 
                    product_id, option_item_id, subtract, quantity, price_sign, price, weight_sign, weight) VALUES 
                    (:pOID, :oGID, :pID, :oIID, :pOVSubtract, :pOVQuantity, :pOVPriceSign, :pOVPrice, :pOVWeightSign, :pOVWeight)", array(
                        'pOID' => $product_option_id,
                        'oGID' => $option['option_group_id'],
                        'pID' => $product_id,
                        'oIID' => $option_item['option_item_id'],
                        'pOVSubtract' => $option_item['subtract'],
                        'pOVQuantity' => $option_item['quantity'],
                        'pOVPriceSign' => $option_item['price_sign'],
                        'pOVPrice' => $option_item['price'],
                        'pOVWeightSign' => $option_item['weight_sign'],
                        'pOVWeight' => $option_item['weight'],
                    ));
                }
            }
        }

        if (isset($data['specials'])) {
            foreach ($data['specials'] as $product_special) {
                $this->Database->query("INSERT INTO product_special (product_id, priority, price, date_start, date_end) VALUES 
                (:pID, :pSPriority, :pSPrice, :pSDStart, :pSDEnd)", array(
                    'pID' => $product_id,
                    'pSPriority' => $product_special['priority'],
                    'pSPrice' => $product_special['price'],
                    'pSDStart' => $product_special['date_start'],
                    'pSDEnd' => $product_special['date_end'],
                ));
            }
        }

        if (isset($data['images'])) {
            foreach ($data['images'] as $image) {
                $this->Database->query("INSERT INTO product_image (product_id, image, sort_order) VALUES 
                (:pID, :pImage, :pSortOrder)", array(
                    'pID' => $product_id,
                    'pImage' => $image['src'],
                    'pSortOrder' => $image['sort_order']
                ));
            }
        }

        return $product_id;
    }

    public function getProductSpecials($product_id)
    {
        $this->Database->query("SELECT * FROM product_special WHERE product_id = :pID ORDER BY priority ASC", array(
            'pID' => $product_id
        ));
        return $this->Database->getRows();
    }

    public function getProduct($product_id, $lID = null)
    {
        $language_id = $this->Language->getLanguageID();
        if ($lID && $lID != "all") {
            $language_id = $lID;
        }
        if ($lID != "all") {
            $this->Database->query("SELECT * FROM product p LEFT JOIN product_language pl on p.product_id = pl.product_id
            WHERE pl.language_id = :lID AND p.product_id = :pID", array(
                'pID' => $product_id,
                'lID' => $language_id
            ));
            if ($this->Database->hasRows()) {
                $row = $this->Database->getRow();
                return $row;
            }
            return false;
        } else {
            $this->Database->query("SELECT * FROM product p LEFT JOIN product_language pl on p.product_id = pl.product_id
            WHERE  p.product_id = :pID", array(
                'pID' => $product_id,
            ));
            $rows = $this->Database->getRows();
            return $rows;
        }
    }

    public function editProduct($product_id, $data)
    {
        $sql = "UPDATE product SET ";
        $query = [];
        $params = [];
        if (isset($data['sort_order'])) {
            $query[] = 'sort_order = :pSortOrder';
            $params['pSortOrder'] = $data['sort_order'];
        }
        if (isset($data['product-quantity'])) {
            $query[] = 'quantity = :pQuantity';
            $params['pQuantity'] = $data['product-quantity'];
        }
        if (isset($data['manufacturer_id'])) {
            $query[] = 'manufacturer_id = :mID';
            $params['mID'] = $data['manufacturer_id'];
        }
        if (isset($data['image'])) {
            $query[] = 'image = :pImage';
            $params['pImage'] = $data['image'];
        }
        if (isset($data['stock_status_id'])) {
            $query[] = 'stock_status_id = :sSID';
            $params['sSID'] = $data['stock_status_id'];
        }
        if (isset($data['product-price'])) {
            $query[] = 'price = :pPrice';
            $params['pPrice'] = $data['product-price'];
        }
        if (isset($data['product-date'])) {
            $query[] = 'date_available = :pDAvailable';
            $params['pDAvailable'] = $data['product-date'];
        }
        if (isset($data['time_added'])) {
            $query[] = 'date_added = :pDAdded';
            $params['pDAdded'] = $data['time_added'];
        }
        if (isset($data['time_updated'])) {
            $query[] = 'date_updated = :pDUpdated';
            $params['pDUpdated'] = $data['time_updated'];
        }
        if (isset($data['weight'])) {
            $query[] = 'weight = :pWeight';
            $params['pWeight'] = $data['weight'];
        }
        if (isset($data['length'])) {
            $query[] = 'length = :pLength';
            $params['pLength'] = $data['length'];
        }
        if (isset($data['width'])) {
            $query[] = 'width = :pWidth';
            $params['pWidth'] = $data['width'];
        }
        if (isset($data['height'])) {
            $query[] = 'height = :pHeight';
            $params['pHeight'] = $data['height'];
        }
        if (isset($data['weight_id'])) {
            $query[] = 'weight_id = :wID';
            $params['wID'] = $data['weight_id'];
        }
        if (isset($data['length_id'])) {
            $query[] = 'length_id = :lengthID';
            $params['lengthID'] = $data['length_id'];
        }
        if (isset($data['views'])) {
            $query[] = 'views = :pViews';
            $params['pViews'] = $data['views'];
        }
        if (isset($data['product-min-quantity-per-order'])) {
            $query[] = 'minimum = :pMinimum';
            $params['pMinimum'] = $data['product-min-quantity-per-order'];
        }
        if (isset($data['status'])) {
            $query[] = 'status = :pStatus';
            $params['pStatus'] = $data['status'];
        }

        $sql .= implode(' , ', $query);
        $sql .= " WHERE product_id = :pID ";
        $params['pID'] = $product_id;
        if (count($query) > 0) {
            $this->Database->query($sql, $params);
        }
        if (isset($data['product_descriptions'])) {
            foreach ($data['product_descriptions'] as $language_id => $product_description) {
                $this->Database->query("UPDATE product_language SET name = :pName, description = :pDescription WHERE 
                product_id = :pID AND language_id = :lID", array(
                    'pName' => $product_description['name'],
                    'pDescription' => $product_description['description'],
                    'pID' => $product_id,
                    'lID' => $language_id
                ));
            }
        }


        if (isset($data['categories_id'])) {
            $this->Database->query("DELETE FROM product_category WHERE product_id = :pID", array(
                'pID' => $product_id
            ));
            foreach ($data['categories_id'] as $category_id) {
                $this->Database->query("INSERT INTO product_category (product_id, category_id) VALUES (:pID, :cID)", array(
                    'pID' => $product_id,
                    'cID' => $category_id
                ));
            }
        }
        if (isset($data['filters_id'])) {
            $this->Database->query("DELETE FROM product_filter WHERE product_id = :pID", array(
                'pID' => $product_id
            ));
            foreach ($data['filters_id'] as $filters_id) {
                $this->Database->query("INSERT INTO product_filter (product_id, filter_id) VALUES (:pID, :fID)", array(
                    'pID' => $product_id,
                    'fID' => $filters_id
                ));
            }
        }
        if (isset($data['attributes'])) {
            $this->Database->query("DELETE FROM product_attribute WHERE product_id = :pID", array(
                'pID' => $product_id
            ));
            foreach ($data['attributes'] as $attribute) {
                foreach ($attribute['names'] as $language_id => $attribute_name) {
                    $this->Database->query("INSERT INTO product_attribute (product_id, attribute_id, language_id, `value`) VALUES 
                    (:pID, :aID, :lID, :pAValue)", array(
                        'pID' => $product_id,
                        'aID' => $attribute['attribute_id'],
                        'lID' => $language_id,
                        'pAValue' => $attribute_name
                    ));
                }
            }
        }
        if (isset($data['options'])) {
            $this->Database->query("DELETE FROM product_option WHERE product_id = :pID", array(
                'pID' => $product_id
            ));
            $this->Database->query("DELETE FROM product_option_value WHERE product_id = :pID", array(
                'pID' => $product_id
            ));
            foreach ($data['options'] as $option) {
                $this->Database->query("INSERT INTO product_option (option_group_id, product_id, required) VALUES
                (:oGID, :pID, :pORequired)", array(
                    'pID' => $product_id,
                    'oGID' => $option['option_group_id'],
                    'pORequired' => $option['required']
                ));
                $product_option_id = $this->Database->insertId();
                foreach ($option['option_items'] as $option_item) {
                    $this->Database->query("INSERT INTO product_option_value (prodct_option_id, option_group_id, 
                    product_id, option_item_id, subtract, quantity, price_sign, price, weight_sign, weight) VALUES 
                    (:pOID, :oGID, :pID, :oIID, :pOVSubtract, :pOVQuantity, :pOVPriceSign, :pOVPrice, :pOVWeightSign, :pOVWeight)", array(
                        'pOID' => $product_option_id,
                        'oGID' => $option['option_group_id'],
                        'pID' => $product_id,
                        'oIID' => $option_item['option_item_id'],
                        'pOVSubtract' => $option_item['subtract'],
                        'pOVQuantity' => $option_item['quantity'],
                        'pOVPriceSign' => $option_item['price_sign'],
                        'pOVPrice' => $option_item['price'],
                        'pOVWeightSign' => $option_item['weight_sign'],
                        'pOVWeight' => $option_item['weight'],
                    ));
                }
            }
        }

        if (isset($data['specials'])) {
            $this->Database->query("DELETE FROM product_special WHERE product_id = :pID", array(
                'pID' => $product_id
            ));
            foreach ($data['specials'] as $product_special) {
                $this->Database->query("INSERT INTO product_special (product_id, priority, price, date_start, date_end) VALUES 
                (:pID, :pSPriority, :pSPrice, :pSDStart, :pSDEnd)", array(
                    'pID' => $product_id,
                    'pSPriority' => $product_special['priority'],
                    'pSPrice' => $product_special['price'],
                    'pSDStart' => $product_special['date_start'],
                    'pSDEnd' => $product_special['date_end'],
                ));
            }
        }

        if (isset($data['images'])) {
            $this->Database->query("DELETE FROM product_image WHERE product_id = :pID", array(
                'pID' => $product_id
            ));
            foreach ($data['images'] as $image) {
                $this->Database->query("INSERT INTO product_image (product_id, image, sort_order) VALUES 
                (:pID, :pImage, :pSortOrder)", array(
                    'pID' => $product_id,
                    'pImage' => $image['src'],
                    'pSortOrder' => $image['sort_order']
                ));
            }
        }

        if ($this->Database->numRows() > 0) {
            return true;
        } else {
            return false;
        }

    }

    public function deleteProduct($product_id, $data = [])
    {
        if (isset($data['product_descriptions'])) {
            foreach ($data['product_descriptions'] as $language_id => $product_description) {
                $this->Database->query("DELETE FROM product_language WHERE language_id = :lID AND product_id = :pID", array(
                    'pID' => $product_id,
                    'lID' => $language_id
                ));
            }
        } else {
            $this->Database->query("DELETE FROM product WHERE product_id = :pID", array(
                'pID' => $product_id
            ));
        }
        return $this->Database->numRows();
    }

    public function getProductCategories($product_id)
    {
        $this->Database->query("SELECT * FROM product_category WHERE product_id = :pID", array(
            'pID' => $product_id,
        ));
        $result = [];
        foreach ($this->Database->getRows() as $row) {
            $result[] = $row['category_id'];
        }
        return $result;
    }

    public function getProductFilters($product_id)
    {
        $this->Database->query("SELECT * FROM product_filter WHERE product_id = :pID", array(
            'pID' => $product_id,
        ));
        $result = [];
        foreach ($this->Database->getRows() as $row) {
            $result[] = $row['filter_id'];
        }
        return $result;
    }

    public function getProductAttributes($product_id)
    {
        $this->Database->query("SELECT * FROM product_attribute WHERE product_id = :pID", array(
            'pID' => $product_id
        ));
        $result = [];

        foreach ($this->Database->getRows() as $row) {
            $result[$row['attribute_id']]['attribute_id'] = $row['attribute_id'];
            $result[$row['attribute_id']]['values'][$row['language_id']] = $row['value'];
        }
        return $result;
    }

    public function getProductOptions($product_id, $lID = null)
    {
        $language_id = $this->Language->getLanguageID();
        if ($lID) {
            $language_id = $lID;
        }
        $this->Database->query("SELECT * FROM product_option po LEFT JOIN option_group og ON og.option_group_id = po.option_group_id LEFT JOIN option_group_language ogl ON po.option_group_id = ogl.option_group_id  WHERE product_id = :pID AND language_id = :lID ", array(
            'pID' => $product_id,
            'lID' => $language_id
        ));
        if (!$this->Database->hasRows()) {
            $this->Database->query("SELECT * FROM product_option po LEFT JOIN option_group og ON og.option_group_id = po.option_group_id LEFT JOIN option_group_language ogl ON po.option_group_id = ogl.option_group_id  WHERE product_id = :pID AND language_id = :lID ", array(
                'pID' => $product_id,
                'lID' => $this->Language->getDefaultLanguageID(),
            ));
        }
        $option_groups = [];
        foreach ($this->Database->getRows() as $row) {
            $option_groups[$row['product_option_id']] = array(
                'product_option_id' => $row['product_option_id'],
                'option_group_id' => $row['option_group_id'],
                'product_id' => $row['product_id'],
                'required' => $row['required'],
                'sort_order' => $row['sort_order'],
                'option_type' => $row['type'],
                'language_id' => $row['language_id'],
                'name' => $row['name']
            );
        }
        foreach ($option_groups as $index => $option_group) {
            $product_option_items = [];
            $this->Database->query("SELECT * FROM product_option_value pov LEFT JOIN option_item_language oil on 
            pov.option_item_id = oil.option_item_id WHERE prodct_option_id = :pOID AND language_id = :lID", array(
                'pOID' => $option_group['product_option_id'],
                'lID' => $language_id
            ));
            if (!$this->Database->hasRows()) {
                $this->Database->query("SELECT * FROM product_option_value pov LEFT JOIN option_item_language oil on 
            pov.option_item_id = oil.option_item_id WHERE prodct_option_id = :pOID AND language_id = :lID", array(
                    'pOID' => $option_group['product_option_id'],
                    'lID' => $this->Language->getDefaultLanguageID()
                ));
            }
            foreach ($this->Database->getRows() as $row) {
                $product_option_items[$row['product_option_value_id']] = array(
                    'product_option_value_id' => $row['product_option_value_id'],
                    'option_group_id' => $row['option_group_id'],
                    'product_option_id' => $row['prodct_option_id'],
                    'option_item_id' => $row['option_item_id'],
                    'product_id' => $row['product_id'],
                    'subtract' => $row['subtract'],
                    'quantity' => $row['quantity'],
                    'price_sign' => $row['price_sign'],
                    'weight_sign' => $row['weight_sign'],
                    'price' => $row['price'],
                    'weight' => $row['weight'],
                    'name' => $row['name'],
                    'language_id' => $row['language_id']
                );
            }
            $option_groups[$index]['option_items'] = $product_option_items;
        }
        return $option_groups;
    }

    public function getProductImages($product_id)
    {
        $this->Database->query("SELECT * FROM product_image WHERE product_id = :pID", array(
            'pID' => $product_id
        ));
        return $this->Database->getRows();
    }

    public function getProductReviews($product_id)
    {
        $this->Database->query("SELECT * FROM review WHERE product_id = :pID ORDER by date_added DESC ", array(
            'pID' => $product_id,
        ));
        return $this->Database->getRows();
    }

    public function getProductComplete($product_id, $lID = null)
    {
        $language_id = $this->Language->getLanguageID();
        if ($lID) {
            $language_id = $lID;
        }
        $this->Database->query("SELECT *,p.image as `image`, pl.name AS name, ml.name AS manufacturer_name ,(SELECT ps.price FROM product_special ps WHERE ps.product_id = p.product_id 
        AND ps.date_start < UNIX_TIMESTAMP() AND ps.date_end > UNIX_TIMESTAMP() ORDER BY ps.priority DESC  LIMIT 0,1) AS special, (SELECT ss.name FROM
         stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = pl.language_id) as `stock_status_name`,
         (SELECT wl.unit FROM weight_language wl WHERE wl.weight_id = p.weight_id AND wl.language_id = pl.language_id ) AS weight_unit,
         (SELECT ll.unit FROM length_langugae ll WHERE ll.length_id = p.length_id AND ll.language_id = pl.language_id ) AS `length_unit`,
         (SELECT AVG(r1.rate) FROM review r1 WHERE r1.product_id = p.product_id AND r1.status = 1)  AS rating, (SELECT COUNT(*) FROM 
         review r2 WHERE r2.product_id = p.product_id AND r2.status = 1) AS reviews
          FROM product p LEFT JOIN product_language pl ON p.product_id = pl.product_id
        LEFT JOIN manufacturer m ON m.manufacturer_id = p.manufacturer_id LEFT JOIN manufacturer_language ml ON ml.manufacturer_id = m.manufacturer_id 
        WHERE p.product_id = :pID  AND pl.language_id = :lID AND ml.language_id = :lID", array(
            'pID' => $product_id,
            'lID' => $language_id
        ));
        $row = $this->Database->getRow();
        if (!$row) {
            $this->Database->query("SELECT *,p.image as `image`, pl.name AS name, ml.name AS manufacturer_name ,(SELECT ps.price FROM product_special ps WHERE ps.product_id = p.product_id 
        AND ps.date_start < UNIX_TIMESTAMP() AND ps.date_end > UNIX_TIMESTAMP() ORDER BY ps.priority DESC  LIMIT 0,1) AS special, (SELECT ss.name FROM
         stock_status ss WHERE ss.stock_status_id = p.stock_status_id AND ss.language_id = pl.language_id) as `stock_status_name`,
         (SELECT wl.unit FROM weight_language wl WHERE wl.weight_id = p.weight_id AND wl.language_id = pl.language_id ) AS weight_unit,
         (SELECT ll.unit FROM length_langugae ll WHERE ll.length_id = p.length_id AND ll.language_id = pl.language_id ) AS `length_unit`,
         (SELECT AVG(r1.rate) FROM review r1 WHERE r1.product_id = p.product_id AND r1.status = 1)  AS rating, (SELECT COUNT(*) FROM 
         review r2 WHERE r2.product_id = p.product_id AND r2.status = 1) AS reviews
          FROM product p LEFT JOIN product_language pl ON p.product_id = pl.product_id
        LEFT JOIN manufacturer m ON m.manufacturer_id = p.manufacturer_id LEFT JOIN manufacturer_language ml ON ml.manufacturer_id = m.manufacturer_id 
        WHERE p.product_id = :pID  AND pl.language_id = :lID ", array(
                'pID' => $product_id,
                'lID' => $this->Language->getDefaultLanguageID()
            ));
            $row = $this->Database->getRow();
        }
        if (!$row) {
            return false;
        }
        return array(
            'product_id' => $row['product_id'],
            'special' => $row['special'],
            'rate' => round($row['rating']),
            'reviews_count' => $row['reviews'],
            'name' => $row['name'],
            'description' => $row['description'],
            'language_id' => $row['language_id'],
            'quantity' => $row['quantity'],
            'stock_status_id' => $row['stock_status_id'],
            'stock_status' => $row['stock_status_name'],
            'image' => $row['image'],
            'manufacturer_id' => $row['manufacturer_id'],
            'manufacturer_name' => $row['manufacturer_name'],
            'manufacturer_url' => $row['url'],
            'price' => $row['price'],
            'date_available' => $row['date_available'],
            'date_added' => $row['date_added'],
            'date_updated' => $row['date_updated'],
            'weight' => $row['weight'],
            'weight_id' => $row['weight_id'],
            'weight_unit' => $row['weight_unit'],
            'length' => $row['length'],
            'length_id' => $row['length_id'],
            'width' => $row['width'],
            'height' => $row['height'],
            'length_unit' => $row['length_unit'],
            'minimum' => $row['minimum'],
            'views' => $row['views'],
            'sort_order' => $row['sort_order']
        );
    }

    public function getCategory($product_id, $lID = null)
    {
        $language_id = $this->Language->getLanguageID();
        if ($lID) {
            $language_id = $lID;
        }
        $this->Database->query("SELECT * FROM product_category pc LEFT JOIN category c on pc.category_id = c.category_id
        LEFT JOIN category_language cl on c.category_id = cl.category_id WHERE pc.product_id = :pID AND cl.language_id = :lID ORDER 
        BY c.level DESC", array(
            'pID' => $product_id,
            'lID' => $language_id
        ));
        $row = $this->Database->getRow();
        if (!$row) {
            $this->Database->query("SELECT * FROM product_category pc LEFT JOIN category c on pc.category_id = c.category_id
        LEFT JOIN category_language cl on c.category_id = cl.category_id WHERE pc.product_id = :pID AND cl.language_id = :lID ORDER 
        BY c.level DESC", array(
                'pID' => $product_id,
                'lID' => $this->Language->getDefaultLanguageID()
            ));
            $row = $this->Database->getRow();
        }
        return $row;
    }

    public function getAttributes($product_id, $lID = null)
    {
        $language_id = $this->Language->getLanguageID();
        if ($lID) {
            $language_id = $lID;
        }
        $this->Database->query("SELECT *, al.name as `name`, agl.name as `group_name` FROM product_attribute pa LEFT JOIN attribute a on pa.attribute_id = a.attribute_id 
        LEFT JOIN attribute_language al on a.attribute_id = al.attribute_id LEFT JOIN attribute_group ag on a.attribute_group_id = ag.attribute_group_id LEFT JOIN 
        attribute_group_language agl on ag.attribute_group_id = agl.attribute_group_id WHERE pa.product_id = :pID AND al.language_id = :lID AND agl.language_id = :lID 
        ORDER BY ag.sort_order, a.attribute_group_id, a.sort_order ASC", array(
            'pID' => $product_id,
            'lID' => $language_id
        ));
        $rows = $this->Database->getRows();
        if (!$rows) {
            $this->Database->query("SELECT *, al.name as `name`, agl.name as `group_name` FROM product_attribute pa LEFT JOIN attribute a on pa.attribute_id = a.attribute_id 
        LEFT JOIN attribute_language al on a.attribute_id = al.attribute_id LEFT JOIN attribute_group ag on a.attribute_group_id = ag.attribute_group_id LEFT JOIN 
        attribute_group_language agl on ag.attribute_group_id = agl.attribute_group_id WHERE pa.product_id = :pID AND al.language_id = :lID AND agl.language_id = :lID 
        ORDER BY ag.sort_order, a.attribute_group_id, a.sort_order ASC", array(
                'pID' => $product_id,
                'lID' => $this->Language->getDefaultLanguageID()
            ));
            $rows = $this->Database->getRows();
        }

        $result = [];
        foreach ($rows as $row) {
            $result[] = array(
                'attribute_id' => $row['attribute_id'],
                'attribute_group_id' => $row['attribute_group_id'],
                'attribute_group_name' => $row['group_name'],
                'value' => $row['value'],
                'name' => $row['name']
            );
        }
        return $result;
    }

    public function getProductsComplete($data = [], $lID = null)
    {
        $i = 0;
        $params = [];
        $language_id = $this->Language->getLanguageID();
        if ($lID) {
            $language_id = $lID;
        }
        $sql = 'SELECT *,p.image as `image`,p.price AS `price`, pl.name AS name, ml.name AS manufacturer_name ,
         (SELECT AVG(r1.rate) FROM review r1 WHERE r1.product_id = p.product_id AND r1.status = 1)  AS rating, (SELECT COUNT(*) FROM 
         review r2 WHERE r2.product_id = p.product_id AND r2.status = 1) AS reviews, ps.price AS `special`';


        $sql .= ' FROM product p ';
        if(isset($data['category_id'])) {
            $sql .= ' INNER JOIN product_category pc ON p.product_id = pc.product_id INNER JOIN category_path cp ON pc.category_id = cp.category_id';
            if(isset($data['filters'])) {
                foreach ($data['filters'] as $filter_group_id => $filters_id) {
                    $sql .= " LEFT JOIN product_filter pf{$filter_group_id} ON pf{$filter_group_id}.product_id = p.product_id ";
                }
            }
        }
        $sql .= ' LEFT JOIN (SELECT ps.price, ps.product_id FROM product_special ps WHERE ps.date_start < UNIX_TIMESTAMP() 
        AND ps.date_end > UNIX_TIMESTAMP() ORDER BY ps.priority) AS ps ON ps.product_id = p.product_id ';
        $sql .= ' LEFT JOIN product_language pl ON p.product_id = pl.product_id
        LEFT JOIN manufacturer m ON m.manufacturer_id = p.manufacturer_id LEFT JOIN manufacturer_language ml ON ml.manufacturer_id = m.manufacturer_id ';


        $sql .= ' WHERE pl.language_id = :lID AND ml.language_id = :lID';
        if(isset($data['category_id'])) {
            $sql .= ' AND cp.path_id = :cPID';
            if(isset($data['filters'])) {
                foreach ($data['filters'] as $filter_group_id => $filters) {
                    $place_holder = [];
                    $place_holder_value = [];
                    foreach ($filters as $filter_id) {
                        $i++;
                        $place_holder[] = ':FID' . $i;// ['MID1', 'MID2' , 'MID3']
                        $place_holder_value['FID' . $i] = $filter_id;
                    }
                    $sql .= " AND pf{$filter_group_id}.filter_id IN (". implode(', ', $place_holder) .")";
                    $params = array_merge($params, $place_holder_value);
                }
            }
        }
        if(isset($data['min'])) {
            $sql .= ' AND ((ps.price IS NOT NULL AND ps.price >= :pMin) || ( ps.price IS NULL AND p.price >= :pMin)) ';
        }
        if(isset($data['max'])) {
            $sql .= ' AND ((ps.price IS NOT NULL AND ps.price <= :pMax) || (ps.price IS NULL AND p.price <= :pMax)) ';
        }
        if(isset($data['manufacturers_id'])) {
            $place_holder = [];
            $place_holder_value = [];
            foreach ($data['manufacturers_id'] as $manufacturer_id) {
                $i++;
                $place_holder[] = ':MID' . $i;// ['MID1', 'MID2' , 'MID3']
                $place_holder_value['MID' . $i] = $manufacturer_id;
            }
            $sql .= ' AND m.manufacturer_id IN ('. implode(', ', $place_holder) .')';
            $params = array_merge($params, $place_holder_value);
        }

        $sql .= ' GROUP BY p.product_id';
        $params['lID']  = $language_id;

        if(isset($data['category_id'])) {
            $params['cPID'] = $data['category_id'];
        }
        if(isset($data['min'])) {
            $params['pMin'] = $data['min'];
        }
        if(isset($data['max'])) {
            $params['pMax'] = $data['max'];
        }
        $this->Database->query($sql, $params);
        return $this->Database->getRows();
    }
}