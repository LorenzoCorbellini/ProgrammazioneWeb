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
			/* FILTRI */
				$where  = [];
				$params = [];
	
				// Filtro per nome del file
				if (!empty($_GET['filename'])) {
					$where[]             = "fmm.titolo LIKE :filename";
					$params[':filename'] = '%' . $_GET['filename'] . '%';
				}
				// Filtro per tipo di file
				if (!empty($_GET['filetype'])) {
					$filetype = $filetypes[$_GET['filetype']];
					$where[]             = "fmm.tipo = :filetype";
					$params[':filetype'] =  $filetype;
				}
	
			/* VISTA DEI DATI */
	
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
	
				/*
				 * prepara la query (statement)
				 * la esegue con i $params 
				 * salva il risultato (una tabella) in $righe
				 */
				$stmt = $pdo->prepare($sql);
				$stmt->execute($params);
				$righe = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
				if (empty($righe)) {
					echo "<p>Nessun file trovato.</p>";
				} else {
					echo "<table border='1'><tr>";
	
					$mappa_colonne = [
						'title'     => 'File',
						'size' 		=> 'Dimensione',
						'type'      => 'Tipo',
						'nickname'  => 'Proprietario'
					];
					
					/* Imposta i nomi delle colonne,
					 * saltando quelle nella blacklist.
					 * Il parame 'true' di 'in_array(...)' impone strict comparison
					 */
					$blacklist = ['owner', 'file_id', 'url', 'type'];
					foreach (array_keys($righe[0]) as $colonna) {
						if (in_array($colonna, $blacklist, true)) continue;
						$titolo_visibile = $mappa_colonne[$colonna] ?? ucfirst($colonna);
						echo "<th>" . htmlspecialchars($titolo_visibile) . "</th>";
					}
	
					echo "</tr>";
					
					$icon_types = [
						'immagine' => 'images/image.png',
						'video' => 'images/video.png',
						'audio' => 'images/headphones.png',
	
						'default' => 'images/document.png'
					];
	
					foreach ($righe as $riga) {
						echo "<tr>";
		
						// $item as $key => $value
						foreach ($riga as $colonna => $valore) {
							$val = (string) $valore;
							if (in_array($colonna, $blacklist, true)) {
								continue;
							// Mostra link cliccabile sui nomi dei file
							} elseif ($colonna === 'title') {
								// Rimuove i 3 numeri alla fine del filename
								$title = preg_replace('/\d{3}$/', '', $riga['title']);
								$icon_path = $icon_types[$riga['type']] ?? $icon_types['default'];
								
								echo "<td class='titolo'>";
								echo "<img class='icona icona-filetype' src='" . htmlspecialchars($icon_path) . "' alt='" . htmlspecialchars($riga['type']) . "'>";
								echo "<a href='" . htmlspecialchars($riga['url']) .  "'>" . htmlspecialchars($title) . "</a>";
								echo "</td>";
							} elseif ($colonna === 'nickname') {
								$owner_link = "utenti.php?utente=" . urlencode($riga['owner']);
								echo "<td class='titolo'><a href='" . htmlspecialchars($owner_link) .  "'>" . htmlspecialchars($val) . "</a></td>";
							} elseif (is_numeric($val)) {
								echo "<td class='numero'>" . htmlspecialchars($val) . "</td>";
							} elseif (isData($val)) {
								echo "<td class='data'>"   . htmlspecialchars($val) . "</td>";
							} else {
								echo "<td>"                . htmlspecialchars($val) . "</td>";
							}
						}
						echo "</tr>";
					}
					echo "</table>";
				}
			?>
		</div>
	</div>

	<?php include 'footer.html'; ?>
</body>
<?php $pdo = null; // Chiude esplicitamente la connessione al db ?>
</html>