<?php
/*
 * @ https://hospedagem.matrixch.store 
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 17/06/2024
*/

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
error_reporting(32767);
$db = new SQLite3("./.db.db");
$query = "SELECT image_url FROM ads";
$results = $db->query($query);
$imageUrls = [];
while ($row = $results->fetchArray(SQLITE3_ASSOC)) {
    $imageUrls[] = $row["image_url"];
}
$html = "<html><head><style>body {margin: 0; background-color: transparent;}\n.slideshow-container {position: relative; width: 100%; height: 100%; background-color: transparent;}\n.slideshow-image {position: absolute; top: 0; left: 0; opacity: 0; transition: opacity 0.5s ease; width: 100%; height: 100%; object-fit: fill;}\n.slideshow-image.active {opacity: 1;}\n.indicator-container {position: absolute; bottom: 10px; left: 50%; transform: translateX(-50%); display: flex; justify-content: center; align-items: center;}\n.indicator {width: 10px; height: 10px; border-radius: 50%; background-color: gray; margin: 0 5px;}\n.indicator.active {background-color: white;}</style></head><body>";
$html .= "<div class=\"slideshow-container\">";
foreach ($imageUrls as $index => $imageUrl) {
    $html .= "<img class=\"slideshow-image" . ($index === 0 ? " active" : "") . "\" src=\"" . $imageUrl . "\">";
}
$html .= "<div class=\"indicator-container\">";
foreach ($imageUrls as $index => $imageUrl) {
    $html .= "<div class=\"indicator" . ($index === 0 ? " active" : "") . "\"></div>";
}
$html .= "</div>";
$html .= "</div>";
$html .= "<script>\n    var slideshowImages = Array.from(document.getElementsByClassName(\"slideshow-image\"));\n    var indicators = Array.from(document.getElementsByClassName(\"indicator\"));\n    var currentSlide = 0;\n    var transitionInterval = 4000; // Time in milliseconds, change as needed\n\n    setInterval(function() {\n        currentSlide = (currentSlide + 1) % slideshowImages.length;\n        updateSlide(currentSlide);\n    }, transitionInterval);\n\n    function updateSlide(slideIndex) {\n        slideshowImages.forEach(function(image) {\n            image.style.opacity = \"0\";\n            image.style.pointerEvents = \"none\";\n        });\n\n        indicators.forEach(function(indicator) {\n            indicator.classList.remove(\"active\");\n        });\n\n        slideshowImages[slideIndex].style.opacity = \"1\";\n        slideshowImages[slideIndex].style.pointerEvents = \"auto\";\n        indicators[slideIndex].classList.add(\"active\");\n    }\n</script>";
$html .= "</body></html>";
echo $html;

?>