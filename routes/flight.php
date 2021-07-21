<?php

function route($method, $url_data, $data) {

    if ($method == 'GET') return search_flights($data);
    else header('HTTP/1.1 405 Method not allowed');

}

function search_flights($data) {

    include 'Core/connect.php';

    /* Validate data */
    $validator = new Validator();

    $validator->validate($data, [
        'from'  => ['required', 'exists:airports:iata'],
        'to'    => ['required', 'exists:airports:iata'],
        'date1' => ['required', 'date:Y-m-d'],
        'date2' => ['date:Y-m-d'],
        'passengers' => ['required', 'min:1', 'max:8']
    ]);

    if (empty($validator->errors)) {

        /* Search airports */
        $from_airport = $pdo->query("SELECT * FROM `airports` WHERE `iata` = '".$data['from']."' LIMIT 1");
        $from_airport = $from_airport->fetch(PDO::FETCH_ASSOC);

        $to_airport = $pdo->query("SELECT * FROM `airports` WHERE `iata` = '".$data['to']."' LIMIT 1");
        $to_airport = $to_airport->fetch(PDO::FETCH_ASSOC);

        /* Search flights */
        $flights_to = $pdo->query("SELECT * FROM `flights` WHERE `from_id` = '".$from_airport['id']."' AND `to_id` = '".$to_airport['id']."'");
        $flights_to = $flights_to->fetchAll(PDO::FETCH_ASSOC);

        $flights_back = $pdo->query("SELECT * FROM `flights` WHERE `from_id` = '".$to_airport['id']."' AND `to_id` = '".$from_airport['id']."'");
        $flights_back = ($validator->required($data['date2'])) ? $flights_back->fetchAll(PDO::FETCH_ASSOC) : [];

        /* Modify flights_to array */
        foreach ($flights_to as $i => $flight) {

            $flights_to[$i] = [
                'flight_id'         => $flight['id'],
                'flight_code'       => $flight['flight_code'],
                'from'              => [
                    'city'      => $from_airport['city'],
                    'airport'   => $from_airport['name'],
                    'iata'      => $from_airport['iata'],
                    'date'      => $data['date1'],
                    'time'      => $flight['time_from']
                ],
                'to'                => [
                    'city'      => $to_airport['city'],
                    'airport'   => $to_airport['name'],
                    'iata'      => $to_airport['iata'],
                    'date'      => $data['date1'],
                    'time'      => $flight['time_to']
                ],
                'cost'              => $flight['cost'],
                'availability'      => 156
            ];

        }

        /* Modify flights_back array */
        foreach ($flights_back as $i => $flight) {

            $flights_back[$i] = [
                'flight_id'         => $flight['id'],
                'flight_code'       => $flight['flight_code'],
                'from'              => [
                    'city'      => $to_airport['city'],
                    'airport'   => $to_airport['name'],
                    'iata'      => $to_airport['iata'],
                    'date'      => $data['date2'],
                    'time'      => $flight['time_from']
                ],
                'to'                => [
                    'city'      => $from_airport['city'],
                    'airport'   => $from_airport['name'],
                    'iata'      => $from_airport['iata'],
                    'date'      => $data['date2'],
                    'time'      => $flight['time_to']
                ],
                'cost'              => $flight['cost'],
                'availability'      => 156
            ];

        }

        header('HTTP/1.1 200 OK');
        return [
            'data' => [
                'flights_to' => $flights_to,
                'flights_back' => $flights_back
            ]
        ];

    }


}