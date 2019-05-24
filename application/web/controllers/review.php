<?php

namespace App\Web\Controller;

use App\lib\Action;
use App\lib\Request;
use App\lib\Response;
use App\model\Customer;
use App\model\Product;
use App\Model\Review;
use App\system\Controller;
/**
 * @property Response Response
 * @property Request Request
 * @property Customer Customer
 **/
class ControllerReview extends Controller {

    public function add()
    {
        $data = [];
        $messages = [];
        $error = false;
        if(isset($this->Request->post['comment-post'])) {
            $product_id = isset($this->Request->post['product-id']) && (int) $this->Request->post['product-id'] ? (int) $this->Request->post['product-id'] : 0;
            /** @var Product $Product */
            $Product = $this->load("Product", $this->registry);
            if($product_id && $Product->getProduct($product_id)) {

                $data['product_id'] = $product_id;
                if(!empty($this->Request->post['comment-name'])) {
                    $data['author'] = $this->Request->post['comment-name'];
                }else {
                    $error = true;
                    $messages[] = $this->Language->get('error_comment_name_empty');
                }
                if(!empty($this->Request->post['comment-description'])) {
                    $data['text'] = $this->Request->post['comment-description'];
                }else {
                    $error = true;
                    $messages[] = $this->Language->get('error_comment_description_empty');
                }
                $data['date_added'] = time();
                $data['date_updated'] = time();
                $data['customer_id'] = 0;
                $data['status'] = 1;
                $data['rate'] = isset($this->Request->post['comment-rating']) ? (int) $this->Request->post['comment-rating'] : 0;
                $data['rate'] = $data['rate'] >= 0 && $data['rate'] <= 5 ? $data['rate'] : 0;
                if($this->Customer) {
                    $data['author'] = $this->Customer->getFirstName() . " " . $this->Customer->getLastName();
                    $data['customer_id'] = $this->Customer->getCustomerId();
                }
                $json = [];

                if(!$error) {
                    /** @var Review $Review */
                    $Review = $this->load("Review", $this->registry);
                    $Review->insertReview($data);
                    $json['status'] = 1;
                    $json['messages'] = [$this->Language->get('success_message')];
                }
                if($error) {
                    $json['status'] = 0;
                    $json['messages'] = $messages;
                }
                $this->Response->setOutPut(json_encode($json));
                return;
            }

        }
        return new Action('error/notFound', 'web');
    }

}