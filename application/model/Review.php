<?php

namespace App\Model;

use App\Lib\Database;
use App\system\Model;

/**
 * @property Database Database
 */
class Review extends Model {

    public function insertReview($data) {
        $this->Database->query("INSERT INTO review (product_id, author, rate, text, status, date_added, date_updated, customer_id) VALUES 
        (:pID, :cAuthor, :cRate, :cText, :cStatus, :cDAdded, :cDUpdated, :cCustomerID)", array(
           'pID'    => $data['product_id'],
           'cAuthor'    => $data['author'],
           'cRate'      => $data['rate'],
           'cText'      => $data['text'],
           'cStatus'    => $data['status'],
           'cDAdded'    => $data['date_added'],
           'cDUpdated'    => $data['date_updated'],
            'cCustomerID'   => $data['customer_id']
        ));
        $review_id = $this->Database->insertId();
        return $review_id;

    }

}