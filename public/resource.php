<?php

declare(strict_types=1);

use Firebase\JWT\JWT;

require_once('../vendor/autoload.php');

// Do some checking for the request method here, if desired.

// Attempt to extract the token from the Bearer header
if (! preg_match('/Bearer\s(\S+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
    header('HTTP/1.0 400 Bad Request');
    echo 'Token not found in request';
    exit;
}

$jwt = $matches[1];

if (! $jwt) {
    // No token was able to be extracted from the authorization header
    header('HTTP/1.0 400 Bad Request');
    exit;
}

$secretKey  = 'secret';
JWT::$leeway += 60;
$token = JWT::decode($jwt, $secretKey, ['HS512']);
$now = new DateTimeImmutable();
$serverName = "example.com";

if ($token->iss !== $serverName ||
    $token->nbf > $now->getTimestamp() ||
    $token->exp < $now->getTimestamp())
{
    header('HTTP/1.1 401 Unauthorized');
    exit;
}

// The token is valid, so send the response back to the user.

$post_string = file_get_contents('php://input');
$post = json_decode( $post_string, true );

$start_date_string = $post["start_date"];

$end_date_string = $post["end_date"];

$currency_id = $post["currency_id"];

$start_date = new DateTime($start_date_string);
$end_date = new DateTime($end_date_string);
$interval = $start_date->diff($end_date);

$fixed_rate = 3;
$trip_length = $interval->days + 1;

$age_array = explode(", ", $post["age"]);
$total = 0;
foreach($age_array as $age){
  $age_load = get_age_load($age);
  $cost = $fixed_rate * $age_load * $trip_length;
  $total += $cost;
}

function get_age_load($age){
  if($age>=18 && $age<=30){
    return 0.6;
  }elseif ($age>=31 && $age<=40) {
    return 0.7;
  }elseif ($age>=41 && $age<=50) {
    return 0.8;
  }elseif ($age>=51 && $age<=60) {
    return 0.9;
  }elseif ($age>=61 && $age<=70) {
    return 1;
  }
}

$data["total"] = ceil($total);
$data["currency_id"] = $currency_id;
$data["quotation_id"] = rand(1, 32000);
$data["trip_length"] = $trip_length;

echo json_encode( $data );
