<?php
/*
 * @ https://hospedagem.matrixch.store 
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 17/06/2024
*/

ini_set("display_errors", 0);
include "includes/header.php";
echo "<style>\r\n    .custom-button {\r\n        padding: 10px 20px;\r\n    }\r\n\r\n    #url-form {\r\n        display: none;\r\n    }\r\n\r\n    .custom-input {\r\n        color: blue;\r\n    }\r\n\r\n</style>\r\n\r\n\r\n<div class=\"col-md-6 mx-auto\">\r\n    <div class=\"modal fade\" id=\"how2\" tabindex=\"-1\" aria-labelledby=\"exampleModalLabel\" aria-hidden=\"true\">\r\n        <div class=\"modal-dialog\">\r\n            <div class=\"modal-content\">\r\n                <div class=\"modal-header\">\r\n\r\n                </div>\r\n                <div class=\"modal-body\">\r\n                </div>\r\n                <div class=\"modal-footer\">\r\n                    <button type=\"button\" class=\"btn btn-secondary\" data-dismiss=\"modal\">Close</button>\r\n                    <a href=\"https://www.tvsportguide.com/page/widget/\"><button type=\"button\" class=\"btn btn-primary\">Go to webpage</button></a>\r\n                </div>\r\n            </div>\r\n        </div>\r\n    </div>\r\n    <div class=\"card-body\">\r\n        <div class=\"card ctcard\">\r\n            <div class=\"card-header\">\r\n                <center>\r\n                    <h2><i class=\"fa fa-file-image-o\"></i> Advert's Settings</h2>\r\n                </center>\r\n            </div>\r\n            <div class=\"card-body\">\r\n\r\n                ";
$db = new SQLite3("./api/.db_ads.db");
if (!$db) {
    exit("Database connection error.");
}
$query = "CREATE TABLE IF NOT EXISTS adsstatus (id INTEGER PRIMARY KEY, adstype TEXT)";
if (!$db->exec($query)) {
    echo "Error creating table: " . $db->lastErrorMsg() . "<br>";
}
$query = "SELECT COUNT(*) FROM adsstatus";
$result = $db->querySingle($query);
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ad_item = $_POST["ad_item"];
    if (empty($ad_item)) {
        exit("Please select an ad item.");
    }
    if ($result === 0) {
        $updateQuery = "INSERT INTO adsstatus (adstype) VALUES (:ad_item)";
    } else {
        $updateQuery = "UPDATE adsstatus SET adstype = :ad_item WHERE id = 1";
    }
    $stmt = $db->prepare($updateQuery);
    $stmt->bindValue(":ad_item", $ad_item, SQLITE3_TEXT);
    if ($stmt->execute()) {
        echo "Ad item '" . $ad_item . "' has been successfully updated.<br>";
        if ($ad_item === "Manualads") {
            $source = "./Manualads/allads.php";
            $destination = "./api/allads.php";
            if (file_exists($source)) {
                copy($source, $destination);
            } else {
                echo "Error: File 'allads.php' not found in 'Manualads' folder.<br>";
            }
        } else {
            if ($ad_item === "Autoads") {
                $source = "./Autoads/allads.php";
                $destination = "./api/allads.php";
                if (file_exists($source)) {
                    copy($source, $destination);
                } else {
                    echo "Error: File 'allads.php' not found in 'Autoads' folder.<br>";
                }
            }
        }
    } else {
        echo "Error updating record: " . $db->lastErrorMsg();
    }
}
echo "\r\n                <form method=\"POST\" action=\"\">\r\n                    <div class=\"ctinput\">\r\n                        <label for=\"ad_item\">Select an Ad Item:</label>\r\n                        <select name=\"ad_item\" id=\"ad_item\">\r\n                            <option value=\"Autoads\">Auto ads</option>\r\n                            <option value=\"Manualads\">Manual ads</option>\r\n                        </select>\r\n                    </div>\r\n\r\n                    <input type=\"submit\" name=\"submit\" value=\"Update\" class=\"ctbtn ctuserbtn\">\r\n                </form>\r\n\r\n                ";
$db->close();
echo "\r\n            </div>\r\n        </div>\r\n    </div>\r\n</div>\r\n\r\n";
include "includes/footer.php";
echo "\r\n</body>\r\n\r\n</html>\r\n";

?>