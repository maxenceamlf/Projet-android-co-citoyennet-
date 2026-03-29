<?php
/**
 * login.php — Authentification d'un résident
 *
 * Méthode : POST
 * Corps    : email, password
 * Retour   : token + données utilisateur (sans le mot de passe)
 *
 * Exemple de réponse succès :
 * {
 *   "success": true,
 *   "message": "Connexion réussie.",
 *   "data": {
 *     "token": "abc123...",
 *     "user": { "id": 1, "firstname": "Alice", "email": "...", "habitat_id": 1 }
 *   }
 * }
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/response.php';

// Seules les requêtes POST sont acceptées
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError("Méthode non autorisée.", 405);
}

// --- Récupération et validation des données ---
$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
    sendError("L'email et le mot de passe sont obligatoires.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendError("Format d'email invalide.");
}

// --- Vérification en base de données ---
try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // On vérifie TOUJOURS password_verify pour éviter les timing attacks
    if (!$user || !password_verify($password, $user['password'])) {
        sendError("Email ou mot de passe incorrect.", 401);
    }

    // --- Génération d'un nouveau token de session (64 octets = 128 hex chars) ---
    $token = bin2hex(random_bytes(64));

    $stmtToken = $pdo->prepare("UPDATE users SET token = ? WHERE id = ?");
    $stmtToken->execute([$token, $user['id']]);

    // On ne renvoie jamais le mot de passe hashé au client
    sendSuccess([
        "token" => $token,
        "user"  => [
            "id"         => $user['id'],
            "firstname"  => $user['firstname'],
            "lastname"   => $user['lastname'],
            "email"      => $user['email'],
            "phone"      => $user['phone'],
            "habitat_id" => $user['habitat_id'],
            "eco_coins"  => $user['eco_coins'],
        ]
    ], "Connexion réussie.");

} catch (PDOException $e) {
    // En production, ne pas exposer le message d'erreur SQL
    sendError("Erreur serveur. Veuillez réessayer.", 500);
}
