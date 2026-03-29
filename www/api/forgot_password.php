<?php
/**
 * forgot_password.php — Demande de réinitialisation du mot de passe
 *
 * Méthode : POST
 * Corps    : email
 *
 * Dans un vrai projet, on enverrait un email avec un lien de reset.
 * Ici, on simule la logique (log côté serveur) et on retourne succès
 * pour ne pas révéler si l'email existe ou non (sécurité).
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError("Méthode non autorisée.", 405);
}

$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    sendError("L'email est obligatoire.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendError("Format d'email invalide.");
}

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // On répond TOUJOURS avec succès pour éviter l'énumération d'emails
    if ($user) {
        // TODO production : envoyer un vrai email avec lien de reset
        // mail($email, "Réinitialisation PowerHome", "Lien : https://...");
        error_log("[PowerHome] Demande de reset mot de passe pour : " . $email);
    }

    // Même réponse si l'email n'existe pas (évite de révéler les comptes)
    sendSuccess(null, "Si cet email est enregistré, un lien de réinitialisation vous sera envoyé.");

} catch (PDOException $e) {
    sendError("Erreur serveur. Veuillez réessayer.", 500);
}
