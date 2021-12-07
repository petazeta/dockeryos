<?php
/*
  Constants that have a default value
*/

if (!defined('THEME_PATH')) define('THEME_PATH', '../../client/themes');
if (!defined('CATALOG_PATH')) define('CATALOG_PATH', '../../catalog-images/');
if (!defined('VIEWS_FOLDER')) define('VIEWS_FOLDER', 'views');

//Database system
if (!defined('DB_SYSTEM')) define('DB_SYSTEM', 'mysql');
if (!defined('DB_SQLFILE')) define('DB_SQLFILE', '../utils/mysqldb.sql');
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
?>
