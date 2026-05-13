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
					fmd.caricatoDa	AS 'owner',
					fmd.numero		AS 'file_id',
					fmd.titolo		AS 'Titolo',
					fmd.dimensione  AS 'Dimensione',
					fmd.URL			AS 'URL',
					fmd.tipo		AS 'Tipo'
				FROM FileMultimediale fmd
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
			$sql .= " GROUP BY fmd.caricatoDa, fmd.tipo, fmd.dimensione";

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

				/* Imposta i nomi delle colonne,
				 * saltando quelle nella blacklist.
				 * Il parame 'true' di 'in_array(...)' impone strict comparison
				 */
				$blacklist = ['owner', 'file_id'];
				foreach (array_keys($righe[0]) as $colonna) {
					if (in_array($colonna, $blacklist, true)) continue;
					echo "<th>" . htmlspecialchars($colonna) . "</th>";
				}
				echo "</tr>";
				foreach ($righe as $riga) {
					echo "<tr>";
					$titolo = urlencode($riga['Titolo']);
					$owner = urlencode($riga['owner']);
					foreach ($riga as $colonna => $valore) {
						$val = (string) $valore;
						if (in_array($colonna, $blacklist, true)) {
							continue;
						// Gli elementi della colonna "Numero Utenti" triggerano una GET per mostrare gli utenti della bacheca
						} elseif ($colonna === 'Numero Utenti') {
							echo "<td class='numero'><a href='media.php?vista=utenti&bacheca=$titolo&owner=$owner'>" . htmlspecialchars($val) . "</a></td>";
						// Gli elementi della colonna "Numero File" triggerano una GET per mostrare i file nella bacheca
						} elseif ($colonna === 'Numero File') {
							echo "<td class='numero'><a href='media.php?vista=file&bacheca=$titolo&owner=$owner'>" . htmlspecialchars($val) . "</a></td>";
						// Gestiamo qualsiasi altro caso in cui vengono mostrati dei valori
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