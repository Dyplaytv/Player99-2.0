<?php
/*
 * @ https://hospedagem.matrixch.store 
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 17/06/2024
*/

error_reporting(0);
include "includes/header.php";
$jsonFilePath = "includes/ad_type.json";
$jsonData = json_decode(file_get_contents($jsonFilePath), true);
$currentAdType = $jsonData["adType"] ?? "manual";
$db = new SQLite3("./api/.bet_tmdb.db");
$table_name = "api_key";
$db->exec("CREATE TABLE IF NOT EXISTS " . $table_name . " (id INTEGER PRIMARY KEY, key TEXT)");
$rows = $db->query("SELECT COUNT(*) as count FROM " . $table_name);
$row = $rows->fetchArray();
$numRows = $row["count"];
if ($numRows == 0) {
    $db->exec("INSERT INTO " . $table_name . "(key) VALUES('')");
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST["submit"])) {
        $newKey = $_POST["key"];
        $stmt = $db->prepare("UPDATE " . $table_name . " SET key = :newKey WHERE id = 1");
        $stmt->bindValue(":newKey", $newKey, SQLITE3_TEXT);
        $stmt->execute();
        header("Location: tmdb_api.php");
        exit;
    }
    if (isset($_POST["deleteCache"])) {
        $cacheFilePath = "./api/cache/combined_cache.json";
        if (file_exists($cacheFilePath)) {
            unlink($cacheFilePath);
        }
        header("Location: tmdb_api.php");
        exit;
    }
}
$res = $db->query("SELECT * FROM " . $table_name . " WHERE id = 1");
$rowU = $res->fetchArray();
if (empty($rowU["key"])) {
    echo "<div class=\"alert alert-warning\" role=\"alert\">\n        You must add your tMDB API key before using the app. Follow the instructions below to add your API key.\n    </div>";
}
echo "<link rel=\"stylesheet\" href=\"css/slider.css\">\n        <div class=\"col-md-12 mx-auto\">\n            <div class=\"card-body\">\n                \n                <div class=\"card bg-primary text-white\">\n                    <div class=\"card-header card-header-warning\">\n                                    <div class=\"card-header bg-primary py-3\">\n                                        \n                                        <div id=\"slider-container\" data-ad-type=\"";
echo $currentAdType;
echo "\" style=\"display: flex; align-items: center; justify-content: center;\">\n    <span style=\"margin-right: 20px; font-size: 24px;\">Manual Ads</span> <!-- Increased font size here -->\n    <label class=\"switch\">\n        <input type=\"checkbox\" id=\"pageSlider\" onchange=\"toggleAdType()\">\n        <span class=\"slider round\"></span>\n    </label>\n    <span style=\"margin-left: 20px; font-size: 24px;\">tMDB Ads</span> <!-- And here -->\n</div>\n                            <center>\n                            <h5> Advert Type Selector</h5>\n                            </center>\n                                    </div>\n                                    <div class=\"card-body\">\n                                        <!-- Add instructions here -->\n                                        <p style=\"font-size: 16px;\">\n                                            To use this application, you need to have a tMDB API key. Follow these steps to obtain your API key:\n                                        </p>\n                                        <ol>\n                                            <li>Visit the tMDB website at <a href=\"https://www.themoviedb.org\" target=\"_blank\" style=\"font-weight: bold; color: #007bff; text-decoration: underline;\">https://www.themoviedb.org/</a></li>\n                                            <li>Sign in or create an account if you don't have one.</li>\n                                            <li>To register for an API key, click the <a href=\"https://www.themoviedb.org/settings/api\" target=\"_blank\" style=\"font-weight: bold; color: #007bff; text-decoration: underline;\">API link</a> from within your account settings page.</li>\n\n                                            <li>\n                                                Please note that the API registration process is not optimized for mobile devices so you should access these pages on a desktop computer and browser\n                                            </li>\n                                            <li>Generate a new API key and copy it.</li>\n                                        </ol>\n                                        <p>\n                                            Paste the copied API key into the field below and click the \"Save Settings\" button.\n                                        </p>\n                                        <!-- End of instructions -->\n\n                                        <form method=\"post\">\n                                            <div class=\"form-group mb-3\">\n                                                <label class=\"control-label text-primary\" for=\"key\">\n                                                    <strong>Api key:</strong>\n                                                </label>\n                                                <div class=\"input-group\">\n                                                    <input class=\"form-control\" id=\"key\" name=\"key\" value=\"";
echo $rowU["key"];
echo "\" type=\"text\" />\n                                                </div>\n                                            </div>\n                                            <div align=\"center\" class=\"form-group mb-3\">\n                                                <br>\n                                                <div>\n                                                    <button class=\"btn btn-success\" name=\"submit\" type=\"submit\">\n                                                        <span class=\"icon text-white-50\"><i class=\"fa fa-save\"></i>&nbsp;&nbsp;</span>\n                                                        <span class=\"text\">Save Settings</span>\n                                                    </button>\n                                                </div>\n                                            </div>\n                                        </form>\n                                    </div>\n                                 <!-- Add the \"Delete Cache\" button -->\n                    <form method=\"post\">\n                        <div align=\"center\" class=\"form-group mb-3\">\n                            <button class=\"btn btn-danger\" name=\"deleteCache\" type=\"submit\">\n                                <span class=\"icon text-white-50\"><i class=\"fa fa-trash\"></i>&nbsp;&nbsp;</span>\n                                <span class=\"text\">Delete Cache</span>\n                            </button>\n                        </div>\n                    </form>\n                </div>\n                    <!-- ============================================================== -->\n                    <!-- End Container fluid  -->\n                    <!-- ============================================================== -->\n                </div>\n                <!-- ============================================================== -->\n                <!-- End Page wrapper  -->\n                <!-- ============================================================== -->\n            </div>\n        </div>\n    </div>\n</div>\n<script>\ndocument.addEventListener('DOMContentLoaded', function() {\n    var sliderContainer = document.getElementById('slider-container');\n    var currentAdType = sliderContainer.getAttribute('data-ad-type');\n    var slider = document.getElementById('pageSlider');\n\n    // Set the slider based on the ad type from JSON\n    slider.checked = (currentAdType === 'tmdb');\n\n    slider.addEventListener('change', function() {\n        var adType = this.checked ? 'tmdb' : 'manual';\n        updateAdType(adType);\n    });\n});\n\nfunction updateAdType(adType, isChecked) {\n    var xhr = new XMLHttpRequest();\n    xhr.open('POST', 'ad_type.php', true);\n    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');\n    xhr.onload = function() {\n        // Redirect to the appropriate page based on the slider's position\n        window.location.href = isChecked ? 'tmdb_api.php' : 'ads.php';\n    };\n    xhr.send('adType=' + adType);\n}\n</script>\n";
include "includes/functions.php";

?>