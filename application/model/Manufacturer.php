<?php


namespace App\model;


use App\Lib\Database;
use App\system\Model;

/**
 * @property Language Language
 * @property Database Database
 */
class Manufacturer extends Model
{
    private $rows = [];
    private $manufacturer_id;
    private $image;
    private $url;
    private $sort_order;
    private $language_id;
    private $name;
    private $status;

    public function getManufacturers($option = []) {
        $option['sort_order'] = isset($option['sort_order']) ? $option['sort_order'] : '';
        $option['order']   = isset($option['order']) ? $option['order'] : 'ASC';
        $option['language_id'] = isset($option['language_id']) ? $option['language_id'] : $this->Language->getLanguageID();

        $sql = "SELECT * FROM manufacturer m LEFT JOIN manufacturer_language ml on m.manufacturer_id = ml.manufacturer_id
        WHERE ml.language_id = :lID ";
        if(isset($option['filter_name'])) {
            $sql .= " AND ml.name LIKE :fName ";
        }
        $sort_order = array(
            'name',
            'sort_order'
        );
        if(isset($option['sort_order']) && in_array($option['sort_order'], $sort_order)) {
            $sql .= " ORDER BY " . $option['sort_order'];
        }else {
            $sql .= " ORDER BY m.manufacturer_id";
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
        if(isset($option['filter_name'])) {
            $params['fName'] = '%' . $option['filter_name'] . '%';
        }
        $this->Database->query($sql, $params);
        $this->rows = $this->Database->getRows();
        return $this->rows;
    }

    public function getManufacturerByUrl($url, $lID = null) {
        $language_id = $this->Language->getLanguageID();
        if($lID && $lID != "all") {
            $language_id = $lID;
        }
        if($lID != "all") {
            $this->Database->query("SELECT * FROM manufacturer m LEFT  JOIN manufacturer_language ml 
            on m.manufacturer_id = ml.manufacturer_id WHERE ml.language_id = :lID AND m.url = :mURL", array(
                'mURL'  => $url,
                'lID'   => $language_id
            ));
            if($this->Database->hasRows()) {
                $row = $this->Database->getRow();
                $this->manufacturer_id = $row['manufacturer_id'];
                $this->image = $row['image'];
                $this->url = $row['url'];
                $this->sort_order = $row['sort_order'];
                $this->language_id = $row['language_id'];
                $this->name = $row['name'];
                $this->status = $row['status'];
                $this->rows = [];
                $this->rows[] = $row;
                return $row;
            }
            return false;
        }else {
            $this->Database->query("SELECT * FROM manufacturer m LEFT  JOIN manufacturer_language ml 
            on m.manufacturer_id = ml.manufacturer_id WHERE m.url = :mURL", array(
                'mURL'  => $url,
            ));
            if($this->Database->hasRows()) {
                $rows = $this->Database->getRows();
                $this->manufacturer_id = $rows[0]['manufacturer_id'];
                $this->image = $rows[0]['image'];
                $this->url = $rows[0]['url'];
                $this->sort_order = $rows[0]['sort_order'];
                $this->status = $rows[0]['status'];
                $this->rows = $rows;
                return $rows;
            }
            return [];
        }
    }

    public function getManufacturerByID($manufacturer_id, $lID = null) {
        $language_id = $this->Language->getLanguageID();
        if($lID && $lID != "all") {
            $language_id = $lID;
        }
        if($lID != "all") {
            $this->Database->query("SELECT * FROM manufacturer m LEFT  JOIN manufacturer_language ml 
            on m.manufacturer_id = ml.manufacturer_id WHERE ml.language_id = :lID AND m.manufacturer_id = :mID", array(
                'mID'  => $manufacturer_id,
                'lID'   => $language_id
            ));
            if($this->Database->hasRows()) {
                $row = $this->Database->getRow();
                $this->manufacturer_id = $row['manufacturer_id'];
                $this->image = $row['image'];
                $this->url = $row['url'];
                $this->sort_order = $row['sort_order'];
                $this->language_id = $row['language_id'];
                $this->name = $row['name'];
                $this->status = $row['status'];
                $this->rows = [];
                $this->rows[] = $row;
                return $row;
            }
            return false;
        }else {
            $this->Database->query("SELECT * FROM manufacturer m LEFT  JOIN manufacturer_language ml 
            on m.manufacturer_id = ml.manufacturer_id WHERE m.manufacturer_id = :mID", array(
                'mID'  => $manufacturer_id,
            ));
            if($this->Database->hasRows()) {
                $rows = $this->Database->getRows();
                $this->manufacturer_id = $rows[0]['manufacturer_id'];
                $this->image = $rows[0]['image'];
                $this->url = $rows[0]['url'];
                $this->sort_order = $rows[0]['sort_order'];
                $this->status = $rows[0]['status'];
                $this->rows = $rows;
                return $rows;
            }
            return [];
        }
    }

    public function insertManufacturer($data, $manufacturer_id = null) {
        if(!$manufacturer_id) {
            $this->Database->query("INSERT INTO manufacturer (image, url, sort_order) VALUES (:mImage, :mURL, :mSortOrder)", array(
                'mImage'    => $data['image'],
                'mURL'      => $data['url'],
                'mSortOrder'=> $data['sort_order']
            ));
            $manufacturer_id = $this->Database->insertId();
        }
        foreach ($data['manufacturer_names'] as $language_id => $manufacturer_name) {
            $this->Database->query("INSERT INTO manufacturer_language (manufacturer_id, language_id, name) 
            VALUES (:mID, :lID, :mName)", array(
                'mID'  => $manufacturer_id,
                'lID'   => $language_id,
                'mName'=> $manufacturer_name
            ));
        }
        return $manufacturer_id;
    }

    public function editManufacturer($manufacturer_id, $data) {
        $sql = "UPDATE manufacturer SET ";
        $query = [];
        $params = [];
        if(isset($data['sort_order'])) {
            $query[] = 'sort_order = :mSortOrder';
            $params['mSortOrder'] = $data['sort_order'];
        }
        if(isset($data['image'])) {
            $query[] = 'image = :mImage';
            $params['mImage'] = $data['image'];
        }
        if(isset($data['url'])) {
            $query[] = 'url = :mUrl';
            $params['mUrl'] = $data['url'];
        }
        if(isset($data['status'])) {
            $query[] = 'status = :mStatus';
            $params['mStatus'] = $data['status'];
        }

        $sql .= implode(' , ', $query);
        $sql .= " WHERE manufacturer_id = :mID ";
        $params['mID'] = $manufacturer_id;
        if(count($query) > 0) {
            $this->Database->query($sql, $params);
        }
        if(isset($data['manufacturer_names'])) {

            foreach ($data['manufacturer_names'] as $language_id => $manufacturer_name) {
                $this->Database->query("UPDATE manufacturer_language SET name = :mName WHERE 
                manufacturer_id = :mID AND language_id = :lID", array(
                    'mName' => $manufacturer_name,
                    'mID'  => $manufacturer_id,
                    'lID'   => $language_id
                ));
            }

        }
        if($this->Database->numRows() > 0) {
            return true;
        }else {
            return false;
        }
    }

    public function deleteManufacturer($manufacturer_id, $data = []) {
        if(isset($data['manufacturer_names']) && count($data['manufacturer_names']) > 0) {
            foreach ($data['manufacturer_names'] as $language_id => $manufacturer_name) {
                $this->Database->query("DELETE FROM manufacturer_language WHERE manufacturer_id = :mID AND 
                language_id = :lID", array(
                    'mID'  => $manufacturer_id,
                    'lID'   => $language_id
                ));
            }
        }else {
            $this->Database->query("DELETE FROM manufacturer WHERE manufacturer_id = :mID", array(
                'mID'  => $manufacturer_id
            ));
        }
        return $this->Database->numRows();
    }
}