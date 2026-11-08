<?php
/*
 * @ https://hospedagem.matrixch.store 
 * @ PHP 7.4
 * @ Decoder version: 1.0.2
 * @ Release: 17/06/2024
*/

echo "<!DOCTYPE html>\n<html>\n<head>\n    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n    <title>Movie Slider</title>\n    <link rel=\"stylesheet\" type=\"text/css\" href=\"betstyle.css\">\n</head>\n<body>\n    <div class=\"container\">\n        <!-- Your slider content here -->\n    </div>\n\n    <script>\n    function adjustBackgroundSize() {\n    var container = document.querySelector('.container');\n    var imgAspectRatio = 3840 / 2160; // Aspect ratio of your image\n\n    var containerAspectRatio = container.offsetWidth / container.offsetHeight;\n\n    if (containerAspectRatio > imgAspectRatio) {\n        container.style.backgroundSize = '100% auto';\n    } else {\n        container.style.backgroundSize = 'auto 100%';\n    }\n}\n\n// Initial adjustment\nadjustBackgroundSize();\n\n// Adjust on window resize\nwindow.addEventListener('resize', adjustBackgroundSize);\n    </script>\n\n    <script type=\"text/javascript\" src=\"movies_script.js\"></script>\n</body>\n</html>\n";

?>