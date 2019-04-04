<?php


namespace App\model;


use App\Lib\Database;
use App\system\Model;

/**
 * @property Database Database
 * @property Language Language
 */
class Filter extends Model
{
    private $rows = [];
    private $filter_group_id;
    private $sort_order;
    private $language_id;
    private $name;
    private $filters;

    /**
     * @return mixed
     */
    public function getFilters()
    {
        return $this->filters;
    }

    public function getFilterGroups($option = []) {
        $option['sort_order'] = isset($option['sort_order']) ? $option['sort_order'] : '';
        $option['order']   = isset($option['order']) ? $option['order'] : 'ASC';
        $option['language_id'] = isset($option['language_id']) ? $option['language_id'] : $this->Language->getLanguageID();

        $sql = "SELECT * FROM filter_group fg LEFT  JOIN filter_group_language fgl on fg.filter_group_id = fgl.filter_group_id
        WHERE fgl.language_id = :lID ";

        $sort_order = array(
            'name',
            'sort_order'
        );
        if(isset($option['sort_order']) && in_array($option['sort_order'], $sort_order)) {
            $sql .= " ORDER BY " . $option['sort_order'];
        }else {
            $sql .= " ORDER BY fg.filter_group_id";
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

    public function insertFilterGroup($data, $filter_group_id = null) {
        if(!$filter_group_id) {
            $this->Database->query("INSERT INTO filter_group (sort_order) VALUES (:fGSortOrder)", array(
                'fGSortOrder' => $data['sort_order']
            ));
            $filter_group_id = $this->Database->insertId();
        }
        foreach ($data['filter_names'] as $language_id => $filter) {
            $this->Database->query("INSERT INTO filter_group_language (filter_group_id, language_id, name) 
            VALUES (:fGID, :lID, :fGName)", array(
                'fGID'  => $filter_group_id,
                'lID'   => $language_id,
                'fGName'=> $filter
            ));
        }
        return $filter_group_id;
    }

    public function insertFilterItems($filter_group_id, $data) {
        foreach ($data as $item) {
            $this->Database->query("INSERT INTO filter (filter_group_id ,sort_order) VALUES (:fGID, :fGSortOrder)", array(
                'fGID' => $filter_group_id,
                'fGSortOrder' => $item['sort_order']
            ));
            $filter_id = $this->Database->insertId();
            foreach ($item['names'] as $language_id => $filter) {
                $this->Database->query("INSERT INTO filter_language (filter_id, language_id, name) 
            VALUES (:fID, :lID, :fName)", array(
                    'fID'  => $filter_id,
                    'lID'   => $language_id,
                    'fName'=> $filter
                ));
            }
        }


        return $filter_group_id;
    }

    public function getFilterGroup($filter_group_id, $lID = null) {
        $language_id = $this->Language->getLanguageID();
        if($lID && $lID != "all") {
            $language_id = $lID;
        }
        if($lID != "all") {
            $this->Database->query("SELECT * FROM filter_group fg LEFT JOIN filter_group_language fgl on fg.filter_group_id = fgl.filter_group_id
            WHERE fgl.language_id = :lID AND fg.filter_group_id = :fGID", array(
                'fGID'  => $filter_group_id,
                'lID'   => $language_id
            ));
            if($this->Database->hasRows()) {
                $this->Database->query("SELECT * FROM filter f LEFT JOIN filter_language fl on f.filter_id = fl.filter_id
                WHERE fl.language_id = :lID ", array(
                    'lID'   => $language_id,
                ));
                $filters = $this->Database->getRows();
                $row = $this->Database->getRow();
                $this->filter_group_id = $row['filter_group_id'];
                $this->sort_order = $row['sort_order'];
                $this->language_id = $row['language_id'];
                $this->name = $row['name'];
                $this->filters = $filters;
                $this->rows = [];
                $this->rows[] = $row;
                return $row;
            }
            return false;
        }else {
            $this->Database->query("SELECT * FROM filter_group fg LEFT JOIN filter_group_language fgl on fg.filter_group_id = fgl.filter_group_id
            WHERE fg.filter_group_id = :fGID", array(
                'fGID'  => $filter_group_id,
            ));
            $rows = $this->Database->getRows();
            if(count($rows) > 0) {
                $this->Database->query("SELECT * FROM filter f LEFT JOIN filter_language fl on f.filter_id = fl.filter_id");
                $filters = $this->Database->getRows();
                $this->filter_group_id = $rows[0]['filter_group_id'];
                $this->sort_order = $rows[0]['sort_order'];
                $this->filters = $filters;
                $this->rows = $rows;
            }
            return $rows;
        }
    }

    public function deleteFilterGroup($filter_group_id, $data = []) {
        if(isset($data['filter_group_names']) && count($data['filter_group_names']) > 0) {
            foreach ($data['filter_group_names'] as $language_id => $filter) {
                $this->Database->query("DELETE FROM filter_group_language WHERE filter_group_id = :fGID AND 
                language_id = :lID", array(
                    'fGID'  => $filter_group_id,
                    'lID'   => $language_id
                ));
            }
        }else {
            $this->Database->query("DELETE FROM filter_group WHERE filter_group_id = :fGID", array(
                'fGID'  => $filter_group_id
            ));
        }
        return $this->Database->numRows();
    }

    public function editFilterGroup($filter_group_id, $data) {
        $sql = "UPDATE filter_group SET ";
        $query = [];
        $params = [];
        if(isset($data['sort_order'])) {
            $query[] = 'sort_order = :fGSortOrder';
            $params['fGSortOrder'] = $data['sort_order'];
        }

        $sql .= implode(' , ', $query);
        $sql .= " WHERE filter_group_id = :fGID ";
        $params['fGID'] = $filter_group_id;
        if(count($query) > 0) {
            $this->Database->query($sql, $params);
        }
        if(isset($data['filter_names'])) {

            foreach ($data['filter_names'] as $language_id => $filter_name) {
                $this->Database->query("UPDATE filter_group_language SET name = :fGName WHERE 
                attribute_group_id = :fGID AND language_id = :lID", array(
                    'fGName' => $filter_name,
                    'fGID'  => $filter_group_id,
                    'lID'   => $language_id
                ));
            }
        }
        if(isset($data['filters'])) {
            $this->Database->query("DELETE FROM filter WHERE filter_group_id = :fGID", array(
                'fGID'  => $filter_group_id
            ));
            foreach ($data['filters'] as $filter) {
                $this->Database->query("INSERT INTO filter (filter_group_id, sort_order) VALUES (:fGID, :fSortOrder)", array(
                    'fGID'  => $filter_group_id,
                    'fSortOrder'    => $filter['sort_order']
                ));
                $filter_id = $this->Database->insertId();
                foreach ($filter['names'] as $language_id => $name) {

                    $this->Database->query("INSERT INTO filter_language (filter_id,language_id, name) VALUES (:fID,:lID, :fName)", array(
                        'fID'  => $filter_id,
                        'fName'    => $name,
                        'lID'   => $language_id
                    ));
                }
            }


        }
        if($this->Database->numRows() > 0) {
            return true;
        }else {
            return false;
        }
    }

    public function getFiltersSearch($data = []) {
        $data['sort'] = isset($data['sort']) ? $data['sort'] : '';
        $data['order'] = isset($data['order']) ? strtoupper($data['order']) : 'ASC';
        $data['language_id'] = isset($data['language_id']) ? $data['language_id'] : $this->Language->getLanguageID();

        $sql = "SELECT f.filter_id, f.filter_group_id, fl.name, fgl.name as `group`, fl.language_id, sort_order
        FROM filter f JOIN filter_language fl on f.filter_id = fl.filter_id JOIN filter_group_language fgl ON f.filter_group_id = fgl.filter_group_id
        WHERE fl.language_id = :lID AND fgl.language_id = :lID ";
        $sort_data = array(
            'name',
            'sort_order'
        );
        if(!empty($data['filter_name'])) {

            $sql .= " AND (fl.name LIKE :fName OR fgl.name LIKE :fName ) ";
        }

        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        }else {
            $data['sort'] = '';
            $sql .= " ORDER BY f.filter_id";
        }

        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
        $params = array(
            'lID'   => $data['language_id'],
        );
        if(isset($data['filter_name'])) {
            $params['fName'] = $data['filter_name'] . '%';
        }
        $this->Database->query($sql, $params);
        $rows = $this->Database->getRows();
        return $rows;
    }

    public function getFilterItem($filter_id, $lID = null) {
        $language_id = $this->Language->getLanguageID();
        if($lID && $lID != "all") {
            $language_id = $lID;
        }
        if($lID != "all") {
            $this->Database->query("SELECT *, (SELECT fgl.name FROM filter_group_language fgl WHERE fgl.filter_group_id = f.filter_group_id AND fgl.language_id = fl.language_id) as `group_name` FROM filter f LEFT JOIN filter_language fl on f.filter_id = fl.filter_id
            WHERE f.filter_id = :fID AND fl.language_id = :lID", array(
                'fID'  => $filter_id,
                'lID'   => $language_id
            ));
            if($this->Database->hasRows()) {
                $row = $this->Database->getRow();
                return $row;
            }
            return false;
        }else {
            $this->Database->query("SELECT *,(SELECT * FROM filter_group_language fgl WHERE fgl.filter_group_id = f.filter_group_id AND fgl.language_id = fl.language_id) as `group_name` FROM filter f LEFT JOIN filter_language fl on f.filter_id = fl.filter_id
            WHERE f.filter_id = :fID ", array(
                'fID'  => $filter_id,
            ));
            $rows = $this->Database->getRows();
            return $rows;
        }
    }

}