//File di configurazione per la connessione al database
<?php
$host     = '127.0.0.1';
$dbname   = 'my_plainasia';
$username = 'root';
$password = 'root'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore connessione: " . $e->getMessage());
}