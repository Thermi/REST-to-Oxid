<?php

require dirname(__FILE__) . "/bootstrap.php";


/* Importstart */

/* Produkte abrufen */

$productRequestURL = 'http://student.mi.hs-offenburg.de:8080/sqlrest/PRODUCT';
$productResponse = file_get_contents($productRequestURL);
$productXMLData = simplexml_load_string($productResponse);

/* Benutzer abrufen */

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
    $statement = $connection->prepare("INSERT INTO oxarticles (OXID, OXARTNUM, OXTITLE, OXPRICE, OXSUBCLASS) VALUES (?, ?, ?, ?, 'oxarticle')");
    if(!$statement) {
        echo "Line 42: Prepare failed: (" . $connection->errno . ")" . $connection->error . PHP_EOL;
        exit();
    }

    /* bind statement */
    if(!$statement->bind_param("sisd", $RANDOMID, $ID, $NAME, $PRICE)) {
        echo "Binding parameters failed: (" . $statement->errno . ") " . $statement->error . PHP_EOL;
        exit();
    }
    if(!$statement->execute()) {
        echo 'Error at import with ' . $entry . PHP_EOL;
        echo "Line 57: Execute failed: (" . $statement->errno . ") " . $statement->error . PHP_EOL;
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