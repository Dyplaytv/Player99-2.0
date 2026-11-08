<!DOCTYPE html>
<html>
<head>
    <title>ADS3</title>
    <style>
        body {
            margin: 0;
            padding: 0;
        }
        #slideshow {
            position: relative;
            width: 100%;
            height: 100vh; /* Ocupa toda a altura da tela */
            overflow: hidden;
        }
        img, video {
            width: 100%;
            height: 100%;
            object-fit: contain; /* Faz a imagem manter sua proporção original */
            position: absolute;
            top: 0;
            left: 0;
            transition: transform 10s ease-in-out; /* Ajusta a duração da transição do zoom */
        }
        .slide-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: gray;
            border-radius: 50%;
            margin: 5px;
            cursor: pointer;
        }
        .active {
            background-color: white;
        }
    </style>
</head>
<body>
    <div id="slideshow"></div>
    <div id="slide-indicators"></div>

<script>
    var slideshow = document.getElementById("slideshow");

    fetch('ads3.php')
        .then(response => response.json())
        .then(data => {
            var mediaUrls = data.map(obj => obj.AdUrl);
            loadSlides(mediaUrls);
        });

    function getMediaType(url) {
        var extension = url.split('.').pop();
        if (extension == "jpg" || extension == "jpeg" || extension == "png" || extension == "gif") {
            return "image";
        } else if (extension == "mp4") {
            return "video";
        } else {
            return null;
        }
    }

    function loadSlides(mediaUrls) {
        var i;
        for (i = 0; i < mediaUrls.length; i++) {
            var mediaType = getMediaType(mediaUrls[i]);
            var slideElement;
            if (mediaType == "image") {
                slideElement = document.createElement("img");
                slideElement.src = mediaUrls[i];
                slideElement.style.transform = "scale(1)"; // Começa com tamanho normal
            } else if (mediaType == "video") {
                slideElement = document.createElement("video");
                slideElement.src = mediaUrls[i];
                slideElement.autoplay = true;
                slideElement.controls = false;
                slideElement.muted = true;
                slideElement.loop = false;
                slideElement.addEventListener('ended', function () {
                    setTimeout(showSlides, 0); 
                });
            }
            slideshow.appendChild(slideElement);
            slideElement.style.opacity = "0";
        }
        showSlides();
    }

    var slideIndex = 0;

    function showSlides() {
        var i;
        var slides = slideshow.childNodes;
        for (i = 0; i < slides.length; i++) {
            if (slides[i].tagName == "IMG" || slides[i].tagName == "VIDEO") {
                slides[i].style.display = "none";
            }
        }
        slideIndex++;
        if (slideIndex > slides.length) {
            slideIndex = 1;
        }
        if (slides[slideIndex - 1].tagName == "IMG") {
            slides[slideIndex - 1].style.display = "block";
            fadeIn(slides[slideIndex - 1]);
            resetZoom(slides[slideIndex - 1]); // Reseta o zoom antes de aplicar
            startZoom(slides[slideIndex - 1]); // Inicia o efeito de zoom
            setTimeout(showSlides, 8000); 
        } else if (slides[slideIndex - 1].tagName == "VIDEO") {
            slides[slideIndex - 1].style.display = "block";
            fadeIn(slides[slideIndex - 1]);
            slides[slideIndex - 1].play();
        }
    }

    function fadeIn(element) {
        var op = 0.1;
        element.style.opacity = op;
        var timer = setInterval(function () {
            if (op >= 1) {
                clearInterval(timer);
            }
            element.style.opacity = op;
            element.style.filter = 'alpha(opacity=' + op * 100 + ")";
            op += op * 0.1;
        }, 50);
    }

    function startZoom(element) {
        var scale = 1;
        var zoomInterval = setInterval(function() {
            if (scale >= 1.2) { // Limite do zoom
                clearInterval(zoomInterval);
            } else {
                scale += 0.01; // Aumento gradual do zoom
                element.style.transform = "scale(" + scale + ")";
            }
        }, 100); // Intervalo de tempo para aumentar o zoom
    }

    function resetZoom(element) {
        element.style.transform = "scale(1)"; // Reseta o zoom para o tamanho normal
    }
</script>

</body>
</html>
