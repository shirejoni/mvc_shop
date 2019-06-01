<?php


$Router->all('.*', 'init/startup/init', 'web', false);
$Router->all('user/.*', 'init/login/index', 'web', false);
$Router->all('checkout/.*', 'init/login/index', 'web', false);
$Router->all('.*', 'init/startup/customer', 'web', false);
$Router->get('.*', 'init/front/category', 'web', false);
$Router->all('checkout/index', 'checkout/checkout/index', 'web', true);
$Router->all('checkout/cart', 'checkout/checkout/cart', 'web', true);
$Router->get('product/(\d+)', 'product/index', 'web', true);