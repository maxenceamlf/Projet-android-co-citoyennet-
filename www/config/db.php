<?php
/**
 * db.php — Connexion PDO à la base de données PowerHome
 *
 * À adapter selon votre configuration locale (XAMPP, WAMP, etc.)
 * Sous XAMPP par défaut : host=localhost, user=root, pass=""
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'powerhome');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Retourne une instance PDO configurée avec des préparations sécurisées.
 * Lance une exception si la connexion échoue.
 */
function getDB(): PDO {
    static $pdo = null; // Singleton : une seule connexion par requête PHP

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,  // Vraies requêtes préparées
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}
