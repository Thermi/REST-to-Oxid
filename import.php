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
$category = "15eb8d678953e37addc7451995279e57";

/* import start  */

/* getting the products  */

$productRequestURL = 'http://student.mi.hs-offenburg.de:8080/sqlrest/PRODUCT';
$productResponse = file_get_contents($productRequestURL);
$productXMLData = simplexml_load_string($productResponse);

/* getting the users  */

$customerRequestURL = 'http://student.mi.hs-offenburg.de:8080/sqlrest/CUSTOMER';
$customerResponse = file_get_contents($customerRequestURL);
$customerXMLData = simplexml_load_string($customerResponse);

$connection = new mysqli ("localhost", "eb_apps_9", "PASSWORD", "eb_apps_9");
$continue = true;

if(mysqli_connect_errno()) {
    echo "Failed to connect to the MySQL server. Error: " . mysqli_connect_error() . PHP_EOL;
    exit();
}

foreach ($productXMLData->PRODUCT as $entry) {
    $productTempUrl = $productRequestURL."/".$entry;
    $productTempResponse = file_get_contents($productTempUrl);
    $productXMLData = simplexml_load_string($productTempResponse);

    $RANDOMID = uniqid();
    $ID = $productXMLData->ID;
    $NAME = $productXMLData->NAME;
    $PRICE = $productXMLData->PRICE;
    $i = null;
    /* prepare statement */
    /* problem here: Generate an unique OXID for every article  */
    $statement = $connection->prepare("INSERT INTO oxarticles (OXID, OXARTNUM, OXTITLE, OXPRICE, OXSUBCLASS, OXSHOPID, OXSTOCK, OXTITLE_1) VALUES (?, ?, ?, ?, 'oxarticle', 'oxbaseshop', 1, ?)");
    if(!$statement) {
        echo "Prepare failed: (" . $connection->errno . ")" . $connection->error . PHP_EOL;
        exit();
    }

    /* bind statement */
    if(!$statement->bind_param("sisds", $RANDOMID, $ID, $NAME, $PRICE, $ID)) {
        echo "Binding parameters failed: (" . $statement->errno . ") " . $statement->error . PHP_EOL;
        exit();
    }

    if(!$statement->execute()) {
        echo "Execute failed: (" . $statement->errno . ") " . $statement->error . PHP_EOL;
        exit();
    }


    /* Put the articles in the correct category */
    $statement = $connection->prepare("INSERT INTO oxobject2category (OXID, OXOBJECTID, OXCATNID, OXTIMESTAMP VALUES (?,?,?,?)");
    if(!$statement) {
        echo "Prepare failed: (" . $connection->errno . ")" . $connection->error . PHP_EOL;
        exit();
    }

    /* bind statement */
    $anotherRandomID = uniqid();
    if(!$statement->bind_param("ssss", $anotherRandomID, $ID, $category, date("%Y-%M-%d %H:%M:%S"))) {
        echo "Binding parameters failed: (" . $statement->errno . ") " . $statement->error . PHP_EOL;
        exit();
    }
    if(!$statement->execute()) {
        echo "Execute failed: (" . $statement->errno . ") " . $statement->error . PHP_EOL;
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
        echo "Line 73: Prepare failed: (" . $connection->errno . ")" . $connection->error . PHP_EOL;
        exit();
    }

        if(!$statement->bind_param("sisssss", $RANDOMID, $ID, $i, $FIRSTNAME, $LASTNAME, $STREET, $CITY)) {
        echo "Binding parameters failed: (" . $statement->errno . ") " . $statement->error . PHP_EOL;
        exit();
    }
        if(!$statement->execute()) {
        echo "Line 82: Execute failed: (" . $statement->errno . ") " . $statement->error;
        exit();
    }
}

echo 'Import erfolgreich.' . PHP_EOL;

mysqli_close($connection);


Oxid::run();

?>