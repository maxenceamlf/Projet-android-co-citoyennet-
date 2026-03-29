<?php
/**
 * register.php — Inscription d'un nouveau résident
 *
 * Méthode : POST
 * Corps    : firstname, lastname, email, password, phone (optionnel)
 * Retour   : token + données utilisateur créé
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError("Méthode non autorisée.", 405);
}

// --- Récupération des données ---
$firstname = trim($_POST['firstname'] ?? '');
$lastname  = trim($_POST['lastname']  ?? '');
$email     = trim($_POST['email']     ?? '');
$password  = trim($_POST['password']  ?? '');
$phone     = trim($_POST['phone']     ?? '') ?: null; // null si vide

// --- Validation ---
if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
    sendError("Tous les champs obligatoires doivent être remplis.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendError("Format d'email invalide.");
}

if (strlen($password) < 6) {
    sendError("Le mot de passe doit contenir au moins 6 caractères.");
}

// --- Insertion en base ---
try {
    $pdo = getDB();

    // Vérifier que l'email n'est pas déjà utilisé
    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmtCheck->execute([$email]);
    if ($stmtCheck->fetch()) {
        sendError("Cet email est déjà utilisé.", 409);
    }

    // Hasher le mot de passe avec bcrypt
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Générer un token de session
    $token = bin2hex(random_bytes(64));

    // Insérer l'utilisateur (sans habitat_id : à assigner plus tard)
    $stmtInsert = $pdo->prepare(
        "INSERT INTO users (firstname, lastname, email, password, phone, token)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmtInsert->execute([$firstname, $lastname, $email, $hashedPassword, $phone, $token]);

    $newId = $pdo->lastInsertId();

    sendSuccess([
        "token" => $token,
        "user"  => [
            "id"         => (int)$newId,
            "firstname"  => $firstname,
            "lastname"   => $lastname,
            "email"      => $email,
            "phone"      => $phone,
            "habitat_id" => null,
            "eco_coins"  => 0,
        ]
    ], "Compte créé avec succès.", 201);

} catch (PDOException $e) {
    sendError("Erreur serveur. Veuillez réessayer.", 500);
}
