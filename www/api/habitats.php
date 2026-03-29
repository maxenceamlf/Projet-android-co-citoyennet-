<?php
/**
 * habitats.php — Liste de tous les habitats avec leurs équipements
 *
 * Méthode : GET
 * Headers : Authorization: Bearer <token>
 *
 * Retour : tableau d'habitats, chacun contenant sa liste d'équipements
 * et sa consommation totale en watts.
 *
 * Exemple de réponse :
 * {
 *   "success": true,
 *   "data": [
 *     {
 *       "id": 1, "name": "Appartement A101", "floor": 1, "area": 45.5,
 *       "total_wattage": 4700,
 *       "appliances": [
 *         { "id": 1, "name": "Lave-linge", "wattage": 2500, "icon_name": "ic_laundry" },
 *         ...
 *       ]
 *     }
 *   ]
 * }
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/response.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError("Méthode non autorisée.", 405);
}

// Authentification obligatoire
$pdo = getDB();
requireAuth($pdo);

try {
    // Récupérer tous les habitats
    $stmtHabitats = $pdo->query("SELECT * FROM habitats ORDER BY floor, name");
    $habitats = $stmtHabitats->fetchAll();

    // Pour chaque habitat, récupérer ses équipements
    $stmtAppliances = $pdo->prepare(
        "SELECT a.id, a.name, a.wattage, a.icon_name
         FROM appliances a
         JOIN habitat_appliances ha ON ha.appliance_id = a.id
         WHERE ha.habitat_id = ?"
    );

    $result = [];
    foreach ($habitats as $habitat) {
        $stmtAppliances->execute([$habitat['id']]);
        $appliances = $stmtAppliances->fetchAll();

        // Calcul de la consommation totale de l'habitat
        $totalWattage = array_sum(array_column($appliances, 'wattage'));

        $result[] = [
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
        ];
    }

    sendSuccess($result, "Liste des habitats récupérée.");

} catch (PDOException $e) {
    sendError("Erreur serveur.", 500);
}
