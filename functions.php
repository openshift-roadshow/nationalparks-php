<?php

function export($input) {
    $data = array();
    foreach ($input as $item) {
        $tmp = array(
            'id' => $item->name,
            'latitude' => (string) $item->coordinates[0],
            'longitude' => (string) $item->coordinates[1],
            'name' => $item->toponymName
        );
        array_push($data, $tmp);
    }
    return json_encode($data);
}

function envar($name, $default) {
    if(isset($_ENV[$name])) return $_ENV[$name];
    return $default;
}