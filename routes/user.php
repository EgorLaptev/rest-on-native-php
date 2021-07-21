<?php

function route($method, $url_data, $data) {

    if ($method === 'GET' && !$url_data[0]) return get_user_info();
    else if ($method === 'GET' && $url_data[0]) return get_user_bookings();

    header('HTTP/1.1 405 Method Not Allowed');

}

function get_user_bookings() {

    include 'Core/connect.php';

    $items = [];
    $cost  = 0;

    $authorization_header = getallheaders()['Authorization'];
    $bearer_token = explode(' ', $authorization_header)[1];

    $user = $pdo->query("SELECT * FROM `users` WHERE `api_token` = '$bearer_token' LIMIT 1");
    $user = $user->fetch(PDO::FETCH_ASSOC);

    if ($user) {

        /* Search passengers */
        $user_bookings_id = $pdo->query("SELECT booking_id FROM `passengers` WHERE `document_number` = '".$user['document_number']."'");
        $user_bookings_id = $user_bookings_id->fetchAll(PDO::FETCH_ASSOC);

        /* Search bookings */
        $bookings = [];

        foreach ($user_bookings_id as  $user_booking_id) {
            $bookings[] = $pdo->query("SELECT * FROM `bookings` WHERE `id` = '".$user_booking_id['booking_id']."'")->fetch(PDO::FETCH_ASSOC);
        }

        /* Search flights */
        $flights = [];

        foreach ($bookings as $booking) {

            $booking_flights = $pdo->query("SELECT * FROM `flights` WHERE `id` IN (".$booking['flight_from'].", ".$booking['flight_back'].")")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($booking_flights as $booking_flight) {

                $airport_from = $pdo->query("SELECT * FROM `airports` WHERE `id` = '".$booking_flight['from_id']."'")->fetch(PDO::FETCH_ASSOC);
                $airport_to   = $pdo->query("SELECT * FROM `airports` WHERE `id` = '".$booking_flight['to_id']."'")->fetch(PDO::FETCH_ASSOC);

                $flights[] = [
                    'flight_id' => $booking_flight['id'],
                    'flight_code' => $booking_flight['flight_code'],
                    'from' => [
                        'city' => $airport_from['city'],
                        'name' => $airport_from['name'],
                        'iata' => $airport_from['iata'],
                        'date' => date('Y-m-d H:i:s'),
                        'time' => $booking_flight['time_from'],
                    ],
                    'to' => [
                        'city' => $airport_to['city'],
                        'name' => $airport_to['name'],
                        'iata' => $airport_to['iata'],
                        'date' => date('Y-m-d H:i:s'),
                        'time' => $booking_flight['time_to'],
                    ],
                    'cost' => $booking_flight['cost'],
                    'availability' => 58,
                ];
                $cost += $booking_flight['cost'];
            }

            $passengers = $pdo->query("SELECT id, first_name, last_name, birth_date, document_number, place_from, place_back FROM `passengers` WHERE `booking_id` = '".$booking['id']."'")->fetchAll(PDO::FETCH_ASSOC);

            $cost *= count($passengers);

            $items[] = [
                'code' => $booking['code'],
                'cost' => $cost,
                'flights' => $flights,
                'passengers' => $passengers
            ];

        }


        return [
            'data' => [
                'items' => $items,
            ]
        ];

    }

    header('HTTP/1.1 401 Unauthorized');
    return [
        'error' => [
            'code' => 401,
            'message' => 'Unauthorized'
        ]
    ];

}

function get_user_info() {

    include 'Core/connect.php';

    $all_headers = getallheaders();
    $bearer_token = explode(' ', $all_headers['Authorization'])[1];

    $user = $pdo->query("SELECT first_name, last_name, phone, document_number FROM `users` WHERE `api_token` = '$bearer_token' LIMIT 1");
    $user = $user->fetch(PDO::FETCH_ASSOC);

    if ($user) return $user;

    header('HTTP/1.1 401 Unauthorized');
    return [
        'error' => [
            'code' => 401,
            'message' => 'Unauthorized'
        ]
    ];

}