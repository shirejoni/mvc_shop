<?php


namespace App\model;


use App\Lib\Database;
use App\system\Model;

/**
 * @property  Database Database
 * @property  Language Language
 */
class Option extends Model
{
    /**
     * @var array|bool
     */
    private $rows = [];

    public function getOptionGroups($option = []) {
        $option['sort_order'] = isset($option['sort_order']) ? $option['sort_order'] : '';
        $option['order']   = isset($option['order']) ? $option['order'] : 'ASC';
        $option['language_id'] = isset($option['language_id']) ? $option['language_id'] : $this->Language->getLanguageID();

        $sql = "SELECT * FROM option_group og LEFT  JOIN option_group_language ogl on og.option_group_id = ogl.option_group_id
        WHERE ogl.language_id = :lID ";

        $sort_order = array(
            'name',
            'sort_order'
        );
        if(isset($option['sort_order']) && in_array($option['sort_order'], $sort_order)) {
            $sql .= " ORDER BY " . $option['sort_order'];
        }else {
            $sql .= " ORDER BY og.option_group_id";
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

    public function insertOptionGroup($data, $option_group_id = null) {
        if(!$option_group_id) {
            $this->Database->query("INSERT INTO option_group (type, sort_order) VALUES (:oGType, :oGSortOrder)", array(
                'oGType' => $data['type'],
                'oGSortOrder' => $data['sort_order']
            ));
            $option_group_id = $this->Database->insertId();
        }
        foreach ($data['optiongroup_names'] as $language_id => $optiongroup_name) {
            $this->Database->query("INSERT INTO option_group_language (option_group_id, language_id, name) 
            VALUES (:oGID, :lID, :oGName)", array(
                'oGID'  => $option_group_id,
                'lID'   => $language_id,
                'oGName'=> $optiongroup_name
            ));
        }
        return $option_group_id;
    }

    public function insertOptionItems($option_group_id, $data) {
        foreach ($data as $item) {
            $this->Database->query("INSERT INTO option_item (option_group_id, image, sort_order) VALUES (:oGID, :oImage, :oSortOrder)",array(
                'oGID'  => $option_group_id,
                'oImage'=> $item['image'],
                'oSortOrder' => $item['sort_order']
            ));
            $option_id = $this->Database->insertId();
            foreach ($item['names'] as $language_id => $option_name) {
                $this->Database->query("INSERT INTO option_item_language (option_item_id, language_id, name) 
            VALUES (:oGID, :lID, :oName)", array(
                    'oGID'  => $option_id,
                    'lID'   => $language_id,
                    'oName'=> $option_name
                ));
            }
        }


        return $option_group_id;
    }
}