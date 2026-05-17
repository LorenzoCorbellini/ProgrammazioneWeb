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
		<h1 id="hcod1">Media</h1>
	</header>
	
	<div class="main-container">
	<aside class="sidebar">
		<?php include 'nav.html'; ?>
		
		<?php 
			$filtro_config = [
				'campi' => [
					['tipo'  => 'text',  'name' => 'filename', 'label' => 'File'],
					['tipo'  => 'select',  'name' => 'filetype', 'label' => 'Tipo',
						'opzioni' => ['Immagini', 'Audio', 'Video']]
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
		
			/* PAGINAZIONE */
			$limit = 50;
			$pagina = isset($_GET["pagina"]) ? max(1, (int)$_GET["pagina"]) : 1;
			$start_from = ($pagina - 1) * $limit;

			/* FILTRI */
			$where = [];
			$params = [];

			if (!empty($_GET['filename'])) {
				$where[] = "fmm.titolo LIKE :filename";
				$params[':filename'] = '%' . $_GET['filename'] . '%';
			}

			if (!empty($_GET['filetype']) && isset($filetypes[$_GET['filetype']])) {
				$where[] = "fmm.tipo = :filetype";
				$params[':filetype'] =  $filetypes[$_GET['filetype']];
			}
	
			/* VISTA DEI DATI */
	
			// Query per ottenere i dati da mostrare all'utente
			$sql = "
				SELECT
					fmm.caricatoDa	AS 'owner',
					fmm.numero		AS 'file_id',
					fmm.titolo		AS 'title',
					fmm.dimensione  AS 'size',
					fmm.URL			AS 'url',
					fmm.tipo		AS 'type',
					u.nickname		AS 'nickname'
				FROM FileMultimediale fmm
					LEFT JOIN Utente u
						ON fmm.caricatoDa = u.codice
			";
			if ($where) $sql .= " WHERE " . implode(" AND ", $where);
			$sql .= " LIMIT " . (int)$start_from . ", " . (int)$limit;
	
			/*
			 * prepara la query (statement)
			 * la esegue con i $params 
			 * salva il risultato (una tabella) in $righe
			 */
			$stmt = $pdo->prepare($sql);
			$stmt->execute($params);
			$righe = $stmt->fetchAll(PDO::FETCH_ASSOC);

			// Query per ottenere il numero di file memorizzati nel db
			$sql_count = "SELECT COUNT(*) FROM FileMultimediale as fmm";
			if ($where) $sql_count .= " WHERE " . implode(" AND ", $where);
			$stmt_count = $pdo->prepare($sql_count);
			$stmt_count->execute($params);
			$numero_records = $stmt_count->fetchColumn();
			$totale_pagine = ceil($numero_records / $limit);

			echo "<p>Trovati <strong>$numero_records</strong> file ($limit per pagina).</p>";

			if (!empty($righe)) {
				$icon_types = [
					'immagine' => 'images/image.png',
					'video' => 'images/video.png',
					'audio' => 'images/headphones.png',
					'default' => 'images/document.png'
				];
				
				$datiFormattati = [];
				foreach ($righe as $riga) {
					$tipoStr = strtolower($riga['type']);
					$icon_path = $icon_types[$tipoStr] ?? $icon_types['default'];
					
					// Rimuove i 3 numeri alla fine del filename
					$title = preg_replace('/\d{3}$/', '', $riga['title']);
					
					$htmlFile = "<img class='icona icona-filetype' src='" . htmlspecialchars($icon_path) . "' alt='" . htmlspecialchars($tipoStr) . "'>";
					$htmlFile .= "<a href='" . htmlspecialchars($riga['url']) . "' target='_blank'>" . htmlspecialchars($title) . "</a>";
					
					// Crea il link al proprietario
					$owner_link = "utenti.php?utente=" . urlencode($riga['owner']);
					$htmlOwner = "<a href='" . htmlspecialchars($owner_link) .  "'>" . htmlspecialchars($riga['nickname']) . "</a>";
					
					// Preparazione della riga da inviare a stampaTabella
					$datiFormattati[] = [
						'File' => $htmlFile,
						'Dimensione (MB)' => $riga['size'],
						//'Tipo' => ucfirst($riga['type']),
						'Proprietario' => $htmlOwner
					];
				}
				
				// Stampa la tabella passando l'array delle colonne che contengono codice HTML
				stampaTabella($datiFormattati, ['File', 'Proprietario']);

				// Navigazione pagine
				echo "<div style='margin-top:20px;'>";
				$queryParams = $_GET;
				if ($pagina > 1) {
					$queryParams['pagina'] = $pagina - 1;
					echo "<a href='?" . http_build_query($queryParams) . "'>&larr;</a>";
				}
				echo "<span style='margin:0 10px;'>Pagina $pagina di $totale_pagine</span>";
				if ($pagina < $totale_pagine) {
					$queryParams['pagina'] = $pagina + 1;
					echo "<a href='?" . http_build_query($queryParams) . "'>&rarr;</a>";
				}
				echo "</div>";
			} else {
				echo "<p>Nessun file trovato.</p>";
			}
		?>
		</div>
	</div>

	<?php include 'footer.html'; ?>
</body>
</html>