<?php
require_once 'config.php';
require_once 'security_headers.php';

$coinId = intval($_GET['coin'] ?? 0);
$photoIndex = intval($_GET['photo'] ?? 0);

$coins = getCoins();
$totalCoins = count($coins);

if ($coinId < 0 || $coinId >= $totalCoins) {
    header('Location: index.php');
    exit;
}

$coinImages = $coins[$coinId];
$photosPerCoin = count($coinImages);

// Calculer navigation
$prevPhoto = null;
$nextPhoto = null;

if ($photoIndex > 0) {
    // Photo précédente de la même pièce
    $prevPhoto = ['coin' => $coinId, 'photo' => $photoIndex - 1];
} elseif ($coinId > 0) {
    // Dernière photo de la pièce précédente
    $prevPhoto = ['coin' => $coinId - 1, 'photo' => count($coins[$coinId - 1]) - 1];
}

if ($photoIndex < $photosPerCoin - 1) {
    // Photo suivante de la même pièce
    $nextPhoto = ['coin' => $coinId, 'photo' => $photoIndex + 1];
} elseif ($coinId < $totalCoins - 1) {
    // Première photo de la pièce suivante
    $nextPhoto = ['coin' => $coinId + 1, 'photo' => 0];
}

$currentImage = $coinImages[$photoIndex];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pièce #<?= $coinId + 1 ?> - <?= $photoIndex === 0 ? 'Face' : 'Pile' ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: #000;
            color: #fff;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .lightbox {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .lightbox img {
            max-width: 95%;
            max-height: 95vh;
            object-fit: contain;
        }
        .close {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 36px;
            color: #fff;
            text-decoration: none;
            background: rgba(0,0,0,0.7);
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            cursor: pointer;
        }
        .close:hover { background: rgba(0,0,0,0.9); }
        .nav-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            font-size: 48px;
            color: #fff;
            background: rgba(0,0,0,0.5);
            padding: 20px 30px;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            user-select: none;
        }
        .nav-arrow:hover { background: rgba(0,0,0,0.8); }
        .prev { left: 20px; }
        .next { right: 20px; }
        .info {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.7);
            padding: 10px 20px;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="lightbox">
        <a href="coin.php?id=<?= $coinId ?>" class="close">×</a>

        <img src="<?= PICTURES_URL . '/' . $currentImage ?>" alt="Pièce <?= $coinId + 1 ?>">

        <?php if ($prevPhoto): ?>
            <a href="lightbox.php?coin=<?= $prevPhoto['coin'] ?>&photo=<?= $prevPhoto['photo'] ?>" class="nav-arrow prev">‹</a>
        <?php endif; ?>

        <?php if ($nextPhoto): ?>
            <a href="lightbox.php?coin=<?= $nextPhoto['coin'] ?>&photo=<?= $nextPhoto['photo'] ?>" class="nav-arrow next">›</a>
        <?php endif; ?>

        <div class="info">
            Pièce <?= $coinId + 1 ?> / <?= $totalCoins ?> - <?= $photoIndex === 0 ? 'Face' : 'Pile' ?>
        </div>
    </div>

    <script>
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                window.location.href = 'coin.php?id=<?= $coinId ?>';
            }
            <?php if ($prevPhoto): ?>
            else if (e.key === 'ArrowLeft') {
                window.location.href = 'lightbox.php?coin=<?= $prevPhoto['coin'] ?>&photo=<?= $prevPhoto['photo'] ?>';
            }
            <?php endif; ?>
            <?php if ($nextPhoto): ?>
            else if (e.key === 'ArrowRight') {
                window.location.href = 'lightbox.php?coin=<?= $nextPhoto['coin'] ?>&photo=<?= $nextPhoto['photo'] ?>';
            }
            <?php endif; ?>
        });
    </script>
</body>
</html>
