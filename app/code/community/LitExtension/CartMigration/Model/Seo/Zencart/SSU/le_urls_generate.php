<?php
/**
 * import ssu category and product urls from cache folder (cache/ssu/categories, cache/ssu/products) into database
 */
require('includes/application_top.php');
global $db;

//create & clear tables
echo "Create and truncate tables: le_url_cache_categories, le_url_cache_products <br />";
$db->Execute("
CREATE TABLE IF NOT EXISTS `le_url_cache_categories` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `categories_id` int(11),
  `lang` varchar(10),
  `path` varchar(255),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;
");

$db->Execute("
CREATE TABLE IF NOT EXISTS `le_url_cache_products` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `products_id` int(11),
  `lang` varchar(10),
  `path` varchar(255),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1;
");

$db->Execute("TRUNCATE table le_url_cache_categories;");
$db->Execute("TRUNCATE table le_url_cache_products;");

echo "import into le_url_cache_categories<br />";
$cat_base_dir = "cache/ssu/categories";

$cat_files = scandir($cat_base_dir);
foreach ($cat_files as $cat_file_name){
    if(in_array($cat_file_name, array(".",".."))){
        continue;
    }
    $name_arr = explode("_", $cat_file_name);
    if(count($name_arr) != 2){
        continue;
    }
    if($name_arr[0] < 1){
        continue;
    }

    $path = file_get_contents($cat_base_dir. "/" . $cat_file_name);

    $db->Execute("insert into le_url_cache_categories (categories_id, lang, path)
		values ('".$name_arr[0]."', '".$name_arr[1]."', '".$path."')
		");

}


//prd
echo "import into le_url_cache_products<br />";
$base_dir = "cache/ssu/products";

$files = scandir($base_dir);
foreach ($files as $file_name){
    if(in_array($file_name, array(".",".."))){
        continue;
    }
    $name_arr = explode("_", $file_name);
    if(count($name_arr) != 2){
        continue;
    }
    if($name_arr[0] < 1){
        continue;
    }

    $path = file_get_contents($base_dir. "/" . $file_name);

    $db->Execute("insert into le_url_cache_products (products_id, lang, path)
		values ('".$name_arr[0]."', '".$name_arr[1]."', '".$path."')
		");
}

echo "done! <br />";