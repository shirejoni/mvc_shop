<?php


namespace App\model;


use App\Lib\Database;
use App\system\Model;

/**
 * @property Database Database
 */
class Weight extends Model
{
    public function getWeights($option = []) {
        $option['order']   = isset($option['order']) ? $option['order'] : 'ASC';
        $option['language_id'] = isset($option['language_id']) ? $option['language_id'] : $this->Language->getLanguageID();

        $sql = "SELECT * FROM weight w LEFT JOIN weight_language wl ON w.weight_id = wl.weight_id WHERE wl.language_id = :lID ORDER BY w.weight_id ";



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

    public function getWeight($weight_id, $lID = null) {
        $language_id = $this->Language->getLanguageID();
        if($lID && $lID != "all") {
            $language_id = $lID;
        }
        if($lID != "all") {
            $this->Database->query("SELECT * FROM weight w LEFT JOIN weight_language wl on w.weight_id = wl.weight_id 
            WHERE w.weight_id = :wID AND language_id = :lID ", array(
                'wID'  => $weight_id,
                'lID'   => $language_id
            ));
            if($this->Database->hasRows()) {
                $row = $this->Database->getRow();
                return $row;
            }
            return false;
        }else {
            $this->Database->query("SELECT * FROM weight w LEFT JOIN weight_language wl on w.weight_id = wl.weight_id 
            WHERE w.weight_id = :wID", array(
                'wID'  => $weight_id,
            ));
            $rows = $this->Database->getRows();
            return $rows;
        }
    }

}