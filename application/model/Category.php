<?php


namespace App\model;


use App\Lib\Database;
use App\system\Model;

/**
 * @property Database Database
 * @property Language Language
 */
class Category extends Model
{
    /**
     * @var array
     */
    private $rows = [];

    public function getCategories($data = []) {
        $option['sort_order'] = isset($option['sort_order']) ? $option['sort_order'] : '';
        $option['order']   = isset($option['order']) ? $option['order'] : 'ASC';
        $option['language_id'] = isset($option['language_id']) ? $option['language_id'] : $this->Language->getLanguageID();

        $sql = "SELECT * FROM category c LEFT JOIN category_language cl on c.category_id = cl.category_id WHERE
        cl.language_id = :lID ";
        if(isset($data['filter_name'])) {
            $sql .= " AND cl.name LIKE :fName";
        }

        $sort_order = array(
            'name',
            'sort_order'
        );
        if(isset($option['sort_order']) && in_array($option['sort_order'], $sort_order)) {
            $sql .= " ORDER BY " . $option['sort_order'];
        }else {
            $sql .= " ORDER BY c.category_id";
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
        if(isset($data['filter_name'])) {
            $params['fName'] = $data['filter_name'] . "%";
        }
        $this->Database->query($sql, $params);
        $this->rows = $this->Database->getRows();
        return $this->rows;
    }


    public function insertCategory($data, $category_id = null) {
        if(!$category_id) {
            $this->Database->query("INSERT INTO category (parent_id, top, level, sort_order, status) VALUES 
            (:cPID, :cTop, :cLevel, :cSortOrder, :cStatus)", array(
                'cPID'  => $data['parent_id'],
                'cTop'  => 0,
                'cLevel'=> $data['level'],
                'cSortOrder'    => $data['sort_order'],
                'cStatus'       => 0
            ));
            $category_id = $this->Database->insertId();
        }
        foreach ($data['category_names'] as $language_id => $category_name) {
            $this->Database->query("INSERT INTO category_language (category_id, language_id, name) 
            VALUES (:cID, :lID, :cName)", array(
                'cID'  => $category_id,
                'lID'   => $language_id,
                'cName'=> $category_name
            ));
        }
        foreach ($data['filters_id'] as $filter_id ) {
            $this->Database->query("INSERT INTO category_filter (category_id, filter_id) VALUES (:cID, :fID)", array(
                'cID'   => $category_id,
                'fID'   => $filter_id
            ));
        }

        return $category_id;
    }

    public function getCategory() {

    }

}