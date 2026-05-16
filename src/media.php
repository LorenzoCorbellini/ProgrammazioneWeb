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
	<?php include 'nav.html'; ?>
	
	<?php 
	$filtro_config = [
		'campi' => [
			['tipo'  => 'text',  'name' => 'titolo', 'label' => 'Titolo'],
			['tipo'  => 'text', 'name' => 'data',   'label' => 'Data (gg/mm/aaaa)'],
		]
	];
	include 'filter.php';
	?>
	
	<div id="content">
		<?php 
		// --- VISTA PRINCIPALE ---
			$where  = [];
			$params = [];

			if (!empty($_GET['titolo'])) {
				$where[]           = "b.nome LIKE :titolo";
				$params[':titolo'] = '%' . $_GET['titolo'] . '%';
			}
			if (!empty($_GET['data'])) {
				$dataConvertita = DateTime::createFromFormat('d/m/Y', $_GET['data']);
				if ($dataConvertita) {
					$where[]         = "DATE(b.dataCreazione) >= :data";
					$params[':data'] = $dataConvertita->format('Y-m-d');
				}
			}
			if (!empty($_GET['tipo'])) {
				$where[]         = "b.tipo = :tipo";
				$params[':tipo'] = $_GET['tipo'];
			}
			if (isset($_GET['solo_attive'])) {
				$where[] = "b.attiva = 1";
			}

			/*
			 * Da aggiungere:
			 * - bacheche in cui il file è pubblicato
			 * - utente che ha pubblicato il file
			 * - url clickabile
			 */
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

			// Tengo la query come esempio (temporaneamente)
			// $sql = "
			// 	SELECT
			// 		b.codiceUtente                        AS 'owner',
			// 		u.nickname                            AS 'Proprietario',
			// 		b.nome                                AS 'Nome Bacheca',
			// 		b.dataCreazione                       AS 'Data Creazione',
			// 		COUNT(DISTINCT uab.utenteAutorizzato) AS 'Numero Utenti',
			// 		COUNT(DISTINCT f.file)                AS 'Numero File'
			// 	FROM Bacheca b
			// 		LEFT JOIN UtenteAutorizzatoBacheca uab
			// 			ON uab.codUtente = b.codiceUtente AND uab.nomeBacheca = b.nome
			// 		LEFT JOIN FilePubblicatoBacheca f
			// 			ON f.codUtente = b.codiceUtente AND f.nomeBacheca = b.nome
			// 		LEFT JOIN Utente u
			// 			ON u.codice = b.codiceUtente
			// ";
			if ($where) $sql .= " WHERE " . implode(" AND ", $where);
			//$sql .= " GROUP BY fmm.numero, fmm.caricatoDa, fmm.tipo, fmm.dimensione, fmm.titolo, fmm.URL";

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
					'title'     => 'Nome file',
					'size' 		=> 'Dimensione',
					'type'      => 'Tipo',
					'nickname'  => 'Caricato da'
				];
				
				/* Imposta i nomi delle colonne,
				 * saltando quelle nella blacklist.
				 * Il parame 'true' di 'in_array(...)' impone strict comparison
				 */
				$blacklist = ['file_id', 'url'];
				foreach (array_keys($righe[0]) as $colonna) {
					if (in_array($colonna, $blacklist, true)) continue;
					$titolo_visibile = $mappa_colonne[$colonna] ?? ucfirst($colonna);
					echo "<th>" . htmlspecialchars($titolo_visibile) . "</th>";
				}

				echo "</tr>";
				
				foreach ($righe as $riga) {
					echo "<tr>";
					$url = urlencode($riga["url"]);
					$owner = urlencode($riga['owner']);

					
					// $item as $key => $value
					foreach ($riga as $colonna => $valore) {
						$val = (string) $valore;
						if (in_array($colonna, $blacklist, true)) {
							continue;
						// Mostra link cliccabile sui nomi dei file
						} elseif ($colonna === 'title') {
							echo "<td class='titolo'><a href='" . $url .  "'>" . htmlspecialchars($val) . "</a></td>";
						} elseif ($colonna === 'nickname') {
							echo "<td class='titolo'><a href='" . $url .  "'>" . htmlspecialchars($val) . "</a></td>";
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

	<?php include 'footer.html'; ?>
</body>
<?php $pdo = null; // Chiude esplicitamente la connessione al db ?>
</html>