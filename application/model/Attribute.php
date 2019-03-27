<?php


namespace App\model;


use App\Lib\Database;
use App\system\Model;

/**
 * @property Database Database
 */
class Attribute extends Model
{
    private $rows = [];
    public function getAttributes($option = []) {
        $option['sort_order'] = isset($option['sort_order']) ? $option['sort_order'] : '';
        $option['order']   = isset($option['order']) ? $option['order'] : 'ASC';
        $option['language_id'] = isset($option['language_id']) ? $option['language_id'] : $this->Language->getLanguageID();

        $sql = "SELECT * FROM attribute a LEFT  JOIN attribute_language al on a.attribute_id = al.attribute_id
        WHERE al.language_id = :lID ";

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
}