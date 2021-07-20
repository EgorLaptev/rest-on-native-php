<?php

header('Content-Type: application/json');

include_once "Core/Validate.php";

//error_reporting(E_ERROR)

$method = $_SERVER['REQUEST_METHOD'];

$data = getData($method);

function getData($method) {

    /* GET, POST */
    if ($method === 'GET') return $_GET;
    if ($method === 'POST') return $_POST;

    /* PUT, PATCH, DELETE.. */
    $data = [];
    $exploded = explode('&', file_get_contents('php://input'));

    foreach ($exploded as $pair) {

        $item = explode('=', $pair);

        if (count($item) === 2) {
            $data[urldecode($item[0])] = urldecode($item[1]);
        }

    }

    return $data;

}

$url = (isset($_GET['q'])) ? $_GET['q'] : '';
$url = rtrim($url, '/');
$urls = explode('/', $url);

$route = $urls[0];
$url_data  = array_splice($urls, 1);

include_once "routes/$route.php";

/* Show results */
var_export(
    json_encode(route($method, $url_data, $data))
);