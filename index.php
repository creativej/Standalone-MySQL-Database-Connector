<?php
require_once 'database.php';
require_once 'config/database.php';

$db = new Database($config);
$products = $db->get_products->all();

foreach($products as $product){
    echo $product->name.'<br/>';
}
?>