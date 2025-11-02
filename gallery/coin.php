<?php
require_once 'config.php';
require_once 'security_headers.php';

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
        .search-link { transition: all 0.2s; }
        .search-link:hover { background: #4a4a4a !important; transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.3); }
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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h2 style="margin: 0;">Informations</h2>
                <button id="editBtn" style="padding: 8px 16px; background: #3a3a3a; color: #fff; border: none; border-radius: 5px; cursor: pointer;">✏️ Modifier</button>
            </div>
            <div class="legend-item"><strong>Pays:</strong> <span id="country"><?= $meta ? htmlspecialchars($meta['country']) : 'À analyser' ?></span></div>
            <div class="legend-item"><strong>Monnaie:</strong> <span id="currency"><?= $meta ? htmlspecialchars($meta['currency']) : 'À analyser' ?></span></div>
            <div class="legend-item"><strong>Année:</strong> <span id="year"><?= $meta && $meta['year'] ? htmlspecialchars($meta['year']) : 'À analyser' ?></span></div>
            <div class="legend-item"><strong>Valeur:</strong> <span id="value"><?= $meta ? htmlspecialchars($meta['value']) : 'À analyser' ?></span></div>
            <div class="legend-item"><strong>Remarques:</strong> <span id="notes"><?= $meta && $meta['notes'] ? htmlspecialchars($meta['notes']) : 'Aucune' ?></span></div>

            <?php if ($meta && isset($meta['valuation'])): ?>
                <div style="margin-top: 20px; padding: 15px; background: rgba(76,175,80,0.1); border-left: 3px solid #4caf50; border-radius: 5px;">
                    <h3 style="margin-bottom: 10px; font-size: 16px; color: #4caf50;">💰 Cotation</h3>
                    <div class="legend-item"><strong>Prix:</strong> <?= htmlspecialchars($meta['valuation']['price']) ?> <?= htmlspecialchars($meta['valuation']['currency']) ?></div>
                    <div class="legend-item"><strong>État:</strong> <?= htmlspecialchars($meta['valuation']['condition']) ?></div>
                    <div class="legend-item"><strong>Source:</strong>
                        <a href="<?= htmlspecialchars($meta['valuation']['source_url']) ?>"
                           target="_blank"
                           rel="noopener noreferrer"
                           style="color: #4caf50; text-decoration: none;">
                            <?= htmlspecialchars($meta['valuation']['source_name']) ?> ↗
                        </a>
                    </div>
                    <?php if (isset($meta['valuation']['notes'])): ?>
                        <div class="legend-item"><strong>Notes:</strong> <?= htmlspecialchars($meta['valuation']['notes']) ?></div>
                    <?php endif; ?>
                    <div class="legend-item" style="font-size: 11px; color: #888;">
                        Mise à jour: <?= htmlspecialchars($meta['valuation']['last_updated']) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($meta): ?>
                <?php
                // Construire la requête de recherche
                $searchParts = [];
                if (!empty($meta['value'])) $searchParts[] = $meta['value'];
                if (!empty($meta['year'])) $searchParts[] = $meta['year'];
                if (!empty($meta['country'])) $searchParts[] = $meta['country'];
                if (!empty($meta['currency'])) $searchParts[] = $meta['currency'];
                $searchQuery = implode(' ', $searchParts);
                $encodedQuery = urlencode($searchQuery);

                // URLs de recherche
                $numistaUrl = "https://fr.numista.com/catalogue/index.php?r=" . $encodedQuery . "&cat=y";
                $ebayUrl = "https://www.ebay.fr/sch/i.html?_nkw=" . $encodedQuery . "&_sacat=11116";
                $leboncoinUrl = "https://www.leboncoin.fr/recherche?category=82&text=" . $encodedQuery;

                // Pour Argus2euros, uniquement si c'est une pièce Euro
                $isEuro = (stripos($meta['currency'], 'euro') !== false || stripos($meta['currency'], 'eur') !== false);
                ?>
                <div style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 5px;">
                    <h3 style="margin-bottom: 10px; font-size: 14px; color: #999;">🔍 Rechercher la cotation sur :</h3>
                    <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                        <a href="<?= htmlspecialchars($numistaUrl) ?>"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="search-link"
                           style="padding: 8px 15px; background: #3a3a3a; color: #fff; text-decoration: none; border-radius: 5px; font-size: 13px; display: inline-block;">
                            📚 Numista
                        </a>
                        <a href="<?= htmlspecialchars($ebayUrl) ?>"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="search-link"
                           style="padding: 8px 15px; background: #3a3a3a; color: #fff; text-decoration: none; border-radius: 5px; font-size: 13px; display: inline-block;">
                            🛒 eBay
                        </a>
                        <a href="<?= htmlspecialchars($leboncoinUrl) ?>"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="search-link"
                           style="padding: 8px 15px; background: #3a3a3a; color: #fff; text-decoration: none; border-radius: 5px; font-size: 13px; display: inline-block;">
                            🏷️ Le Bon Coin
                        </a>
                        <?php if ($isEuro): ?>
                            <a href="https://argus2euros.fr/"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="search-link"
                               style="padding: 8px 15px; background: #3a3a3a; color: #fff; text-decoration: none; border-radius: 5px; font-size: 13px; display: inline-block;">
                                💶 Argus2euros
                            </a>
                        <?php endif; ?>
                    </div>
                    <div style="margin-top: 10px; font-size: 11px; color: #666;">
                        Recherche : "<?= htmlspecialchars($searchQuery) ?>"
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($meta && (!isset($meta['ai_generated']) || $meta['ai_generated'] !== false)): ?>
                <div class="ai-attribution" style="margin-top: 15px; padding: 10px; background: rgba(255,255,255,0.05); border-radius: 5px; font-size: 12px; color: #999;">
                    ℹ️ Informations générées automatiquement par IA
                </div>
            <?php endif; ?>
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
        const coinId = <?= $coinId ?>;
        let isAuthenticated = false;
        let csrfToken = '';

        // Récupérer le token CSRF
        fetch('edit_metadata.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=get_csrf'
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                csrfToken = data.csrf_token;
            }
        });

        // Vérifier l'authentification au chargement
        fetch('edit_metadata.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=check_auth'
        })
        .then(r => r.json())
        .then(data => {
            isAuthenticated = data.authenticated;
        });

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

        // Gestion de l'édition
        document.getElementById('editBtn').addEventListener('click', async () => {
            if (!isAuthenticated) {
                const password = prompt('Mot de passe requis pour modifier:');
                if (!password) return;

                const loginResp = await fetch('edit_metadata.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=login&password=${encodeURIComponent(password)}&csrf_token=${encodeURIComponent(csrfToken)}`
                });
                const loginData = await loginResp.json();

                if (!loginData.success) {
                    alert('Mot de passe incorrect');
                    return;
                }
                isAuthenticated = true;
            }

            // Afficher le formulaire d'édition
            const country = document.getElementById('country').textContent;
            const currency = document.getElementById('currency').textContent;
            const value = document.getElementById('value').textContent;
            const year = document.getElementById('year').textContent;
            const notes = document.getElementById('notes').textContent;

            const newCountry = prompt('Pays:', country === 'À analyser' ? '' : country);
            if (newCountry === null) return;

            const newCurrency = prompt('Monnaie:', currency === 'À analyser' ? '' : currency);
            if (newCurrency === null) return;

            const newValue = prompt('Valeur:', value === 'À analyser' ? '' : value);
            if (newValue === null) return;

            const newYear = prompt('Année:', year === 'À analyser' ? '' : year);
            if (newYear === null) return;

            const newNotes = prompt('Remarques:', notes === 'Aucune' ? '' : notes);
            if (newNotes === null) return;

            // Envoyer la mise à jour
            const updateResp = await fetch('edit_metadata.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=update&coin_id=${coinId}&country=${encodeURIComponent(newCountry)}&currency=${encodeURIComponent(newCurrency)}&value=${encodeURIComponent(newValue)}&year=${encodeURIComponent(newYear)}&notes=${encodeURIComponent(newNotes)}&csrf_token=${encodeURIComponent(csrfToken)}`
            });
            const updateData = await updateResp.json();

            if (updateData.success) {
                alert(updateData.message);
                location.reload();
            } else {
                alert('Erreur: ' + updateData.message);
            }
        });
    </script>
</body>
</html>
