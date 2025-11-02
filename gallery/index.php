<?php
require_once 'config.php';
$coins = getCoins();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galerie de Pièces</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #1a1a1a; color: #fff; padding: 20px; }
        h1 { text-align: center; margin-bottom: 30px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
        .coin-card { background: #2a2a2a; border-radius: 8px; overflow: hidden; cursor: pointer; transition: transform 0.2s; }
        .coin-card:hover { transform: scale(1.05); }
        .coin-card img { width: 100%; height: 200px; object-fit: cover; }
        .coin-info { padding: 10px; }
        .coin-info h3 { font-size: 14px; color: #999; }
    </style>
</head>
<body>
    <h1>Galerie de Pièces</h1>
    <div class="grid">
        <?php foreach ($coins as $index => $coinImages): ?>
            <a href="coin.php?id=<?= $index ?>" class="coin-card">
                <img src="<?= PICTURES_URL . '/' . $coinImages[0] ?>" alt="Pièce <?= $index + 1 ?>">
                <div class="coin-info">
                    <h3>Pièce #<?= $index + 1 ?></h3>
                    <p id="legend-<?= $index ?>"><!-- Légende à venir --></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</body>
</html>
