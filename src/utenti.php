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
	<header>
		<h1 id="hcod1">Utenti</h1>
	</header>

	<div class="main-container">
		<aside class="sidebar">
			<?php include 'nav.html'; ?>

			<?php
			$filtro_config = [
				'campi' => [
					['tipo'  => 'text',  'name' => 'filename', 'label' => 'File'],
					[
						'tipo'  => 'select',
						'name' => 'filetype',
						'label' => 'Tipo',
						'opzioni' => ['Immagini', 'Audio', 'Video']
					]
				]
			];
			include 'filter.php';

			$filetypes = [
				'Immagini' => 'immagine',
				'Audio' => 'audio',
				'Video' => 'video'
			];
			?>
		</aside>

		<div id="content">
			<?php
		// =========================================================
		// PAGINAZIONE
		// =========================================================
			$elementiPerPagina = 50;
			$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
			$offset = ($pagina - 1) * $elementiPerPagina;


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
				SELECT nickname as 'Nickname',
					nome as 'Nome',
					cognome as 'Cognome',
					dataNascita as 'Data di Nascita'
				FROM utente
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


			stampaTabella($righe);


			?>
		</div>
	</div>

	<?php include 'footer.html'; ?>
</body>

</html>