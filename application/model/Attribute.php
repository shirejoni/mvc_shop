<?php


namespace App\model;


use App\Lib\Database;
use App\system\Model;

/**
 * @property Database Database
 * @property Language Language
 */
class Attribute extends Model
{
    private $rows = [];
    private $attribute_id;
    private $attribute_group_id;
    private $sort_order;
    private $language_id;
    private $name;

    public function getAttributes($option = []) {
        $option['sort_order'] = isset($option['sort_order']) ? $option['sort_order'] : '';
        $option['order']   = isset($option['order']) ? $option['order'] : 'ASC';
        $option['language_id'] = isset($option['language_id']) ? $option['language_id'] : $this->Language->getLanguageID();

        $sql = "SELECT *, (SELECT agl.name FROM attribute_group_language agl WHERE agl.attribute_group_id = a.attribute_group_id
        AND agl.language_id = al.language_id) AS attributegroup_name FROM attribute a LEFT  JOIN attribute_language al on a.attribute_id = al.attribute_id
        WHERE al.language_id = :lID ";
        if(isset($option['filter_name'])) {
            $sql .= " AND al.name LIKE :fName ";
        }
        $sort_order = array(
            'name',
            'sort_order'
        );
        if(isset($option['sort_order']) && in_array($option['sort_order'], $sort_order)) {
            $sql .= " ORDER BY " . $option['sort_order'];
        }else {
            $sql .= " ORDER BY a.attribute_id";
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
            $params['fName'] = $option['filter_name'] . "%";
        }
        $this->Database->query($sql, $params);
        $this->rows = $this->Database->getRows();
        return $this->rows;
    }

    public function insertAttribute($data, $attribute_id = null) {
        if(!$attribute_id) {
            $this->Database->query("INSERT INTO attribute (attribute_group_id, sort_order) VALUES (:aGID, :aSortOrder)", array(
                'aGID' => $data['attribute_group_id'],
                'aSortOrder' => $data['sort_order']
            ));
            $attribute_id = $this->Database->insertId();
        }
        foreach ($data['attribute_names'] as $language_id => $attribute_name) {
            $this->Database->query("INSERT INTO attribute_language (attribute_id, language_id, name) 
            VALUES (:aID, :lID, :aName)", array(
                'aID'  => $attribute_id,
                'lID'   => $language_id,
                'aName'=> $attribute_name
            ));
        }
        return $attribute_id;
    }

    public function getAttribute($attribute_id, $lID = null) {
        $language_id = $this->Language->getLanguageID();
        if($lID && $lID != "all") {
            $language_id = $lID;
        }
        if($lID != "all") {
            $this->Database->query("SELECT * FROM attribute a LEFT  JOIN attribute_language al 
            on a.attribute_id = al.attribute_id WHERE al.language_id = :lID AND a.attribute_id = :aID", array(
                'aID'  => $attribute_id,
                'lID'   => $language_id
            ));
            if($this->Database->hasRows()) {
                $row = $this->Database->getRow();
                $this->attribute_id = $row['attribute_id'];
                $this->attribute_group_id = $row['attribute_group_id'];
                $this->sort_order = $row['sort_order'];
                $this->language_id = $row['language_id'];
                $this->name = $row['name'];
                $this->rows = [];
                $this->rows[] = $row;
                return $row;
            }
            return false;
        }else {
            $this->Database->query("SELECT * FROM attribute a LEFT  JOIN attribute_language al 
            on a.attribute_id = al.attribute_id WHERE  a.attribute_id = :aID", array(
                'aID'  => $attribute_id,
            ));
            $rows = $this->Database->getRows();
            if(count($rows) > 0) {
                $this->attribute_id = $rows[0]['attribute_id'];
                $this->attribute_group_id = $rows[0]['attribute_group_id'];
                $this->sort_order = $rows[0]['sort_order'];
                $this->rows = $rows;
            }
            return $rows;
        }
    }

    public function deleteAttribute($attribute_id, $data = []) {
        if(isset($data['attribute_names']) && count($data['attribute_names']) > 0) {
            foreach ($data['attribute_names'] as $language_id => $attribute_name) {
                $this->Database->query("DELETE FROM attribute_language WHERE attribute_id = :aID AND 
                language_id = :lID", array(
                    'aID'  => $attribute_id,
                    'lID'   => $language_id
                ));
            }
        }else {
            $this->Database->query("DELETE FROM attribute WHERE attribute_id = :aID", array(
                'aID'  => $attribute_id
            ));
        }
        return $this->Database->numRows();
    }

    public function editAttribute($attribute_id, $data) {
        $sql = "UPDATE attribute SET ";
        $query = [];
        $params = [];
        if(isset($data['sort_order'])) {
            $query[] = 'sort_order = :aGSortOrder';
            $params['aGSortOrder'] = $data['sort_order'];
        }
        if(isset($data['attribute_group_id'])) {
            $query[] = 'attribute_group_id = :aGID';
            $params['aGID'] = $data['attribute_group_id'];
        }

        $sql .= implode(' , ', $query);
        $sql .= " WHERE attribute_id = :aID ";
        $params['aID'] = $attribute_id;
        if(count($query) > 0) {
            $this->Database->query($sql, $params);
        }
        if(isset($data['attribute_names'])) {

            foreach ($data['attribute_names'] as $language_id => $attribute_name) {
                $this->Database->query("UPDATE attribute_language SET name = :aName WHERE 
                attribute_id = :aID AND language_id = :lID", array(
                    'aName' => $attribute_name,
                    'aID'  => $attribute_id,
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
}