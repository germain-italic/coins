<?php
require_once 'config.php';
session_start();

// Charger le mot de passe depuis .env
$envFile = __DIR__ . '/../.env';
$editPassword = null;
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'EDIT_PASSWORD=') === 0) {
            $editPassword = substr($line, strlen('EDIT_PASSWORD='));
            break;
        }
    }
}

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// Vérifier l'authentification
$action = $_POST['action'] ?? '';

if ($action === 'check_auth') {
    $response['authenticated'] = isset($_SESSION['edit_authenticated']) && $_SESSION['edit_authenticated'];
    $response['success'] = true;
    echo json_encode($response);
    exit;
}

if ($action === 'login') {
    $password = $_POST['password'] ?? '';
    if ($password === $editPassword) {
        $_SESSION['edit_authenticated'] = true;
        $response['success'] = true;
        $response['message'] = 'Authentifié';
    } else {
        $response['message'] = 'Mot de passe incorrect';
    }
    echo json_encode($response);
    exit;
}

// Toutes les autres actions nécessitent l'authentification
if (!isset($_SESSION['edit_authenticated']) || !$_SESSION['edit_authenticated']) {
    $response['message'] = 'Non authentifié';
    echo json_encode($response);
    exit;
}

if ($action === 'update') {
    $coinId = intval($_POST['coin_id'] ?? -1);
    $country = $_POST['country'] ?? '';
    $currency = $_POST['currency'] ?? '';
    $value = $_POST['value'] ?? '';
    $year = $_POST['year'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // Charger les métadonnées
    $metadata = getMetadata();

    if (isset($metadata[$coinId])) {
        // Vérifier si modification effective
        $original = $metadata[$coinId];
        $hasChanged = (
            $original['country'] !== $country ||
            $original['currency'] !== $currency ||
            $original['value'] !== $value ||
            ($original['year'] ?? '') !== $year ||
            ($original['notes'] ?? '') !== $notes
        );

        if ($hasChanged) {
            $metadata[$coinId]['country'] = $country;
            $metadata[$coinId]['currency'] = $currency;
            $metadata[$coinId]['value'] = $value;
            $metadata[$coinId]['year'] = $year ?: null;
            $metadata[$coinId]['notes'] = $notes ?: null;
            $metadata[$coinId]['ai_generated'] = false; // Marquer comme modifié manuellement

            // Sauvegarder
            $jsonData = array_values($metadata); // Reconvertir en array indexé
            file_put_contents(METADATA_FILE, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            $response['success'] = true;
            $response['message'] = 'Métadonnées mises à jour';
        } else {
            $response['success'] = true;
            $response['message'] = 'Aucune modification';
        }
    } else {
        $response['message'] = 'Pièce non trouvée';
    }
}

echo json_encode($response);
