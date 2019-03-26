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

    public function insertAttributeGroup($data) {
        $this->Database->query("INSERT INTO attribute_group (sort_order) VALUES (:aGSortOrder)", array(
            'aGSortOrder' => $data['sort_order']
        ));
        $attribute_group_id = $this->Database->insertId();
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
}