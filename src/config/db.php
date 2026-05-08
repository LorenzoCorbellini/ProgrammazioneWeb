<?php
$host     = 'plainasia.altervista.org';
$dbname   = 'my_plainasia';
$username = 'plainasia';
$password = 'gSQ6$suq3ZQrvbX3';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore connessione: " . $e->getMessage());
}

/* Includerlo in tutti i file in cui fare query
<?php
// in index.php, prodotti.php, ecc.
require_once __DIR__ . '/config/db.php';

// da qui puoi usare $pdo direttamente
$stmt = $pdo->query("SELECT * FROM prodotti");
*/