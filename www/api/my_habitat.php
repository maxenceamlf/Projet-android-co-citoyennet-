<?php
/**
 * my_habitat.php — Habitat de l'utilisateur connecté
 *
 * Méthode : GET
 * Headers : Authorization: Bearer <token>
 *
 * Retour : habitat du résident avec ses équipements et consommation
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError("Méthode non autorisée.", 405);
}

$pdo  = getDB();
$user = requireAuth($pdo);

// Vérifier que l'utilisateur a un habitat assigné
if (empty($user['habitat_id'])) {
    sendSuccess(null, "Aucun habitat assigné à cet utilisateur.");
}

try {
    // Récupérer l'habitat de l'utilisateur
    $stmtHabitat = $pdo->prepare("SELECT * FROM habitats WHERE id = ?");
    $stmtHabitat->execute([$user['habitat_id']]);
    $habitat = $stmtHabitat->fetch();

    if (!$habitat) {
        sendError("Habitat introuvable.", 404);
    }

    // Récupérer ses équipements
    $stmtAppliances = $pdo->prepare(
        "SELECT a.id, a.name, a.wattage, a.icon_name
         FROM appliances a
         JOIN habitat_appliances ha ON ha.appliance_id = a.id
         WHERE ha.habitat_id = ?"
    );
    $stmtAppliances->execute([$habitat['id']]);
    $appliances = $stmtAppliances->fetchAll();

    $totalWattage = array_sum(array_column($appliances, 'wattage'));

    sendSuccess([
        "id"            => (int)$habitat['id'],
        "name"          => $habitat['name'],
        "floor"         => (int)$habitat['floor'],
        "area"          => (float)$habitat['area'],
        "total_wattage" => $totalWattage,
        "appliances"    => array_map(fn($a) => [
            "id"        => (int)$a['id'],
            "name"      => $a['name'],
            "wattage"   => (int)$a['wattage'],
            "icon_name" => $a['icon_name'],
        ], $appliances),
        "eco_coins"     => (int)$user['eco_coins'],
    ], "Habitat récupéré.");

} catch (PDOException $e) {
    sendError("Erreur serveur.", 500);
}
