<?php
// Configuration centralisée

define('PICTURES_DIR', __DIR__ . '/../pictures/2025-11-02_19h15');
define('PICTURES_URL', '/pictures/2025-11-02_19h15');

function getCoins() {
    $images = array_values(array_filter(scandir(PICTURES_DIR), fn($f) => preg_match('/\.jpg$/i', $f)));
    sort($images);
    return array_chunk($images, 2);
}
