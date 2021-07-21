<?php

function route($method, $url_data, $data) {

    if ($method === 'POST') return create_booking($data);
    else if ($method === 'GET' && !$url_data[1]) return get_booking($url_data);
    else if ($method === 'GET' && $url_data[1]) return get_seats($url_data);
    else if ($method === 'PATCH') return change_seat($data, $url_data);

    header('HTTP/1.1 405 Method Not Allowed');

}

function create_booking($data) {

    include 'Core/connect.php';

    $data = json_decode(file_get_contents('php://input'), true);

    $validator = new Validator();

    /* Validate data */
    foreach ( $data['passengers'] as $passenger ) {

        $validator->validate($passenger, [
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'birth_date' => ['required', 'date:Y-m-d'],
            'document_number' => ['required', 'string', 'length:10']
        ]);

    }

    if (empty($validator->errors)) {

        /* Generate code */
        $booking_code = '';

        for ($i=0;$i<5;$i++)
            $booking_code .= 'QWERTYUIOPASDFGHJKLZXCVBNM'[rand(0, 25)];


        /* Adding new booking */
        $query = $pdo->prepare("INSERT INTO `bookings` (`flight_from`, `flight_back`, `date_from`, `date_back`, `code`, `created_at`, `updated_at`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $query = $query->execute([
            $data['flight_from']['id'],
            $data['flight_back']['id'],
            $data['flight_from']['date'],
            $data['flight_back']['date'],
            $booking_code,
            date("Y-m-d H:i:s"),
            date("Y-m-d H:i:s")
        ]);

        /* Adding passengers */
        if ($query) {

            $booking_id = $pdo->query("SELECT id FROM `bookings` WHERE `code` = '$booking_code' LIMIT 1")->fetch(PDO::FETCH_ASSOC)['id'];

            foreach ($data['passengers'] as $passenger) {

                $passenger_query = $pdo->prepare("INSERT INTO `passengers` (`booking_id`, `first_name`, `last_name`, `birth_date`, `document_number`, `place_from`, `place_back`, `created_at`, `updated_at`) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $passenger_query = $passenger_query->execute([
                    $booking_id,
                    $passenger['first_name'],
                    $passenger['last_name'],
                    $passenger['birth_date'],
                    $passenger['document_number'],
                    NULL,
                    NULL,
                    date('Y-m-d H:i:s'),
                    date('Y-m-d H:i:s')
                ]);

            }

        }

        return [ 'data' => [ 'code' => $booking_code ] ];

    }

}

function get_booking($url_data) {

    include 'Core/connect.php';

    $booking_code = $url_data[0];

    /* Search booking */
    $resp = $pdo->prepare("SELECT * FROM `bookings` WHERE `code` = ? LIMIT 1");
    $resp->execute([ $booking_code ]);
    $booking = $resp->fetch(PDO::FETCH_ASSOC);

    /* Search passengers */
    $passengers = $pdo->query("SELECT id, first_name, last_name, birth_date, document_number, place_from, place_back FROM `passengers` WHERE `booking_id` = '".$booking['id']."'")->fetchAll(PDO::FETCH_ASSOC);

    /* Search flights */
    $flight_from = $pdo->query("SELECT * FROM `flights` WHERE `id` = '".$booking['flight_from']."' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $flight_back = $pdo->query("SELECT * FROM `flights` WHERE `id` = '".$booking['flight_back']."' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    /* Search airports */
    $airport_from = $pdo->query("SELECT * FROM `airports` WHERE `id` = '".$flight_from['from_id']."' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $airport_to = $pdo->query("SELECT * FROM `airports` WHERE `id` = '".$flight_from['to_id']."' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    /* Modify flights */
    $flight_from = [
        'flight_id' => $flight_from['id'],
        'flight_code' => $flight_from['flight_code'],
        'from' => [
            'city' => $airport_from['city'],
            'airport' => $airport_from['name'],
            'iata' => $airport_from['iata'],
            'date' => date('Y-m-d H:i:s'),
            'time' => $flight_from['time_from']
        ],
        'to' => [
            'city' => $airport_to['city'],
            'airport' => $airport_to['name'],
            'iata' => $airport_to['iata'],
            'date' => date('Y-m-d H:i:s'),
            'time' => $flight_from['time_to']
        ],
        'cont' => $flight_from['cost'],
        'availability' => 56
    ];

    $flight_back = [
        'flight_id' => $flight_back['id'],
        'flight_code' => $flight_back['flight_code'],
        'from' => [
            'city' => $airport_to['city'],
            'airport' => $airport_to['name'],
            'iata' => $airport_to['iata'],
            'date' => date('Y-m-d H:i:s'),
            'time' => $flight_back['time_to']
        ],
        'to' => [
            'city' => $airport_from['city'],
            'airport' => $airport_from['name'],
            'iata' => $airport_from['iata'],
            'date' => date('Y-m-d H:i:s'),
            'time' => $flight_back['time_from']
        ],
        'cont' => $flight_back['cost'],
        'availability' => 56
    ];

    return [
        'data' => [
            'code' => $booking['code'],
            'cost' => '',
            'flights' => [
                $flight_from,
                $flight_back
            ],
            'passengers' => $passengers
        ]
    ];

}

function get_seats($url_data) {

    include 'Core/connect.php';

    $booking_code = $url_data[0];

    /* Search booking */
    $resp = $pdo->prepare("SELECT * FROM `bookings` WHERE `code` = ? LIMIT 1");
    $resp->execute([ $booking_code ]);
    $booking = $resp->fetch(PDO::FETCH_ASSOC);

    /* Search passengers */
    $passengers = $pdo->query("SELECT id, first_name, last_name, birth_date, document_number, place_from, place_back FROM `passengers` WHERE `booking_id` = '".$booking['id']."'")->fetchAll(PDO::FETCH_ASSOC);

    $occupied_from = [];
    $occupied_back = [];

    foreach ($passengers as $passenger) {

        if ( $passenger['place_from'] )
            $occupied_from[] = [
                'passenger_id' => $passenger['id'],
                'place' => $passenger['place_from']
            ];

        if ( $passenger['place_back'] )
            $occupied_back[] = [
                'passenger_id' => $passenger['id'],
                'place' => $passenger['place_back']
            ];

    }

    return [
        'data' => [
            'occupied_from' => $occupied_from,
            'occupied_back' => $occupied_back
        ]
    ];

}

function change_seat($data, $url_data) {

    include 'Core/connect.php';

    /* Getting raw data */
    $data = json_decode(file_get_contents('php://input'), true);

    $passenger_id = $data['passenger'];
    $seat         = $data['seat'];
    $type         = $data['type'];
    $booking_code = $url_data[0];

    /* Search booking */
    $resp = $pdo->prepare("SELECT id FROM `bookings` WHERE `code` = ? LIMIT 1");
    $booking_id = $resp->execute([ $booking_code ])->fetch(PDO::FETCH_ASSOC)['id'];

    /* If seat - occupied */
    $isSeatOccupied = $pdo->query("SELECT * FROM `passengers` WHERE `booking_id` = '$booking_id' AND `place_$type` = '$seat' LIMIT 1")->rowCount();

    if ($isSeatOccupied) {
        header('HTTP/1.1 422 Validation error');
        return [
            'error' => [
                'code' => 422,
                'message' => 'Seat is occupied'
            ]
        ];
    }

    /* If no passenger */
    $passengers = $pdo->query("SELECT * FROM `passengers` WHERE `id` = '$passenger_id' LIMIT 1")->rowCount();

    if ($passengers) {
        header('HTTP/1.1 403 Forbidden');
        return [
            'error' => [
                'code' => 422,
                'message' => 'Passenger does not apply to booking'
            ]
        ];
    }

    /* Update passenger seat */
    $pdo->query("UPDATE `passengers` SET `place_$type` = '$seat' WHERE `id` = '$passenger_id' ");

    /* Search passenger */
    $passenger = $pdo->query("SELECT id, first_name, last_name, birth_date, document_number, place_from, place_back FROM `passengers` WHERE `id` = '$passenger_id' LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    return [ 'data' => $passenger ];

}