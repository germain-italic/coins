<?php
require_once 'config.php';

$coinId = intval($_GET['id'] ?? 0);
$coins = getCoins();
$totalCoins = count($coins);

if ($coinId < 0 || $coinId >= $totalCoins) {
    header('Location: index.php');
    exit;
}

$coinImages = $coins[$coinId];
$prevCoin = $coinId > 0 ? $coinId - 1 : null;
$nextCoin = $coinId < $totalCoins - 1 ? $coinId + 1 : null;
$meta = getCoinMetadata($coinId);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pièce #<?= $coinId + 1 ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #1a1a1a; color: #fff; }
        .header { display: flex; justify-content: space-between; align-items: center; padding: 20px; background: #2a2a2a; }
        .header a { color: #fff; text-decoration: none; padding: 10px 20px; background: #3a3a3a; border-radius: 5px; }
        .header a:hover { background: #4a4a4a; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        h1 { margin-bottom: 30px; }
        .photos { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .photo-wrapper { text-align: center; }
        .photo-wrapper img { width: 100%; max-width: 500px; cursor: pointer; border-radius: 8px; transition: transform 0.2s; }
        .photo-wrapper img:hover { transform: scale(1.02); }
        .photo-label { margin-top: 10px; color: #999; }
        .legend { background: #2a2a2a; padding: 20px; border-radius: 8px; }
        .legend h2 { margin-bottom: 15px; font-size: 18px; }
        .legend-item { margin-bottom: 10px; color: #999; }
        .nav-arrows { position: fixed; top: 50%; transform: translateY(-50%); width: 100%; pointer-events: none; }
        .nav-arrows a { pointer-events: all; position: absolute; font-size: 48px; color: #fff; background: rgba(0,0,0,0.5); padding: 20px; text-decoration: none; border-radius: 5px; }
        .nav-arrows a:hover { background: rgba(0,0,0,0.8); }
        .nav-arrows .prev { left: 20px; }
        .nav-arrows .next { right: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <a href="index.php">← Retour à la galerie</a>
        <span>Pièce <?= $coinId + 1 ?> / <?= $totalCoins ?></span>
        <div></div>
    </div>

    <div class="container">
        <h1><?php
            if ($meta) {
                echo htmlspecialchars("{$meta['country']} - {$meta['value']}");
                if ($meta['year']) echo " (" . htmlspecialchars($meta['year']) . ")";
            } else {
                echo "Pièce #" . ($coinId + 1);
            }
        ?></h1>

        <div class="photos">
            <?php foreach ($coinImages as $idx => $img): ?>
                <div class="photo-wrapper">
                    <img src="<?= PICTURES_URL . '/' . $img ?>"
                         alt="<?= $idx === 0 ? 'Face' : 'Pile' ?>"
                         onclick="openLightbox(<?= $coinId ?>, <?= $idx ?>)">
                    <div class="photo-label"><?= $idx === 0 ? 'Face' : 'Pile' ?></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="legend">
            <h2>Informations</h2>
            <div class="legend-item"><strong>Pays:</strong> <span id="country"><?= $meta ? htmlspecialchars($meta['country']) : 'À analyser' ?></span></div>
            <div class="legend-item"><strong>Monnaie:</strong> <span id="currency"><?= $meta ? htmlspecialchars($meta['currency']) : 'À analyser' ?></span></div>
            <div class="legend-item"><strong>Année:</strong> <span id="year"><?= $meta && $meta['year'] ? htmlspecialchars($meta['year']) : 'À analyser' ?></span></div>
            <div class="legend-item"><strong>Valeur:</strong> <span id="value"><?= $meta ? htmlspecialchars($meta['value']) : 'À analyser' ?></span></div>
            <div class="legend-item"><strong>Remarques:</strong> <span id="notes"><?= $meta && $meta['notes'] ? htmlspecialchars($meta['notes']) : 'Aucune' ?></span></div>
        </div>
    </div>

    <div class="nav-arrows">
        <?php if ($prevCoin !== null): ?>
            <a href="coin.php?id=<?= $prevCoin ?>" class="prev">‹</a>
        <?php endif; ?>
        <?php if ($nextCoin !== null): ?>
            <a href="coin.php?id=<?= $nextCoin ?>" class="next">›</a>
        <?php endif; ?>
    </div>

    <script>
        // Navigation clavier entre pièces
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft' && <?= $prevCoin !== null ? $prevCoin : 'null' ?> !== null) {
                window.location.href = 'coin.php?id=<?= $prevCoin ?>';
            } else if (e.key === 'ArrowRight' && <?= $nextCoin !== null ? $nextCoin : 'null' ?> !== null) {
                window.location.href = 'coin.php?id=<?= $nextCoin ?>';
            }
        });

        function openLightbox(coinId, photoIndex) {
            window.location.href = 'lightbox.php?coin=' + coinId + '&photo=' + photoIndex;
        }
    </script>
</body>
</html>
