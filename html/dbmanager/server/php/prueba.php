<?php
define('DB_DATABASENAME', 'test');
define('DB_USERNAME', 'root');
define('DB_USERPWD', 'prueba');
define('DB_HOST', 'localhost');
      try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASENAME, DB_USERNAME, DB_USERPWD);
        // set the PDO error mode to exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "Connected successfully";
      } catch(PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
      }
?>
