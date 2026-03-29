<?php
/**
 * response.php — Helpers pour envoyer des réponses JSON uniformes
 *
 * Toutes les réponses de l'API respectent le format :
 * { "success": true/false, "message": "...", "data": { ... } }
 */

// En-têtes CORS pour permettre les requêtes depuis l'émulateur Android
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Répondre aux requêtes preflight OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Envoie une réponse JSON de succès et termine le script.
 *
 * @param mixed  $data    Données à inclure (tableau, objet, null)
 * @param string $message Message descriptif
 * @param int    $code    Code HTTP (200 par défaut)
 */
function sendSuccess($data = null, string $message = "OK", int $code = 200): void {
    http_response_code($code);
    echo json_encode([
        "success" => true,
        "message" => $message,
        "data"    => $data
    ]);
    exit();
}

/**
 * Envoie une réponse JSON d'erreur et termine le script.
 *
 * @param string $message Message d'erreur
 * @param int    $code    Code HTTP (400 par défaut)
 */
function sendError(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode([
        "success" => false,
        "message" => $message,
        "data"    => null
    ]);
    exit();
}

/**
 * Vérifie le token Bearer dans l'en-tête Authorization.
 * Retourne les données de l'utilisateur ou arrête avec 401.
 *
 * @param PDO $pdo Instance de connexion BDD
 * @return array Données de l'utilisateur connecté
 */
function requireAuth(PDO $pdo): array {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (!str_starts_with($authHeader, 'Bearer ')) {
        sendError("Token manquant ou invalide.", 401);
    }

    $token = substr($authHeader, 7); // Enlève "Bearer "

    $stmt = $pdo->prepare("SELECT * FROM users WHERE token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        sendError("Token invalide ou expiré.", 401);
    }

    return $user;
}
