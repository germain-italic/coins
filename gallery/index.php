<?php
require_once 'config.php';
require_once 'security_headers.php';

// Récupérer tous les coins et métadonnées
$coins = getCoins();
$allMetadata = getMetadata();

// Extraire les valeurs uniques pour les filtres
$countries = [];
$currencies = [];
$years = [];

foreach ($allMetadata as $meta) {
    if (isset($meta['country']) && $meta['country']) {
        $countries[$meta['country']] = true;
    }
    if (isset($meta['currency']) && $meta['currency']) {
        $currencies[$meta['currency']] = true;
    }
    if (isset($meta['year']) && $meta['year']) {
        $years[$meta['year']] = true;
    }
}

$countries = array_keys($countries);
$currencies = array_keys($currencies);
$years = array_keys($years);

sort($countries);
sort($currencies);
rsort($years); // Années décroissantes

// Récupérer et valider les filtres actifs
$filterCountry = '';
$filterCurrency = '';
$filterYear = '';

if (isset($_GET['country']) && in_array($_GET['country'], $countries, true)) {
    $filterCountry = $_GET['country'];
}
if (isset($_GET['currency']) && in_array($_GET['currency'], $currencies, true)) {
    $filterCurrency = $_GET['currency'];
}
if (isset($_GET['year']) && in_array($_GET['year'], $years, true)) {
    $filterYear = $_GET['year'];
}
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
        h1 { text-align: center; margin-bottom: 20px; }

        .filters {
            max-width: 1200px;
            margin: 0 auto 30px;
            padding: 20px;
            background: #2a2a2a;
            border-radius: 8px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filters label { font-size: 14px; color: #999; margin-right: 5px; }
        .filters select {
            padding: 8px 12px;
            background: #3a3a3a;
            color: #fff;
            border: 1px solid #4a4a4a;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .filters select:hover { background: #4a4a4a; }
        .filters button {
            padding: 8px 16px;
            background: #555;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .filters button:hover { background: #666; }
        .filter-count { margin-left: auto; color: #999; font-size: 14px; }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; max-width: 1200px; margin: 0 auto; }
        .coin-card { background: #2a2a2a; border-radius: 8px; overflow: hidden; cursor: pointer; transition: transform 0.2s; text-decoration: none; color: inherit; }
        .coin-card:hover { transform: scale(1.05); }
        .coin-card img { width: 100%; height: 200px; object-fit: cover; }
        .coin-info { padding: 10px; }
        .coin-info h3 { font-size: 14px; color: #999; }
        .coin-info p { font-size: 12px; color: #777; margin-top: 5px; }
        .no-results { text-align: center; color: #999; padding: 40px; }
    </style>
</head>
<body>
    <h1>Galerie de Pièces</h1>

    <div class="filters">
        <div>
            <label for="country">Pays:</label>
            <select id="country" name="country">
                <option value="">Tous</option>
                <?php foreach ($countries as $country): ?>
                    <option value="<?= htmlspecialchars($country) ?>" <?= $filterCountry === $country ? 'selected' : '' ?>>
                        <?= htmlspecialchars($country) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="currency">Monnaie:</label>
            <select id="currency" name="currency">
                <option value="">Toutes</option>
                <?php foreach ($currencies as $currency): ?>
                    <option value="<?= htmlspecialchars($currency) ?>" <?= $filterCurrency === $currency ? 'selected' : '' ?>>
                        <?= htmlspecialchars($currency) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="year">Année:</label>
            <select id="year" name="year">
                <option value="">Toutes</option>
                <?php foreach ($years as $year): ?>
                    <option value="<?= htmlspecialchars($year) ?>" <?= $filterYear === $year ? 'selected' : '' ?>>
                        <?= htmlspecialchars($year) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <button onclick="resetFilters()">Réinitialiser</button>

        <span class="filter-count" id="filterCount"></span>
    </div>

    <div class="grid" id="coinGrid">
        <?php
        $displayedCount = 0;
        foreach ($coins as $index => $coinImages):
            $meta = getCoinMetadata($index);

            // Appliquer les filtres
            $show = true;
            if ($filterCountry && (!$meta || ($meta['country'] ?? '') !== $filterCountry)) {
                $show = false;
            }
            if ($filterCurrency && (!$meta || ($meta['currency'] ?? '') !== $filterCurrency)) {
                $show = false;
            }
            if ($filterYear && (!$meta || ($meta['year'] ?? '') !== $filterYear)) {
                $show = false;
            }

            if (!$show) continue;
            $displayedCount++;

            $label = $meta ? "{$meta['country']} - {$meta['value']}" : "Pièce #" . ($index + 1);
        ?>
            <a href="coin.php?id=<?= $index ?>" class="coin-card">
                <img src="<?= PICTURES_URL . '/' . $coinImages[0] ?>" alt="<?= htmlspecialchars($label) ?>">
                <div class="coin-info">
                    <h3><?= htmlspecialchars($label) ?></h3>
                    <?php if ($meta && $meta['year']): ?>
                        <p><?= htmlspecialchars($meta['year']) ?></p>
                    <?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>

        <?php if ($displayedCount === 0): ?>
            <div class="no-results">Aucune pièce ne correspond aux filtres sélectionnés.</div>
        <?php endif; ?>
    </div>

    <script>
        function updateFilters() {
            const country = document.getElementById('country').value;
            const currency = document.getElementById('currency').value;
            const year = document.getElementById('year').value;

            const params = new URLSearchParams();
            if (country) params.set('country', country);
            if (currency) params.set('currency', currency);
            if (year) params.set('year', year);

            const url = params.toString() ? '?' + params.toString() : 'index.php';
            window.location.href = url;
        }

        function resetFilters() {
            window.location.href = 'index.php';
        }

        document.getElementById('country').addEventListener('change', updateFilters);
        document.getElementById('currency').addEventListener('change', updateFilters);
        document.getElementById('year').addEventListener('change', updateFilters);

        // Afficher le nombre de résultats
        const count = <?= $displayedCount ?>;
        const total = <?= count($coins) ?>;
        const countText = count === total
            ? `${total} pièce${total > 1 ? 's' : ''}`
            : `${count} / ${total} pièce${total > 1 ? 's' : ''}`;
        document.getElementById('filterCount').textContent = countText;
    </script>
</body>
</html>
