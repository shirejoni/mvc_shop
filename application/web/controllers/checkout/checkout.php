<?php

namespace App\Web\Controller;

use App\lib\Cart;
use App\lib\Response;
use App\model\Customer;
use App\model\Image;
use App\model\Product;
use App\system\Controller;

/**
 * @property Response Response
 * @property Customer Customer
 */
class ControllerCheckoutCheckout extends Controller {

    public function index() {
        $data = [];
        if($this->Customer && $this->Customer->getCustomerId()) {
            header("location:" . URL . 'checkout/cart');
            exit();
        }
        $data['checkoutProcess'] = array(
            ['ورود', true],
            ['مرسوله', false],
            ['آدرس', false],
            ['پرداخت', false],
            ['پایان', false],

        );
        $this->Response->setOutPut($this->render('checkout/register-login', $data));
    }


    public function cart() {
        $data = [];
        $data['checkoutProcess'] = array(
            ['ورود', false],
            ['مرسوله', true],
            ['آدرس', false],
            ['پرداخت', false],
            ['پایان', false],

        );
        if($this->Customer && isset($_SESSION['session_old_id'])) {
            /** @var Cart $Cart */
            $Cart = new Cart($this->registry, $_SESSION['session_old_id']);
        }else {
            /** @var Cart $Cart */
            $Cart = new Cart($this->registry);
        }
        /** @var Product $Product */
        $Product = $this->load("Product", $this->registry);
        $products = $Cart->getProducts($Product);
        /** @var Image $Image */
        $Image = $this->load("Image", $this->registry);
        $total = 0;
        foreach ($products as $index => $product) {
            $image = $product['image'];
            if(is_file(ASSETS_PATH . DS . substr($product['image'], strlen(ASSETS_URL)))) {
                $image = ASSETS_URL . $Image->resize(substr($product['image'], strlen(ASSETS_URL)), 200, 200);
            }
            $total += $product['total'];
            $products[$index]['image'] = $image;
        }
        $off_price = 0;
        $data['Products'] = $products;
        $data['Total'] = $total;
        $data['TotalFormatted'] = number_format($total);
        $data['Off'] = $off_price;
        $data['OffFormatted'] = number_format($data['Off']);
        $data['PaymentPrice'] = $total - $off_price;
        $data['PaymentPriceFormatted'] = number_format($data['PaymentPrice']);

        $this->Response->setOutPut($this->render('checkout/cart', $data));
    }
}