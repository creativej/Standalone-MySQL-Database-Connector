# Standalone Database Connector

An abstract layer for database connection, and simple data querying. 


Note: I do not advise to use this for production purpose because there are many database class out there that can do more and better things. 

This is just a small experienment/excuse for myself to play around with the PHP magic method. 
Sometimes I just feel like reinventing the wheels. =] 
 
## Features

* Standalone requires single file only
* Converts all records into object for easy access e.g. product->name
* Simplifies mysql query - e.g. $db->get_products->all()

## Usage

Setup database: 
```
	require_once 'database.php';

	//Feel free to put the config detail in a seperate file 
	$config['host'] 		= 'localhost';
	$config['useranme'] 	= 'admin';
	$config['password'] 	= 'admin';
	$config['database']		= 'database';
	
	$db = new Database($config);
```

Get model
```
	//Name convention in getting products model: $db->get_{tablename}
	$products_model = $db->get_products;
```
Get all products:
```
	$products = $products_model->all();

	foreach($products as $product){
   		echo $product->name.'<br/>';
	}

	//More options
	$order	= 'name asc';
	$offset	= 0;
	$limit	= 25;
	
	//Useful for pagination
	$products = $products_model->all($order, $offset, $limit);
```	
Get By fields:
```
	$products = $products_model->by_id(23);

	foreach($products as $product){
		echo $product->name.'<br/>';
	}
	
	//Another example
	$products = $products_model->by_date_added_AND_category_id('2009-11-02', 2);
	
	//More options
	$products = $products_model->by_date_added_AND_category_id('2009-11-02', 2, $order, $limit, $offset);
	
Get first record by field:
	$product = $products_model->first_by_id(23);
	echo $product->name;	
	
	//You can use $model->first_by_{field#1}_AND_{field#2}(value#1, value#2)
	$product = $products_model->first_by_id_AND_name(23, 'product');
```
Easily extendable:
```
	class Product_model extends Database{
		$table_name = 'product';
		
		public function __construct($config){
			parent::__contruct($config);
		}
	}
	
	$product_model = new Product_model($config);
	$products = $product_model->all();
```	
## More to come
* Support the user of OR, LIKE statement for querying.
* Support complex queries
* Support database other than MySQL
* INSERT, UPDATE, DELETE support

		

	
