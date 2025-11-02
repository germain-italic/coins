<?php
require_once 'config.php';

// Configuration sécurisée de la session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Générer un token CSRF si absent
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Charger le mot de passe depuis .env
$envFile = realpath(__DIR__ . '/../.env');
$editPasswordHash = null;
if ($envFile && file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, 'EDIT_PASSWORD=') === 0) {
            $password = substr($line, strlen('EDIT_PASSWORD='));
            // Utiliser password_hash pour comparer (pour compatibilité future)
            $editPasswordHash = $password;
            break;
        }
    }
}

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

$response = ['success' => false, 'message' => ''];

// Vérifier que la requête est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Méthode non autorisée';
    http_response_code(405);
    echo json_encode($response);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'get_csrf') {
    $response['success'] = true;
    $response['csrf_token'] = $_SESSION['csrf_token'];
    echo json_encode($response);
    exit;
}

if ($action === 'check_auth') {
    $response['authenticated'] = isset($_SESSION['edit_auth_hash']) &&
                                  hash_equals($_SESSION['edit_auth_hash'], hash('sha256', $editPasswordHash));
    $response['success'] = true;
    echo json_encode($response);
    exit;
}

if ($action === 'login') {
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    // Vérifier le token CSRF
    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $response['message'] = 'Token CSRF invalide';
        http_response_code(403);
        echo json_encode($response);
        exit;
    }

    // Utiliser hash_equals pour éviter timing attacks
    if (hash_equals($editPasswordHash, $password)) {
        // Ne jamais stocker le mot de passe en session, utiliser un hash
        $_SESSION['edit_auth_hash'] = hash('sha256', $editPasswordHash);
        $response['success'] = true;
        $response['message'] = 'Authentifié';
    } else {
        // Rate limiting basique
        sleep(1); // Ralentir les tentatives de brute force
        $response['message'] = 'Mot de passe incorrect';
    }
    echo json_encode($response);
    exit;
}

// Toutes les autres actions nécessitent l'authentification
if (!isset($_SESSION['edit_auth_hash']) ||
    !hash_equals($_SESSION['edit_auth_hash'], hash('sha256', $editPasswordHash))) {
    $response['message'] = 'Non authentifié';
    http_response_code(401);
    echo json_encode($response);
    exit;
}

if ($action === 'update') {
    $csrfToken = $_POST['csrf_token'] ?? '';

    // Vérifier le token CSRF
    if (!hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $response['message'] = 'Token CSRF invalide';
        http_response_code(403);
        echo json_encode($response);
        exit;
    }

    $coinId = filter_var($_POST['coin_id'] ?? -1, FILTER_VALIDATE_INT);

    // Validation stricte des entrées
    if ($coinId === false || $coinId < 0) {
        $response['message'] = 'ID de pièce invalide';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    // Sanitization des données (limiter la longueur, enlever tags HTML)
    $country = mb_substr(strip_tags($_POST['country'] ?? ''), 0, 100);
    $currency = mb_substr(strip_tags($_POST['currency'] ?? ''), 0, 100);
    $value = mb_substr(strip_tags($_POST['value'] ?? ''), 0, 100);
    $year = mb_substr(strip_tags($_POST['year'] ?? ''), 0, 4);
    $notes = mb_substr(strip_tags($_POST['notes'] ?? ''), 0, 500);

    // Valider l'année si présente
    if ($year !== '' && !preg_match('/^\d{4}$/', $year)) {
        $response['message'] = 'Format d\'année invalide (YYYY attendu)';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    // Charger les métadonnées avec vérification du chemin
    $metadataFile = realpath(METADATA_FILE);
    $expectedDir = realpath(__DIR__);

    if (!$metadataFile || strpos($metadataFile, $expectedDir) !== 0) {
        $response['message'] = 'Chemin de fichier invalide';
        http_response_code(500);
        echo json_encode($response);
        exit;
    }

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
            $metadata[$coinId]['ai_generated'] = false;

            // Sauvegarder avec permissions sécurisées
            $jsonData = array_values($metadata);
            $tempFile = $metadataFile . '.tmp';

            if (file_put_contents($tempFile, json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX) === false) {
                $response['message'] = 'Erreur d\'écriture';
                http_response_code(500);
            } else {
                if (!rename($tempFile, $metadataFile)) {
                    unlink($tempFile);
                    $response['message'] = 'Erreur de sauvegarde';
                    http_response_code(500);
                } else {
                    $response['success'] = true;
                    $response['message'] = 'Métadonnées mises à jour';
                }
            }
        } else {
            $response['success'] = true;
            $response['message'] = 'Aucune modification';
        }
    } else {
        $response['message'] = 'Pièce non trouvée';
        http_response_code(404);
    }
}

echo json_encode($response);
