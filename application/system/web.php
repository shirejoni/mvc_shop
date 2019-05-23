<?php


$Router->all('.*', 'init/startup/init', 'web', false);
$Router->all('user/.*', 'init/login/index', 'web', false);
$Router->all('.*', 'init/startup/customer', 'web', false);


$Router->get('product/(\d+)', 'product/index', 'web', true);