<?php

function route($method, $url_data, $data) {

    if ($method == 'POST') return register($data);
    else header('HTTP/1.1 405 Method Not Allowed');

}

function register($data) {

    include 'Core/connect.php';

    /* Validation */
    $validator = new Validator();

    $validator->validate($data, [
        'first_name'        => ['required', 'string'],
        'last_name'         => ['required', 'string'],
        'phone'             => ['required', 'string', 'unique:users'],
        'document_number'   => ['required', 'string', 'length:10'],
        'password'          => ['required', 'string']
    ]);

    /* Register new user */
    if (empty($validator->errors)) {

        $query = $pdo->prepare("INSERT INTO `users` (`first_name`, `last_name`, `phone`, `document_number`, `password`, `api_token`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $query->execute([
            $validator->valid['first_name'],
            $validator->valid['last_name'],
            $validator->valid['phone'],
            $validator->valid['document_number'],
            $validator->valid['password'],
            bin2hex(random_bytes(10)),
            date("Y-m-d H:i:s"),
            date("Y-m-d H:i:s")
        ]);

        header('HTTP/1.1 204 Created');
        return null;

    }

}