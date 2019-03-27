<?php


namespace App\model;


use App\Lib\Database;
use App\system\Model;

/**
 * @property Language Language
 * @property Database Database
 */
class Attributegroup extends Model
{
    private $rows = [];
    private $attribute_group_id;
    private $sort_order;
    private $language_id;
    private $name;

    public function getAttributeGroups($option = []) {
        $option['sort_order'] = isset($option['sort_order']) ? $option['sort_order'] : '';
        $option['order']   = isset($option['order']) ? $option['order'] : 'ASC';
        $option['language_id'] = isset($option['language_id']) ? $option['language_id'] : $this->Language->getLanguageID();

        $sql = "SELECT * FROM attribute_group ag LEFT  JOIN attribute_group_language agl on ag.attribute_group_id = agl.attribute_group_id
        WHERE agl.language_id = :lID ";

        $sort_order = array(
            'name',
            'sort_order'
        );
        if(isset($option['sort_order']) && in_array($option['sort_order'], $sort_order)) {
            $sql .= " ORDER BY " . $option['sort_order'];
        }else {
            $sql .= " ORDER BY ag.attribute_group_id";
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

    public function insertAttributeGroup($data, $attribute_group_id = null) {
        if(!$attribute_group_id) {
            $this->Database->query("INSERT INTO attribute_group (sort_order) VALUES (:aGSortOrder)", array(
                'aGSortOrder' => $data['sort_order']
            ));
            $attribute_group_id = $this->Database->insertId();
        }
        foreach ($data['attributegroup_names'] as $language_id => $attributegroup_name) {
            $this->Database->query("INSERT INTO attribute_group_language (attribute_group_id, language_id, name) 
            VALUES (:aGID, :lID, :aGName)", array(
                'aGID'  => $attribute_group_id,
                'lID'   => $language_id,
                'aGName'=> $attributegroup_name
            ));
        }
        return $attribute_group_id;
    }

    public function getAttributeGroup($attributegroup_id, $lID = null) {
        $language_id = $this->Language->getLanguageID();
        if($lID && $lID != "all") {
            $language_id = $lID;
        }
        if($lID != "all") {
            $this->Database->query("SELECT * FROM attribute_group ag LEFT  JOIN attribute_group_language agl 
            on ag.attribute_group_id = agl.attribute_group_id WHERE agl.language_id = :lID AND ag.attribute_group_id = :aGID", array(
                'aGID'  => $attributegroup_id,
                'lID'   => $language_id
            ));
            if($this->Database->hasRows()) {
                $row = $this->Database->getRow();
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
            $this->Database->query("SELECT * FROM attribute_group ag LEFT  JOIN attribute_group_language agl 
            on ag.attribute_group_id = agl.attribute_group_id WHERE  ag.attribute_group_id = :aGID", array(
                'aGID'  => $attributegroup_id,
            ));
            $rows = $this->Database->getRows();
            if(count($rows) > 0) {
                $this->attribute_group_id = $rows[0]['attribute_group_id'];
                $this->sort_order = $rows[0]['sort_order'];
                $this->rows = $rows;
            }
            return $rows;
        }
    }

    public function deleteAttributeGroup($attribute_group_id, $data = []) {
        if(isset($data['attributegroup_names']) && count($data['attributegroup_names']) > 0) {
            foreach ($data['attributegroup_names'] as $language_id => $attributegroup_name) {
                $this->Database->query("DELETE FROM attribute_group_language WHERE attribute_group_id = :aGID AND 
                language_id = :lID", array(
                    'aGID'  => $attribute_group_id,
                    'lID'   => $language_id
                ));
            }
        }else {
            $this->Database->query("DELETE FROM attribute_group WHERE attribute_group_id = :aGID", array(
                'aGID'  => $attribute_group_id
            ));
        }
        return $this->Database->numRows();
    }

    public function editAttributeGroup($attribute_group_id, $data) {
        $sql = "UPDATE attribute_group SET ";
        $query = [];
        $params = [];
        if(isset($data['sort_order'])) {
            $query[] = 'sort_order = :aGSortOrder';
            $params['aGSortOrder'] = $data['sort_order'];
        }

        $sql .= implode(' , ', $query);
        $sql .= " WHERE attribute_group_id = :aGID ";
        $params['aGID'] = $attribute_group_id;
        if(count($query) > 0) {
            $this->Database->query($sql, $params);
        }
        if(isset($data['attributegroup_names'])) {

            foreach ($data['attributegroup_names'] as $language_id => $attributegroup_name) {
                $this->Database->query("UPDATE attribute_group_language SET name = :aGName WHERE 
                attribute_group_id = :aGID AND language_id = :lID", array(
                    'aGName' => $attributegroup_name,
                    'aGID'  => $attribute_group_id,
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