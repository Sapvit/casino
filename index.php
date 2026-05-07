<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Выбор игры</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            width: 100%;
        }

        .header {
            text-align: center;
            margin-bottom: 50px;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #f39c12, #e74c3c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header p {
            font-size: 1.1rem;
            color: #aaa;
        }

        .games-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
        }

        .game-card {
            position: relative;
            cursor: pointer;
            overflow: hidden;
            border-radius: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .game-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7);
        }

        .game-card img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.3s ease;
        }

        .game-card:hover img {
            transform: scale(1.05);
        }

        .game-card-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .game-card:hover .game-card-overlay {
            opacity: 1;
        }

        .game-label {
            font-size: 1.8rem;
            font-weight: bold;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.8);
            letter-spacing: 2px;
        }

        .game-card a {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .games-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .header h1 {
                font-size: 1.8rem;
            }

            .game-label {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎰 Выбор игры</h1>
            <p>Нажмите на картинку для выбора игры</p>
        </div>

        <div class="games-grid">
            <div class="game-card">
                <img src="Roulette.jpg" alt="Рулетка">
                <div class="game-card-overlay">
                    <span class="game-label">РУЛЕТКА</span>
                </div>
                <a href="roulette/index.php" title="Играть в рулетку"></a>
            </div>

            <div class="game-card">
                <img src="Poker.jpg" alt="Покер">
                <div class="game-card-overlay">
                    <span class="game-label">ПОКЕР</span>
                </div>
                <a href="poker/index.php" title="Играть в покер"></a>
            </div>
        </div>
    </div>
</body>
</html>
