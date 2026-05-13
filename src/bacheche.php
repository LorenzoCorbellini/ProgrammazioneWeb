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
			['tipo'  => 'date',  'name' => 'data',   'label' => 'Data'],
		]
	];
	include 'filter.php';
	?>

	<div id="content">
		<?php
		require_once __DIR__ . '/config/db.php';

		//DA METTERE IN JS
		function isData(string $val): bool
		{
			return (bool) preg_match('/^\d{4}-\d{2}-\d{2}/', $val);
		}

		function stampaTabella(array $righe): void
		{
			if (empty($righe)) {
				echo "<p>Nessun risultato trovato.</p>";
				return;
			}
			echo "<table border='1'><tr>";
			foreach (array_keys($righe[0]) as $colonna) {
				echo "<th>" . htmlspecialchars($colonna) . "</th>";
			}
			echo "</tr>";
			foreach ($righe as $riga) {
				echo "<tr>";
				foreach ($riga as $valore) {
					$val = (string) $valore;
					if (is_numeric($val))  echo "<td class='numero'>" . htmlspecialchars($val) . "</td>";
					elseif (isData($val))  echo "<td class='data'>"   . htmlspecialchars($val) . "</td>";
					else                   echo "<td>"                 . htmlspecialchars($val) . "</td>";
				}
				echo "</tr>";
			}
			echo "</table>";
		}

		// --- VISTA DETTAGLIO UTENTI ---
		if (!empty($_GET['vista']) && $_GET['vista'] === 'utenti' && !empty($_GET['bacheca'])) {
			$bacheca = $_GET['bacheca'];
			echo "<p><a href='bacheche.php'>&larr; Torna alle bacheche</a></p>";
			echo "<h2>Utenti autorizzati &mdash; " . htmlspecialchars($bacheca) . "</h2>";

			$stmt = $pdo->prepare("
                SELECT
                    u.nickname        AS 'Nickname',
                    u.nome            AS 'Nome',
                    u.cognome         AS 'Cognome',
                    u.dataNascita     AS 'Data Nascita'
                FROM UtenteAutorizzatoBacheca ub
                    JOIN Utente u ON u.codice = ub.utenteAutorizzato
                WHERE ub.nomeBacheca = :bacheca
            ");
			$stmt->execute([':bacheca' => $bacheca]);
			stampaTabella($stmt->fetchAll(PDO::FETCH_ASSOC));

			// --- VISTA DETTAGLIO FILE ---
		} elseif (!empty($_GET['vista']) && $_GET['vista'] === 'file' && !empty($_GET['bacheca'])) {
			$bacheca = $_GET['bacheca'];
			echo "<p><a href='bacheche.php'>&larr; Torna alle bacheche</a></p>";
			echo "<h2>File pubblicati &mdash; " . htmlspecialchars($bacheca) . "</h2>";

			$stmt = $pdo->prepare("
                SELECT
                    fm.titolo         AS 'Titolo',
                    fm.dimensione     AS 'Dimensione(MB)',
                    fm.URL            AS 'URL',
                    fm.tipo           AS 'Tipo'
                FROM FilePubblicatoBacheca fb
                    JOIN FileMultimediale fm
                        ON fm.numero    = fb.file
                WHERE fb.nomeBacheca = :bacheca
            ");
			$stmt->execute([':bacheca' => $bacheca]);
			stampaTabella($stmt->fetchAll(PDO::FETCH_ASSOC));

			// --- VISTA PRINCIPALE ---
		} else {
			$where  = [];
			$params = [];

			if (!empty($_GET['titolo'])) {
				$where[]           = "b.nome LIKE :titolo";
				$params[':titolo'] = '%' . $_GET['titolo'] . '%';
			}
			if (!empty($_GET['data'])) {
				$where[]         = "DATE(b.dataCreazione) = :data";
				$params[':data'] = $_GET['data'];
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
                    b.nome                             AS 'Nome Bacheca',
                    b.dataCreazione                    AS 'Data Creazione',
                    COUNT(DISTINCT u.utenteAutorizzato) AS 'Numero Utenti',
                    COUNT(DISTINCT f.file)             AS 'Numero File'
                FROM Bacheca b
                    LEFT JOIN UtenteAutorizzatoBacheca u
                        ON u.codUtente = b.codiceUtente AND u.nomeBacheca = b.nome
                    LEFT JOIN FilePubblicatoBacheca f
                        ON f.codUtente = b.codiceUtente AND f.nomeBacheca = b.nome
            ";
			if ($where) $sql .= " WHERE " . implode(" AND ", $where);
			$sql .= " GROUP BY b.codiceUtente, b.nome, b.dataCreazione";

			$stmt = $pdo->prepare($sql);
			$stmt->execute($params);
			$righe = $stmt->fetchAll(PDO::FETCH_ASSOC);

			if (empty($righe)) {
				echo "<p>Nessuna bacheca trovata.</p>";
			} else {
				echo "<table border='1'><tr>";
				foreach (array_keys($righe[0]) as $colonna) {
					echo "<th>" . htmlspecialchars($colonna) . "</th>";
				}
				echo "</tr>";
				foreach ($righe as $riga) {
					echo "<tr>";
					$nb = urlencode($riga['Nome Bacheca']);
					foreach ($riga as $colonna => $valore) {
						$val = (string) $valore;
						if ($colonna === 'Numero Utenti') {
							echo "<td class='numero'><a href='bacheche.php?vista=utenti&bacheca=$nb'>" . htmlspecialchars($val) . "</a></td>";
						} elseif ($colonna === 'Numero File') {
							echo "<td class='numero'><a href='bacheche.php?vista=file&bacheca=$nb'>" . htmlspecialchars($val) . "</a></td>";
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