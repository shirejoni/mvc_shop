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
    private $category_id;
    private $sort_order;
    private $language_id;
    private $name;

    public function getCategories($option = []) {
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

    public function getCategoriesComp($option = []) {
        $option['sort_order'] = isset($option['sort_order']) ? $option['sort_order'] : '';
        $option['order']   = isset($option['order']) ? $option['order'] : 'ASC';
        $option['language_id'] = isset($option['language_id']) ? $option['language_id'] : $this->Language->getLanguageID();

        $sql = "SELECT cp.category_id, cl2.name,  GROUP_CONCAT(cl1.name ORDER BY cp.level SEPARATOR ',') as `full_name`, 
                c.parent_id, c.top, c.level, c.sort_order, c.status FROM category c LEFT JOIN category_path cp on c.category_id = cp.category_id LEFT JOIN 
                category_language cl1 on cp.path_id = cl1.category_id LEFT JOIN category_language cl2 ON cp.category_id = cl2.category_id
                WHERE cl1.language_id = :lID AND cl2.language_id = :lID ";
        if(isset($option['filter_name'])) {
            $sql .= " AND cl.name LIKE :fName";
        }
        $sql .= " GROUP BY cp.category_id";
        $sort_order = array(
            'name',
            'sort_order'
        );
        if(isset($option['sort_order']) && in_array($option['sort_order'], $sort_order)) {
            $sql .= " ORDER BY " . $option['sort_order'];
        }else {
            $sql .= " ORDER BY cp.category_id";
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
        $level = 0;
        if(isset($data['parent_id']) && $data['parent_id'] != 0) {
            $this->Database->query("SELECT * FROM category_path WHERE category_id = :cID ORDER BY level ASC", array(
                'cID'   => $data['parent_id']
            ));
            $rows = $this->Database->getRows();
            foreach ($rows as $row) {
                $this->Database->query("INSERT INTO category_path (category_id, path_id, level) VALUES 
                (:cID, :cPID, :cLevel)", array(
                    'cID'   => $category_id,
                    'cPID'  => $row['path_id'],
                    'cLevel'=> $level
                ));
                $level++;
            }

        }
        $this->Database->query("INSERT INTO category_path (category_id, path_id, level) VALUES 
                (:cID, :cPID, :cLevel)", array(
            'cID'   => $category_id,
            'cPID'  => $category_id,
            'cLevel'=> $level
        ));

        return $category_id;
    }

    public function getCategory($category_id, $lID = null) {
        $language_id = $this->Language->getLanguageID();
        if($lID && $lID != "all") {
            $language_id = $lID;
        }
        if($lID != "all") {
            $this->Database->query("SELECT * FROM category c LEFT JOIN category_language cl on c.category_id = cl.category_id
            WHERE c.category_id = :cID AND cl.language_id = :lID", array(
                'cID'  => $category_id,
                'lID'   => $language_id
            ));
            if($this->Database->hasRows()) {
                $row = $this->Database->getRow();
                $this->category_id = $row['category_id'];
                $this->sort_order = $row['sort_order'];
                $this->language_id = $row['language_id'];
                $this->name = $row['name'];
                $this->rows = [];
                $this->rows[] = $row;
                return $row;
            }
            return false;
        }else {
            $this->Database->query("SELECT * FROM category c LEFT JOIN category_language cl on c.category_id = cl.category_id
            WHERE c.category_id = :cID", array(
                'cID'  => $category_id,
            ));
            $rows = $this->Database->getRows();
            if(count($rows) > 0) {
                $this->category_id = $rows[0]['category_id'];
                $this->sort_order = $rows[0]['sort_order'];
                $this->rows = $rows;
            }
            return $rows;
        }
    }

    public function deleteCategory($category_id, $data = []) {
        if(isset($data['category_names']) && count($data['category_names']) > 0) {
            foreach ($data['category_names'] as $language_id => $category_name) {
                $this->Database->query("DELETE FROM category_language WHERE category_id = :cID AND 
                language_id = :lID", array(
                    'cID'  => $category_id,
                    'lID'   => $language_id
                ));
            }
        }else {
            $this->Database->query("SELECT * FROM category_path WHERE path_id = :cPID", array(
                'cPID'  => $category_id
            ));
            $rows = $this->Database->getRows();
            foreach ($rows as $row) {
                $this->Database->query("DELETE FROM category WHERE category_id = :cID", array(
                    'cID' => $row['category_id']
                ));
            }
        }
        return $this->Database->numRows();
    }

    public function editCategory($category_id, $data) {
        $sql = "UPDATE category SET ";
        $query = [];
        $params = [];
        if(isset($data['sort_order'])) {
            $query[] = 'sort_order = :cSortOrder';
            $params['cSortOrder'] = $data['sort_order'];
        }
        if(isset($data['parent_id'])) {
            $query[] = 'parent_id = :cPID';
            $params['cPID'] = $data['parent_id'];
        }
        if(isset($data['top'])) {
            $query[] = 'top = :cTop';
            $params['cTop'] = $data['top'];
        }
        if(isset($data['level'])) {
            $query[] = 'level = :cLevel';
            $params['cLevel'] = $data['level'];
        }
        if(isset($data['status'])) {
            $query[] = 'status = :cStatus';
            $params['cStatus'] = $data['status'];
        }

        $sql .= implode(' , ', $query);
        $sql .= " WHERE category_id = :cID ";
        $params['cID'] = $category_id;
        if(count($query) > 0) {
            $this->Database->query($sql, $params);
        }
        if(isset($data['category_names'])) {

            foreach ($data['category_names'] as $language_id => $category_name) {
                $this->Database->query("UPDATE category_language SET name = :cName WHERE 
                category_id = :cID AND language_id = :lID", array(
                    'cName' => $category_name,
                    'cID'  => $category_id,
                    'lID'   => $language_id
                ));
            }

        }
        if(isset($data['parent_id'])) {
            $this->Database->query("SELECT * FROM category_path WHERE path_id = :cPID", array(
                'cPID'  => $category_id
            ));
            if($this->Database->hasRows()) {
                foreach ($this->Database->getRows() as $row) {
                    $this->Database->query("DELETE FROM category_path WHERE category_id = :cID AND level < :cLevel", array(
                        'cID'   => $row['category_id'],
                        'cLevel'=> $row['level']
                    ));
                    $this->Database->query("SELECT * FROM category_path WHERE category_id = :cID ORDER BY level ASC", array(
                        'cID'   => $data['parent_id']
                    ));
                    $path = [];
                    foreach ($this->Database->getRows() as $result) {
                        $pat[] = $result['path_id'];
                    }
                    $this->Database->query("SELECT * FROM category_path WHERE category_id = :cID ORDER BY level ASC", array(
                        'cID'   => $row['category_id']
                    ));
                    foreach ($this->Database->getRows() as $result) {
                        $pat[] = $result['path_id'];
                    }
                    $level = 0;
                    foreach ($path as $path_id) {
                        $this->Database->query("REPLACE INTO category_path (category_id, path_id, level) VALUES 
                        (:cID, :cPID, :cLevel)", array(
                            'cID'   => $row['category_id'],
                            'cPID'  => $path_id,
                            'cLevel'=> $level,
                        ));
                        $level++;
                    }
                }
            }else {
                $this->Database->query("DELETE FROM category_path WHERE category_id = :cID", array(
                    'cID'   => $category_id
                ));
                $this->Database->query("SELECT * FROM category_path WHERE category_id = :cID ORDER BY level ASC", array(
                    'cID'   => $data['parent_id']
                ));
                $rows = $this->Database->getRows();
                $level = 0;
                foreach ($rows as $row) {
                    $this->Database->query("INSERT INTO category_path (category_id, path_id, level) VALUES 
                (:cID, :cPID, :cLevel)", array(
                        'cID'   => $category_id,
                        'cPID'  => $row['path_id'],
                        'cLevel'=> $level
                    ));
                    $level++;
                }
                $this->Database->query("REPLACE INTO category_path (category_id, path_id, level) VALUES 
                (:cID, :cPID, :cLevel)", array(
                    'cID'   => $category_id,
                    'cPID'  => $category_id,
                    'cLevel'=> $level
                ));
            }



        }
        if(isset($data['filters_id'])) {
            $this->Database->query("DELETE FROM category_filter WHERE category_id = :cID", array(
                'cID'   => $category_id
            ));
            foreach ($data['filters_id'] as $filter_id ) {
                $this->Database->query("INSERT INTO category_filter (category_id, filter_id) VALUES (:cID, :fID)", array(
                    'cID'   => $category_id,
                    'fID'   => $filter_id
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