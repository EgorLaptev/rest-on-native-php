<?php

function route($method, $url_data, $data) {

    if ($method == 'POST') return login($data);
    else header('HTTP/1.1 405 Method Not Allowed');

}

function login($data) {

    include 'Core/connect.php';

    /* Validate data */
    $validator = new Validator();

    $validator->validate($data, [
        'phone'             => ['required', 'string'],
        'password'          => ['required', 'string']
    ]);

    if (empty($validator->errors)) {

        /* Search user with so phone & password */
        $sql = "SELECT api_token FROM `users` WHERE `phone` = '".$validator->valid['phone']."' AND `password` = '".$validator->valid['password']."'";

        $resp = $pdo->query($sql);
        $user = $resp->fetch();

        /* Return token */
        if ($user) {
            header('HTTP/1.1 200 OK');
            return [
                'data' => [
                    'token' => $user['api_token']
                ]
            ];
        }

        header('HTTP/1.1 401 Unauthorized');
        return [
            "error" => [
                "code" => 401,
                "message" => "Unauthorized",
                "errors" => [ "phone" => [ "phone or password incorrect" ] ]
            ]
        ];

    }

}