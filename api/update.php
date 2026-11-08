<?php
/*
 * @ https://hospedagem.matrixch.store 
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 17/06/2024
*/

ini_set("display_errors", 0);
include __DIR__ . "/../includes/functions.php";
$jsonIn = file_get_contents("php://input");
$resonse = json_decode($jsonIn, true);
$decoded = base64_decode($resonse["data"]);
$playlistData = json_decode($decoded, true);
$macAddress = $playlistData["mac_address"];
$macAddress = strtoupper($macAddress);
$pin = $playlistData["parent_control"];
if ($pin) {
    $result = $db->select("playlist", "*", "mac_address = :mac_address", "", [":mac_address" => $macAddress]);
    if (!empty($result)) {
        if ($result[0]["pin"] == $pin) {
            echo "{\"status\":true,\"message\":\"Parental Pin Set\"}";
        } else {
            $data = ["pin" => $pin];
            $db->update("playlist", $data, "mac_address = :mac_address", [":mac_address" => $macAddress]);
            echo "{\"status\":true,\"message\":\"Parental Pin updated Successfully\"}";
        }
    }
} else {
    $newURL = $playlistData["playlist_url"];
    $dnsId = $playlistData["playlist_id"];
    $playlistName = $playlistData["playlist_name"];
    $urlParts = parse_url($newURL, PHP_URL_QUERY);
    parse_str($urlParts, $parsed);
    $username = $parsed["username"];
    $password = $parsed["password"];
    $result = $db->select("playlist", "*", "dns_id = :dns_id AND mac_address = :mac_address", "", [":dns_id" => $dnsId, ":mac_address" => $macAddress]);
    if (!empty($result)) {
        $data = ["username" => $username, "password" => $password];
        $db->update("playlist", $data, "dns_id = :dns_id AND mac_address = :mac_address", [":dns_id" => $dnsId, ":mac_address" => $macAddress]);
        $response = ["success" => 1, "id" => $dnsId, "name" => $playlistName, "url" => $newURL];
        echo json_encode($response);
    } else {
        echo ["success" => 0];
    }
}

?>