<?php

include("../../vendor/autoload.php");

use Zendesk\API\HttpClient as ZendeskAPI;

/**
 * Replace the following with your own.
 */

$subdomain = "subdomain";
$username = "email@example.com";
$token = "6wiIBWbGkBMo1mRDMuVwkw1EPsNkeUj95PIz2akv";

$client = new ZendeskAPI($subdomain);
$client->setAuth('basic', ['username' => $username, 'token' => $token]);

try {
    // Find all helpcenter categories
    $articles = $client->helpCenter->articles()->findAll();

    echo "<pre>";
    print_r($articles);
    echo "</pre>";
} catch (\Zendesk\API\Exceptions\ApiResponseException $e) {
    echo $e->getMessage().'</br>';
}
