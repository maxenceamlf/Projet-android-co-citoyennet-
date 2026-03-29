<?php
/**
 * edit_profile.php — Mise à jour du profil d'un résident connecté
 *
 * Méthode : POST (ou PUT)
 * Headers : Authorization: Bearer <token>
 * Corps    : firstname, lastname, phone (tous optionnels)
 *            + new_password (optionnel, nécessite current_password)
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/response.php';

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
    sendError("Méthode non autorisée.", 405);
}

// --- Authentification obligatoire ---
$pdo  = getDB();
$user = requireAuth($pdo);

// --- Récupération des données ---
$firstname       = trim($_POST['firstname']        ?? $user['firstname']);
$lastname        = trim($_POST['lastname']         ?? $user['lastname']);
$phone           = trim($_POST['phone']            ?? $user['phone']) ?: null;
$newPassword     = trim($_POST['new_password']     ?? '');
$currentPassword = trim($_POST['current_password'] ?? '');

// --- Validation ---
if (empty($firstname) || empty($lastname)) {
    sendError("Le prénom et le nom ne peuvent pas être vides.");
}

// --- Gestion du changement de mot de passe ---
$hashedPassword = $user['password']; // Par défaut, on garde l'ancien

if (!empty($newPassword)) {
    if (empty($currentPassword)) {
        sendError("Le mot de passe actuel est requis pour en définir un nouveau.");
    }
    if (!password_verify($currentPassword, $user['password'])) {
        sendError("Mot de passe actuel incorrect.", 401);
    }
    if (strlen($newPassword) < 6) {
        sendError("Le nouveau mot de passe doit contenir au moins 6 caractères.");
    }
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
}

// --- Mise à jour en base ---
try {
    $stmt = $pdo->prepare(
        "UPDATE users SET firstname = ?, lastname = ?, phone = ?, password = ?
         WHERE id = ?"
    );
    $stmt->execute([$firstname, $lastname, $phone, $hashedPassword, $user['id']]);

    sendSuccess([
        "user" => [
            "id"         => $user['id'],
            "firstname"  => $firstname,
            "lastname"   => $lastname,
            "email"      => $user['email'],
            "phone"      => $phone,
            "habitat_id" => $user['habitat_id'],
            "eco_coins"  => $user['eco_coins'],
        ]
    ], "Profil mis à jour avec succès.");

} catch (PDOException $e) {
    sendError("Erreur serveur. Veuillez réessayer.", 500);
}
