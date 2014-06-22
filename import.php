<?php

/*
 * Copyright (C) 2014 Noel Kuntze <noel@familie-kuntze.de>
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 3 as published by
 * the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

require dirname(__FILE__) . "/bootstrap.php";


/* You need to create a category beforehand and then get its ID by looking in the oxcategories table */
/* The category ID has to be set here */
$category = "CATEGORY ID GOES HERE ";
$host = "HOST GOES HERE";
$user = "DATABASE USER GOES HERE";
$password = "PASSWORD GOES HERE";
$table = "DATABASE TABLE GOES HERE";
/* import start  */

/* getting the products  */

$productRequestURL = 'http://student.mi.hs-offenburg.de:8080/sqlrest/PRODUCT';
$productResponse = file_get_contents($productRequestURL);
$productXMLData = simplexml_load_string($productResponse);

/* getting the users  */

$customerRequestURL = 'http://student.mi.hs-offenburg.de:8080/sqlrest/CUSTOMER';
$customerResponse = file_get_contents($customerRequestURL);
$customerXMLData = simplexml_load_string($customerResponse);

$connection = new mysqli ($host, $user, $password, $table);
$continue = true;

if(mysqli_connect_errno()) {
    echo "Failed to connect to the MySQL server. Error: " . mysqli_connect_error() . PHP_EOL;
    exit();
}
/* Set autocommit to false */
$connection->autocommit(FALSE);

foreach ($productXMLData->PRODUCT as $entry) {
    $productTempUrl = $productRequestURL."/".$entry;
    $productTempResponse = file_get_contents($productTempUrl);
    $productXMLData = simplexml_load_string($productTempResponse);

    $RANDOMID = uniqid();
    $ID = $productXMLData->ID;
    $NAME = $productXMLData->NAME;
    $PRICE = $productXMLData->PRICE;
    $i = null;
    $date = date("%Y-%M-%d %H:%M:%S");
    /* prepare statement */
    /* problem here: Generate an unique OXID for every article  */
    $statement = $connection->prepare("INSERT INTO oxarticles (OXID, OXARTNUM, OXTITLE, OXPRICE, OXSUBCLASS, OXSHOPID, OXSTOCK, OXTITLE_1) VALUES (?, ?, ?, ?, 'oxarticle', 'oxbaseshop', 1, ?)");
    if(!$statement) {
        echo "Articles: Prepare failed: (" . $connection->errno . ")" . $connection->error . PHP_EOL;
        exit();
    }

    /* bind statement */
    if(!$statement->bind_param("sisds", $RANDOMID, $ID, $NAME, $PRICE, $ID)) {
        echo "Articles: Binding parameters failed: (" . $statement->errno . ") " . $statement->error . PHP_EOL;
        exit();
    }

    if(!$statement->execute()) {
        echo "Articles: Execute failed: (" . $statement->errno . ") " . $statement->error . PHP_EOL;
        exit();
    }


    /* Put the articles in the correct category */
    $statement = $connection->prepare("INSERT INTO oxobject2category (OXID, OXOBJECTID, OXCATNID, OXTIMESTAMP) VALUES (?,?,?,?)");
    if(!$statement) {
        echo "Categories: Prepare failed: (" . $connection->errno . ")" . $connection->error . PHP_EOL;
        exit();
    }

    /* bind statement */
    $anotherRandomID = uniqid();
    if(!$statement->bind_param("ssss", $anotherRandomID, $ID, $category, $date)) {
        echo "Categories:  Binding parameters failed: (" . $statement->errno . ") " . $statement->error . PHP_EOL;
        exit();
    }
    if(!$statement->execute()) {
        echo "Categories: Execute failed: (" . $statement->errno . ") " . $statement->error . PHP_EOL;
        exit();
    }
}

foreach ($customerXMLData->CUSTOMER as $entry) {
    $i += 1;
    $customerTempUrl = $customerRequestURL."/".$entry;
    $customerTempResponse = file_get_contents($customerTempUrl);
    $customerXMLData = simplexml_load_string($customerTempResponse);

    $RANDOMID = uniqid();
    $ID = $customerXMLData->ID;
    $FIRSTNAME = $customerXMLData->FIRSTNAME;
    $LASTNAME = $customerXMLData->LASTNAME;
    $STREET = $customerXMLData->STREET;
    $CITY = $customerXMLData->CITY;

    if(!$statement = $connection->prepare("INSERT INTO oxuser (OXID, OXUSERNAME,"
        . "OXSHOPID, OXRIGHTS, OXACTIVE, OXCUSTNR, OXFNAME, OXLNAME, OXSTREET,"
        . "OXCITY) VALUES (?, ?,"
        . "'oxbaseshop', 'user', 1, ?, ?, ?, ?, ?)")) {
        echo "Users: Prepare failed: (" . $connection->errno . ")" . $connection->error . PHP_EOL;
        exit();
    }

        if(!$statement->bind_param("sisssss", $RANDOMID, $ID, $i, $FIRSTNAME, $LASTNAME, $STREET, $CITY)) {
        echo "Users: Binding parameters failed: (" . $statement->errno . ") " . $statement->error . PHP_EOL;
        exit();
    }
        if(!$statement->execute()) {
        echo "Users: Execute failed: (" . $statement->errno . ") " . $statement->error;
        exit();
    }
}

if(!$connection->commit()) {
    echo "Transaction failed.";
    exit();
}
echo 'Import successfully completed!' . PHP_EOL;

mysqli_close($connection);


Oxid::run();

?>