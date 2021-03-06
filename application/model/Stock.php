<?php


namespace App\model;


use App\Lib\Database;
use App\system\Model;

/**
 * @property Database Database
 */
class Stock extends Model
{
    public function getStocks($option = []) {
        $option['order']   = isset($option['order']) ? $option['order'] : 'ASC';
        $option['language_id'] = isset($option['language_id']) ? $option['language_id'] : $this->Language->getLanguageID();

        $sql = "SELECT * FROM stock_status WHERE language_id = :lID ORDER BY stock_status_id ";



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

    public function getStock($stock_status_id, $lID = null) {
        $language_id = $this->Language->getLanguageID();
        if($lID && $lID != "all") {
            $language_id = $lID;
        }
        if($lID != "all") {
            $this->Database->query("SELECT * FROM stock_status WHERE stock_status_id = :sSID AND language_id = :lID ", array(
                'sSID'  => $stock_status_id,
                'lID'   => $language_id
            ));
            if($this->Database->hasRows()) {
                $row = $this->Database->getRow();
                return $row;
            }
            return false;
        }else {
            $this->Database->query("SELECT * FROM stock_status WHERE stock_status_id = :sSID", array(
                'sSID'  => $stock_status_id,
            ));
            $rows = $this->Database->getRows();
            return $rows;
        }
    }

}