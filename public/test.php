<?php


$url = 'https://tgtest.bazarbay.kz/kalybek/app/public/';
$data = [
    'action' => '/market/bulkedit',
    'token' => 'P9osbM1k36uJDwOoG99G1pp6TPrSXzom'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data)); // Если нужен JSON, см. ниже
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

// Если требуется JSON-формат
// curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
// curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
curl_close($ch);

var_dump($response);