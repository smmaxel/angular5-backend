<?php

/**
 * Main configuration for database connection
 * @return PDO
 */
function getConnection() {
  $dbhost = "127.0.0.1";
  $dbuser = "root";
  $dbpass = "";
  $dbname = "angulardb";
  
  $dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpass);
  $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  
  return $dbh;
}