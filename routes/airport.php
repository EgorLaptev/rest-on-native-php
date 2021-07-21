<?php

function route($method, $url_data, $data) {

    if ($method == 'GET') return search_airport($data);
    else header('HTTP/1.1 405 Method Not Allowed');

}

function search_airport($data) {

    include 'Core/connect.php';

    /* Search airports */
    $query = $pdo->prepare("SELECT name, iata FROM `airports` WHERE `city` REGEXP :city OR `name` REGEXP :name OR `iata` REGEXP :iata");
    $query->bindValue(':city', $data['query'], PDO::PARAM_STR);
    $query->bindValue(':name', $data['query'], PDO::PARAM_STR);
    $query->bindValue(':iata', $data['query'], PDO::PARAM_STR);
    $query->execute();

    $airports = $query->fetchAll(PDO::FETCH_ASSOC);

    return [ 'data' => [ 'items' => $airports ] ];

}