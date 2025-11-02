<?php
// Configuration centralisée

define('PICTURES_DIR', __DIR__ . '/../pictures/2025-11-02_19h15');
define('PICTURES_URL', '/pictures/2025-11-02_19h15');
define('METADATA_FILE', __DIR__ . '/coins_metadata.json');

function getCoins() {
    $images = array_values(array_filter(scandir(PICTURES_DIR), fn($f) => preg_match('/\.jpg$/i', $f)));
    sort($images);
    return array_chunk($images, 2);
}

function getMetadata() {
    if (!file_exists(METADATA_FILE)) {
        return [];
    }
    $json = file_get_contents(METADATA_FILE);
    $data = json_decode($json, true);

    // Indexer par ID pour accès rapide
    $indexed = [];
    foreach ($data as $coin) {
        $indexed[$coin['id']] = $coin;
    }
    return $indexed;
}

function getCoinMetadata($coinId) {
    static $metadata = null;
    if ($metadata === null) {
        $metadata = getMetadata();
    }
    return $metadata[$coinId] ?? null;
}
