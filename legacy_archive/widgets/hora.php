<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Hora Atual</title>
    <style>
        body { margin: 0; padding: 0; background-color: #000; color: #fff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100vh; overflow: hidden; }
        .clock { font-size: 20vw; font-weight: bold; text-shadow: 0 0 20px rgba(0,255,204,0.5); letter-spacing: 5px; }
        .date { font-size: 5vw; color: #aaa; margin-top: -20px; }
    </style>
</head>
<body>
    <div class="clock" id="clock">00:00</div>
    <div class="date" id="date">Carregando...</div>
    <script>
        function updateClock() {
            const now = new Date();
            const h = String(now.getHours()).padStart(2, '0');
            const m = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('clock').textContent = h + ':' + m;
            
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('date').textContent = now.toLocaleDateString('pt-BR', options);
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>
