<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/functions.php';

$app = new Silex\Application();
$app['debug'] = true;


$DB_HOSTNAME = envar('DATABASE_SERVICE_NAME','127.0.0.1');
$DB_HOST = envar('uri', "mongodb://$DB_HOSTNAME:27017");
$DB_NAME = envar('database_name', 'mongodb');
$DB_USERNAME = envar('username', 'mongodb');
$DB_PASSWORD = envar('password', 'mongodb');

$DB_URI = str_replace('mongodb://', "mongodb://$DB_USERNAME:$DB_PASSWORD@", $DB_HOST);
$DB_URI .= "/$DB_NAME";

$mongodb = new MongoDB\Client($DB_URI);
$database = $mongodb->selectDatabase($DB_NAME);

$app->get('/ws/healthz/', function () {
    return 'OK';
});

$app->get('/ws/info/', function () {
    return json_encode(array(
        'id' => 'nationalparks-php',
        'displayName' => 'National Parks (PHP)',
        'type' => 'cluster',
        'center' => array('latitude' => '47.039304', 'longitude' => '14.505178'),
        'zoom' => 4
    ));
});

$app->get('/ws/data/load/', function () use ($database) {
    $collection = $database->nationalparks;
    $handle = fopen("nationalparks.json", "r");

    $collection->deleteMany(array());
    $collection->createIndex(array('Location' => '2dsphere'));

    $data = array();
    while (($line = fgets($handle)) !== false) {
        $tmp = json_decode($line);
        $tmp->Location = array($tmp->coordinates[1], $tmp->coordinates[0]);
        array_push($data, $tmp);
    }

    $collection->insertMany($data);
    fclose($handle);
    return 'Number of items in database: ' . $collection->count();
});

$app->get('/ws/data/all/', function () use ($database) {
    return export($database->nationalparks->find());
});

$app->get('/ws/data/within/', function (Symfony\Component\HttpFoundation\Request $request) use ($database) {
    $box = array(
        array((float) $request->get('lon1'), (float) $request->get('lat1')),
        array((float) $request->get('lon2'), (float) $request->get('lat2')),
    );

    $query = array('Location' => array('$within' => array('$box' => $box)));
    return export($database->nationalparks->find($query));
});

$app->get('/', function () {
    return 'Welcome to the National Parks data service.';
});

$app->run();
