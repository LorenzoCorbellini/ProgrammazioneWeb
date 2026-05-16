<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html>
<head>
	<title>SalMeet</title>
	<?php include 'head.html'; ?>
</head>

<body>
	<header><h1 id="hcod1">Utenti</h1></header>
<?php	
	include 'nav.html';
	include 'footer.html';
?>
	<div id="content">     
		<?php
			// In $where memorizziamo i filtri da aggiungere alla query, scritti in sql
			// in $params memorizziamo i valori richiesti da filtrare
			$where = [];
            $params = [];

            // La GET contiene il codice dell'utente richiesto
            if (!empty($_GET['utente'])) {
                $where[] = "codice = :codice";
                $params[':codice'] = $_GET['utente'];
            }

			// Salviamo la query in una stringa per poterla modificare dinamicamente
			$sql = "
				SELECT * FROM utente
			";
			// Se c'è almeno un parametro nella GET, allora aggiungiamo il filtro
			if ($where) $sql .= " WHERE " . implode(" AND ", $where);

			/* Usiamo queste tre righe per effettuare la query. Questo
			 approccio ha 2 vantaggi:
			 1. la query non è suscettibile a sql injection
			 2. vengono applicati in automatico i parametri specificati sopra
			*/
			$stmt = $pdo->prepare($sql);
			$stmt->execute($params);
			$righe = $stmt->fetchAll(PDO::FETCH_ASSOC);

	echo "<table border='1'>
			<tr>
				<th>Nickname</th>
				<th>Nome</th>
				<th>Cognome</th>
				<th>Data nascita</th>
			</tr>";
				
		foreach($righe as $row){

		echo "<tr>";
			echo "<td>".$row['nickname']."</td>";
			echo "<td>".$row['nome']."</td>";
			echo "<td>".$row['cognome']."</td>";
			echo "<td>".$row['dataNascita']."</td>";
		echo "</tr>";
		}

	echo "</table>";
		?>
	</div>

</body>
</html>
