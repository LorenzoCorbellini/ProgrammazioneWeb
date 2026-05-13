<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions.php';
?>

<!DOCTYPE html>
<html>

<head>
	<?php include 'head.html'; ?>
	<title>SalMeet</title>
</head>

<body>
	<header>
		<h1 id="hcod1">Bacheche</h1>
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

		// --- VISTA DETTAGLIO UTENTI ---
		if (!empty($_GET['vista']) && $_GET['vista'] === 'utenti' && !empty($_GET['bacheca']) && !empty($_GET['owner'])) {
			$bacheca = $_GET['bacheca'];
			$owner   = $_GET['owner'];
			echo "<p><a href='bacheche.php'>&larr; Torna alle bacheche</a></p>";
			echo "<h2>Utenti autorizzati &mdash; " . htmlspecialchars($bacheca) . "</h2>";

			$stmt = $pdo->prepare("
				SELECT
					u.nickname    AS 'Nickname',
					u.nome        AS 'Nome',
					u.cognome     AS 'Cognome',
					u.dataNascita AS 'Data Nascita'
				FROM UtenteAutorizzatoBacheca uab
					JOIN Utente u ON u.codice = uab.utenteAutorizzato
				WHERE uab.nomeBacheca = :bacheca
				  AND uab.codUtente   = :owner
			");
			$stmt->execute([':bacheca' => $bacheca, ':owner' => $owner]);
			stampaTabella($stmt->fetchAll(PDO::FETCH_ASSOC));

		// --- VISTA DETTAGLIO FILE ---
		} elseif (!empty($_GET['vista']) && $_GET['vista'] === 'file' && !empty($_GET['bacheca']) && !empty($_GET['owner'])) {
			$bacheca = $_GET['bacheca'];
			$owner   = $_GET['owner'];
			echo "<p><a href='bacheche.php'>&larr; Torna alle bacheche</a></p>";
			echo "<h2>File pubblicati &mdash; " . htmlspecialchars($bacheca) . "</h2>";

			$stmt = $pdo->prepare("
				SELECT
					fm.titolo        AS 'Titolo',
					fm.dimensione    AS 'Dimensione(MB)',
					fm.URL           AS 'URL',
					fm.tipo          AS 'Tipo'
				FROM FilePubblicatoBacheca fb
					JOIN FileMultimediale fm ON fm.numero = fb.file
				WHERE fb.nomeBacheca = :bacheca
				  AND fb.codUtente   = :owner
			");
			$stmt->execute([':bacheca' => $bacheca, ':owner' => $owner]);
			stampaTabella($stmt->fetchAll(PDO::FETCH_ASSOC));

		// --- VISTA PRINCIPALE ---
		} else {
			$where  = [];
			$params = [];

			if (!empty($_GET['titolo'])) { // nome della bacheca
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

			$sql = "
				SELECT
					b.codiceUtente                        AS 'owner',
					u.nickname                            AS 'Proprietario',
					b.nome                                AS 'Nome Bacheca',
					b.dataCreazione                       AS 'Data Creazione',
					COUNT(DISTINCT uab.utenteAutorizzato) AS 'Numero Utenti',
					COUNT(DISTINCT f.file)                AS 'Numero File'
				FROM Bacheca b
					LEFT JOIN UtenteAutorizzatoBacheca uab
						ON uab.codUtente = b.codiceUtente AND uab.nomeBacheca = b.nome
					LEFT JOIN FilePubblicatoBacheca f
						ON f.codUtente = b.codiceUtente AND f.nomeBacheca = b.nome
					LEFT JOIN Utente u
						ON u.codice = b.codiceUtente
			";
			if ($where) $sql .= " WHERE " . implode(" AND ", $where);
			$sql .= " GROUP BY b.codiceUtente, u.nickname, b.nome, b.dataCreazione";

			$stmt = $pdo->prepare($sql);
			$stmt->execute($params);
			$righe = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if (empty($righe)) {
				echo "<p>Nessuna bacheca trovata.</p>";
			} else {
				echo "<table border='1'><tr>";
				foreach (array_keys($righe[0]) as $colonna) {
					if ($colonna === 'owner') continue;
					echo "<th>" . htmlspecialchars($colonna) . "</th>";
				}
				echo "</tr>";
				foreach ($righe as $riga) {
					echo "<tr>";
					$nb    = urlencode($riga['Nome Bacheca']);
					$owner = urlencode($riga['owner']);
					foreach ($riga as $colonna => $valore) {
						$val = (string) $valore;
						if ($colonna === 'owner') {
							continue;
						} elseif ($colonna === 'Numero Utenti') {
							echo "<td class='numero'><a href='bacheche.php?vista=utenti&bacheca=$nb&owner=$owner'>" . htmlspecialchars($val) . "</a></td>";
						} elseif ($colonna === 'Numero File') {
							echo "<td class='numero'><a href='bacheche.php?vista=file&bacheca=$nb&owner=$owner'>" . htmlspecialchars($val) . "</a></td>";
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
		}
		?>
	</div>

	<?php include 'footer.html'; ?>

</body>

</html>