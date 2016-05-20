<?php
require __DIR__ . '/doctrine/dbal/vendor/autoload.php';
require __DIR__ . '/doctrine/doctrine2/vendor/autoload.php';
require __DIR__ . '/uri/vendor/autoload.php';


// ---------------------- Test uri library
use League\Uri\Schemes\Http as HttpUri;
$uri = HttpUri::createFromString("http://uri.thephpleague.com/.././report/");
echo $uri->getPath(), PHP_EOL; //display "/.././report/"

// --------------------- test doctrine/dbal
$config = new \Doctrine\DBAL\Configuration();

$connectionParams = array(
    'dbname' => 'meine_db',
    'user' => 'mein_benutzer',
    'password' => 'f2xEvEcadasd7asdsad7sahbh',
    'host' => 'localhost',
    'port' => 3306,
    'charset' => 'utf8',
    'driver' => 'pdo_mysql',
);

use Doctrine\DBAL\DriverManager;
$conn = DriverManager::getConnection($connectionParams, $config);

$sql = "SELECT * FROM is_tbl_importeur";
$stmt = $conn->query($sql); // Simple, but has several drawbacks
$users = $stmt->fetchAll();
//print_r($users);


// ------------------- test doctrine/doctrine2 (ORM)
// bootstrap.php
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

// Create a simple "default" Doctrine ORM configuration for Annotations
$isDevMode = true;
$config = Setup::createAnnotationMetadataConfiguration(array(__DIR__."/src"), $isDevMode);
// or if you prefer yaml or XML
//$config = Setup::createXMLMetadataConfiguration(array(__DIR__."/config/xml"), $isDevMode);
//$config = Setup::createYAMLMetadataConfiguration(array(__DIR__."/config/yaml"), $isDevMode);

// database configuration parameters
$conn = array(
    'driver' => 'pdo_sqlite',
    'path' => __DIR__ . '/db.sqlite',
);

// obtaining the entity manager
$entityManager = EntityManager::create($conn, $config);

echo "Hallo Welt";