<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>flix-play.com/apks</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            position: relative;
            height: 100vh;
            background-color: #222;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .poster-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        .poster {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
            opacity: 0;
            transform: translateX(100%);
            transition: opacity 3s ease-in-out, transform 3s ease-in-out;
        }

        .poster.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .poster.previous {
            opacity: 0;
            transform: translateX(-100%);
        }

        /* Rodapé esfumado */
        .poster::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 40px;
            background: linear-gradient(to top, rgba(255, 255, 255, 0.5), rgba(34, 34, 34, 0));
            z-index: 1;
        }
    </style>
</head>
<body>
    <div class="poster-container">
        <img id="poster1" class="poster">
        <img id="poster2" class="poster">
    </div>

    <script>
        const apiKey = '6b8e3eaa1a03ebb45642e9531d8a76d2';
        let currentIndex = 0;
        let movieIds = [];
        let isPoster1Visible = true;

        async function fetchPopularMovieIds() {
            try {
                const response = await fetch(`https://api.themoviedb.org/3/discover/movie?api_key=${apiKey}&sort_by=popularity.desc&language=pt`);
                if (!response.ok) {
                    throw new Error('Failed to fetch popular movies');
                }
                const data = await response.json();
                movieIds = data.results.map(movie => movie.id);
            } catch (error) {
                console.error('Error fetching popular movies:', error);
            }
        }

        async function fetchMoviePoster(movieId) {
            try {
                const response = await fetch(`https://api.themoviedb.org/3/movie/${movieId}?api_key=${apiKey}&language=pt`);
                if (!response.ok) {
                    throw new Error('Failed to fetch movie details');
                }
                const data = await response.json();
                const posterPath = data.poster_path;
                return posterPath ? `https://image.tmdb.org/t/p/original${posterPath}` : null;
            } catch (error) {
                console.error('Error fetching movie poster:', error);
                return null;
            }
        }

        async function updatePoster() {
            if (movieIds.length === 0) {
                console.error('No movie IDs available.');
                return;
            }

            const movieId = movieIds[currentIndex];
            const poster1 = document.getElementById('poster1');
            const poster2 = document.getElementById('poster2');

            const currentPoster = isPoster1Visible ? poster1 : poster2;
            const nextPoster = isPoster1Visible ? poster2 : poster1;

            const posterUrl = await fetchMoviePoster(movieId);
            if (posterUrl) {
                nextPoster.src = posterUrl;
            }

            currentPoster.classList.remove('visible');
            nextPoster.classList.add('visible');

            currentIndex = (currentIndex + 1) % movieIds.length;
            isPoster1Visible = !isPoster1Visible;
        }

        fetchPopularMovieIds().then(() => {
            setInterval(updatePoster, 9000); // Troca a cada 9 segundos
            updatePoster(); // Primeira atualização
        });
    </script>
</body>
</html>

