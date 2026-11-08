<?php
/*
 * @ https://hospedagem.matrixch.store 
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 17/06/2024
*/

$jsonFilePath = "../includes/ad_type.json";
if (file_exists($jsonFilePath) && is_readable($jsonFilePath)) {
    $jsonContent = file_get_contents($jsonFilePath);
    $jsonData = json_decode($jsonContent, true);
    $fileToLoad = $jsonData["adType"] ?? "manual";
    if ($fileToLoad === "manual") {
        include "./manual_ads.php";
    } else {
        if ($fileToLoad === "tmdb") {
            include "./tmdb.php";
        } else {
            echo "No valid ad type found.";
        }
    }
} else {
    echo "Unable to read ad type file.";
}

?>