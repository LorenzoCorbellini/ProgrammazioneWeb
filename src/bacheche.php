<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions.php';

// =========================================================
// GESTIONE AZIONI CRUD (chiamate fetch dal JS)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

	header('Content-Type: application/json');

	$input = json_decode(file_get_contents('php://input'), true);

	if (empty($input['azione']) || empty($input['nome']) || empty($input['owner'])) {
		echo json_encode(['successo' => false, 'messaggio' => 'Parametri mancanti.']);
		exit;
	}

	$azione = $input['azione'];
	$nome   = trim($input['nome']);
	$owner  = (int) $input['owner'];

	// ---------------------------------------------------------
	// AGGIUNGI (Nuova bacheca)
	// ---------------------------------------------------------
	if ($azione === 'aggiungi') {
		// Verifica esistenza utente
		$st = $pdo->prepare("SELECT COUNT(*) FROM Utente WHERE codice = ?");
		$st->execute([$owner]);
		if ($st->fetchColumn() == 0) {
			echo json_encode(['successo' => false, 'messaggio' => 'Utente non esistente.']);
			exit;
		}

		// Verifica se esiste già una bacheca con lo stesso nome per questo utente
		$st = $pdo->prepare("SELECT COUNT(*) FROM Bacheca WHERE nome = ? AND codiceUtente = ?");
		$st->execute([$nome, $owner]);
		if ($st->fetchColumn() > 0) {
			echo json_encode(['successo' => false, 'messaggio' => 'Bacheca già esistente per questo utente.']);
			exit;
		}

		try {
			// Iniziamo una transazione per eseguire due inserimenti "tutto o niente"
			$pdo->beginTransaction();

			$dataOggi = date('Y-m-d');

			// 1. Inserimento nella tabella Bacheca
			$stmt1 = $pdo->prepare("INSERT INTO Bacheca (nome, codiceUtente, dataCreazione) VALUES (?, ?, ?)");
			$stmt1->execute([$nome, $owner, $dataOggi]);

			// 2. Inserimento nella tabella UtenteAutorizzatoBacheca (il proprietario è il primo autorizzato)
			$stmt2 = $pdo->prepare("INSERT INTO UtenteAutorizzatoBacheca (nomeBacheca, codUtente, utenteAutorizzato) VALUES (?, ?, ?)");
			$stmt2->execute([$nome, $owner, $owner]);

			// Confermiamo le modifiche nel database
			$pdo->commit();

			echo json_encode(['successo' => true]);
		} catch (Exception $e) {
			// In caso di errore (es: nomi duplicati o problemi DB), annulla tutto
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			echo json_encode(['successo' => false, 'messaggio' => 'Errore durante la creazione: ' . $e->getMessage()]);
		}
		exit;
	}

	// ---------------------------------------------------------
	// AGGIUNGI UTENTE AUTORIZZATO
	// ---------------------------------------------------------
	if ($azione === 'aggiungi_autorizzato') {
		$nuovoUtente = (int) ($input['nuovoUtente'] ?? 0);

		if ($nuovoUtente <= 0) {
			echo json_encode(['successo' => false, 'messaggio' => 'Codice utente non valido.']);
			exit;
		}

		// Verifica che l'utente esista nel database
		$st = $pdo->prepare("SELECT COUNT(*) FROM Utente WHERE codice = ?");
		$st->execute([$nuovoUtente]);
		if ($st->fetchColumn() == 0) {
			echo json_encode(['successo' => false, 'messaggio' => 'L\'utente con questo codice non esiste.']);
			exit;
		}

		// Verifica se l'utente è già autorizzato in questa bacheca
		$st = $pdo->prepare("SELECT COUNT(*) FROM UtenteAutorizzatoBacheca WHERE nomeBacheca = ? AND codUtente = ? AND utenteAutorizzato = ?");
		$st->execute([$nome, $owner, $nuovoUtente]);
		if ($st->fetchColumn() > 0) {
			echo json_encode(['successo' => false, 'messaggio' => 'L\'utente è già autorizzato per questa bacheca.']);
			exit;
		}

		try {
			$pdo->prepare("INSERT INTO UtenteAutorizzatoBacheca (nomeBacheca, codUtente, utenteAutorizzato) VALUES (?, ?, ?)")
				->execute([$nome, $owner, $nuovoUtente]);
			echo json_encode(['successo' => true]);
		} catch (Exception $e) {
			echo json_encode(['successo' => false, 'messaggio' => $e->getMessage()]);
		}
		exit;
	}

	// ---------------------------------------------------------
	// RIMUOVI UTENTE AUTORIZZATO
	// ---------------------------------------------------------
	if ($azione === 'rimuovi_autorizzato') {
		$target = (int) ($input['utenteDaRimuovere'] ?? 0);

		// Se l'utente da rimuovere è il proprietario, blocca l'operazione
		if ($target === $owner) {
			echo json_encode(['successo' => false, 'messaggio' => 'Non puoi rimuovere il proprietario della bacheca.']);
			exit;
		}

		try {
			$pdo->prepare("
                DELETE FROM UtenteAutorizzatoBacheca 
                WHERE nomeBacheca = ? AND codUtente = ? AND utenteAutorizzato = ?
            ")->execute([$nome, $owner, $target]);
			echo json_encode(['successo' => true]);
		} catch (Exception $e) {
			echo json_encode(['successo' => false, 'messaggio' => $e->getMessage()]);
		}
		exit;
	}

	// ---------------------------------------------------------
	// AGGIUNGI FILE (Nuova logica)
	// ---------------------------------------------------------
	if ($azione === 'aggiungi_file') {
		$nuovoFile = (int) ($input['nuovoFile'] ?? 0);

		if ($nuovoFile <= 0) {
			echo json_encode(['successo' => false, 'messaggio' => 'ID file non valido.']);
			exit;
		}

		// Verifica esistenza file
		$st = $pdo->prepare("SELECT COUNT(*) FROM FileMultimediale WHERE numero = ?");
		$st->execute([$nuovoFile]);
		if ($st->fetchColumn() == 0) {
			echo json_encode(['successo' => false, 'messaggio' => 'Il file non esiste nel database.']);
			exit;
		}

		// Verifica se il file è già in bacheca
		$st = $pdo->prepare("SELECT COUNT(*) FROM FilePubblicatoBacheca WHERE nomeBacheca = ? AND codUtente = ? AND file = ?");
		$st->execute([$nome, $owner, $nuovoFile]);
		if ($st->fetchColumn() > 0) {
			echo json_encode(['successo' => false, 'messaggio' => 'Il file è già stato pubblicato in questa bacheca.']);
			exit;
		}

		try {
			$pdo->prepare("INSERT INTO FilePubblicatoBacheca (nomeBacheca, codUtente, file) VALUES (?, ?, ?)")
				->execute([$nome, $owner, $nuovoFile]);
			echo json_encode(['successo' => true]);
		} catch (Exception $e) {
			echo json_encode(['successo' => false, 'messaggio' => $e->getMessage()]);
		}
		exit;
	}

	// ---------------------------------------------------------
	// RIMUOVI FILE (Nuova logica)
	// ---------------------------------------------------------
	if ($azione === 'rimuovi_file') {
		$targetFile = (int) ($input['fileDaRimuovere'] ?? 0);

		try {
			$pdo->prepare("
                DELETE FROM FilePubblicatoBacheca 
                WHERE nomeBacheca = ? AND codUtente = ? AND file = ?
            ")->execute([$nome, $owner, $targetFile]);
			echo json_encode(['successo' => true]);
		} catch (Exception $e) {
			echo json_encode(['successo' => false, 'messaggio' => $e->getMessage()]);
		}
		exit;
	}

	// Verifica che la bacheca esista (per modifica/elimina)
	$check = $pdo->prepare("
        SELECT COUNT(*) FROM Bacheca
        WHERE nome = :nome AND codiceUtente = :owner
    ");
	$check->execute([':nome' => $nome, ':owner' => $owner]);

	if ($check->fetchColumn() == 0) {
		echo json_encode(['successo' => false, 'messaggio' => 'Bacheca non trovata nel database.']);
		exit;
	}

	// ---------------------------------------------------------
	// MODIFICA
	// ---------------------------------------------------------
	if ($azione === 'modifica') {

		$nuovoNome = trim($input['nuovoNome'] ?? '');

		if ($nuovoNome === '') {
			echo json_encode(['successo' => false, 'messaggio' => 'Il nuovo nome non può essere vuoto.']);
			exit;
		}

		$checkDup = $pdo->prepare("
            SELECT COUNT(*) FROM Bacheca
            WHERE nome = :nuovoNome AND codiceUtente = :owner
        ");
		$checkDup->execute([':nuovoNome' => $nuovoNome, ':owner' => $owner]);

		if ($checkDup->fetchColumn() > 0) {
			echo json_encode(['successo' => false, 'messaggio' => 'Esiste già una bacheca con questo nome per lo stesso utente.']);
			exit;
		}

		try {
			$pdo->beginTransaction();

			$pdo->prepare("
                UPDATE Bacheca
                SET nome = :nuovoNome
                WHERE nome = :nome AND codiceUtente = :owner
            ")->execute([':nuovoNome' => $nuovoNome, ':nome' => $nome, ':owner' => $owner]);

			$pdo->commit();
			echo json_encode(['successo' => true]);
		} catch (Exception $e) {
			$pdo->rollBack();
			echo json_encode(['successo' => false, 'messaggio' => 'Errore durante la modifica: ' . $e->getMessage()]);
		}

		// ---------------------------------------------------------
		// ELIMINA
		// ---------------------------------------------------------
	} elseif ($azione === 'elimina') {

		try {
			$pdo->beginTransaction();

			$pdo->prepare("
                DELETE FROM Bacheca
                WHERE nome = :nome AND codiceUtente = :owner
            ")->execute([':nome' => $nome, ':owner' => $owner]);

			$pdo->commit();
			echo json_encode(['successo' => true]);
		} catch (Exception $e) {
			$pdo->rollBack();
			echo json_encode(['successo' => false, 'messaggio' => 'Errore durante la cancellazione: ' . $e->getMessage()]);
		}
	} else {
		echo json_encode(['successo' => false, 'messaggio' => 'Azione non riconosciuta.']);
	}

	$pdo = null;
	exit;
}
?>

<!DOCTYPE html>
<html>

<head>
	<?php include 'head.html'; ?>
	<title>SalMeet</title>
	<script src="./js/bachecheCRUD.js" defer></script>
</head>

<body>

	<header>
		<h1 id="hcod1">Bacheche</h1>
	</header>

	<?php include 'nav.html'; ?>

	<?php
	$filtro_config = [
		'campi' => [
			['tipo' => 'text', 'name' => 'titolo',      'label' => 'Nome Bacheca'],
			['tipo' => 'text', 'name' => 'proprietario', 'label' => 'Proprietario (nickname)'],
			['tipo' => 'text', 'name' => 'data',         'label' => 'Data (gg/mm/aaaa)'],
		]
	];
	include 'filter.php';
	?>

	<div id="content">
		<?php

		// =========================================================
		// VISTA DETTAGLIO BACHECA
		// =========================================================
		if (
			!empty($_GET['vista']) &&
			$_GET['vista'] === 'dettaglio' &&
			!empty($_GET['bacheca']) &&
			!empty($_GET['owner'])
		) {
			$bacheca = $_GET['bacheca'];
			$owner   = $_GET['owner'];
			$bEnc = htmlspecialchars(addslashes($bacheca), ENT_QUOTES);

			echo "<p><a href='" . urlRitorno() . "'>&larr; Torna alle bacheche</a></p>";
			echo "<h2>" . htmlspecialchars($bacheca) . "</h2>";

			// --- Utenti autorizzati ---
			$stmt = $pdo->prepare("
                SELECT
                    u.codice    AS 'Codice',
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
			$utenti = $stmt->fetchAll(PDO::FETCH_ASSOC);
			echo "<h3>Utenti autorizzati: <strong>" . count($utenti) . "</strong></h3>";

			echo "
            <p>
                <a onclick=\"aggiungiAutorizzato('{$bEnc}', {$owner})\" title='Autorizza utente' style='cursor:pointer;'>
                    <img src='images/add.png' alt='Aggiungi' style='width:20px; vertical-align:middle;'> 
                    <strong>Aggiungi utente autorizzato</strong>
                </a>
            </p>
            ";

			if ($utenti) {
				echo "<table border='1'><tr>";
				foreach (array_keys($utenti[0]) as $col) echo "<th>" . htmlspecialchars($col) . "</th>";
				echo "<th>Azioni</th></tr>";
				foreach ($utenti as $u) {
					echo "<tr>";
					foreach ($u as $v) echo "<td>" . htmlspecialchars((string)$v) . "</td>";

					echo "<td style='text-align:center;'>";
					if ((int)$u['Codice'] !== (int)$owner) {
						echo "<img src='images/trash.png' style='width:16px; cursor:pointer;' onclick=\"rimuoviAutorizzato('{$bEnc}', {$owner}, {$u['Codice']})\">";
					} else {
						echo "<small style='color:gray;'>Proprietario</small>";
					}
					echo "</td></tr>";
				}
				echo "</table>";
			} else {
				echo "<p>Nessun utente autorizzato.</p>";
			}

			// --- File ---
			$stmt = $pdo->prepare("
                SELECT
                    fm.numero     AS 'ID File',
                    fm.titolo     AS 'Titolo',
					u.nickname   AS 'Caricato Da',
                    fm.dimensione AS 'Dimensione(MB)',
                    fm.URL        AS 'URL',
                    fm.tipo       AS 'Tipo'
                FROM FilePubblicatoBacheca fb
                JOIN FileMultimediale fm ON fm.numero = fb.file
                JOIN Utente u ON u.codice = fm.caricatoDa
                WHERE fb.nomeBacheca = :bacheca
                  AND fb.codUtente   = :owner
            ");
			$stmt->execute([':bacheca' => $bacheca, ':owner' => $owner]);
			$file = $stmt->fetchAll(PDO::FETCH_ASSOC);

			echo "<h3>File pubblicati: <strong>" . count($file) . "</strong></h3>";

			echo "
            <p>
                <a onclick=\"aggiungiFile('{$bEnc}', {$owner})\" title='Aggiungi file' style='cursor:pointer;'>
                    <img src='images/add.png' alt='Aggiungi' style='width:20px; vertical-align:middle;'> 
                    <strong>Aggiungi file alla bacheca</strong>
                </a>
            </p>
            ";

			if ($file) {
				echo "<table border='1'><tr>";
				foreach (array_keys($file[0]) as $col) echo "<th>" . htmlspecialchars($col) . "</th>";
				echo "<th>Azioni</th></tr>";
				foreach ($file as $f) {
					echo "<tr>";
					foreach ($f as $v) echo "<td>" . htmlspecialchars((string)$v) . "</td>";

					echo "<td style='text-align:center;'>";
					echo "<img src='images/trash.png' style='width:16px; cursor:pointer;' onclick=\"rimuoviFile('{$bEnc}', {$owner}, {$f['ID File']})\">";
					echo "</td></tr>";
				}
				echo "</table>";
			} else {
				echo "<p>Nessun file pubblicato in questa bacheca.</p>";
			}

			// =========================================================
			// VISTA DETTAGLIO UTENTI
			// =========================================================
		} elseif (
			!empty($_GET['vista']) &&
			$_GET['vista'] === 'utenti' &&
			!empty($_GET['bacheca']) &&
			!empty($_GET['owner'])
		) {
			$bacheca = $_GET['bacheca'];
			$owner   = $_GET['owner'];
			$bEnc = htmlspecialchars(addslashes($bacheca), ENT_QUOTES);

			echo "<p><a href='" . urlRitorno() . "'>&larr; Torna alle bacheche</a></p>";

			echo "<h2>" . htmlspecialchars($bacheca) . "</h2>";

			$stmt = $pdo->prepare("
                SELECT
                    u.codice      AS 'Codice',
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
			$utenti = $stmt->fetchAll(PDO::FETCH_ASSOC);

			echo "<h3>Utenti autorizzati: <strong>" . count($utenti) . "</strong></h3>";

			echo "
            <p>
                <a onclick=\"aggiungiAutorizzato('{$bEnc}', {$owner})\" title='Autorizza utente' style='cursor:pointer;'>
                    <img src='images/add.png' alt='Aggiungi' style='width:20px; vertical-align:middle;'> 
                    <strong>Aggiungi utente autorizzato</strong>
                </a>
            </p>
            ";

			if ($utenti) {
				echo "<table border='1'><tr>";
				foreach (array_keys($utenti[0]) as $col) echo "<th>" . htmlspecialchars($col) . "</th>";
				echo "<th>Azioni</th></tr>";
				foreach ($utenti as $u) {
					echo "<tr>";
					foreach ($u as $v) echo "<td>" . htmlspecialchars((string)$v) . "</td>";

					echo "<td style='text-align:center;'>";
					if ((int)$u['Codice'] !== (int)$owner) {
						echo "<img src='images/trash.png' style='width:16px; cursor:pointer;' onclick=\"rimuoviAutorizzato('{$bEnc}', {$owner}, {$u['Codice']})\">";
					} else {
						echo "<small style='color:gray;'>Proprietario</small>";
					}
					echo "</td></tr>";
				}
				echo "</table>";
			} else {
				echo "<p>Nessun utente autorizzato.</p>";
			}

			// =========================================================
			// VISTA DETTAGLIO FILE
			// =========================================================
		} elseif (
			!empty($_GET['vista']) &&
			$_GET['vista'] === 'file' &&
			!empty($_GET['bacheca']) &&
			!empty($_GET['owner'])
		) {
			$bacheca = $_GET['bacheca'];
			$owner   = $_GET['owner'];
			$bEnc = htmlspecialchars(addslashes($bacheca), ENT_QUOTES);

			echo "<p><a href='" . urlRitorno() . "'>&larr; Torna alle bacheche</a></p>";
			echo "<h2>" . htmlspecialchars($bacheca) . "</h2>";

			$stmt = $pdo->prepare("
                SELECT
                    fm.numero     AS 'ID File',
                    fm.titolo     AS 'Titolo',
					u.nickname   AS 'Caricato Da',
                    fm.dimensione AS 'Dimensione(MB)',
                    fm.URL        AS 'URL',
                    fm.tipo       AS 'Tipo'
                FROM FilePubblicatoBacheca fb
                JOIN FileMultimediale fm ON fm.numero = fb.file
				JOIN Utente u ON u.codice = fm.caricatoDa
                WHERE fb.nomeBacheca = :bacheca
                  AND fb.codUtente   = :owner
            ");
			$stmt->execute([':bacheca' => $bacheca, ':owner' => $owner]);
			$file = $stmt->fetchAll(PDO::FETCH_ASSOC);

			echo "<h3>File pubblicati: <strong>" . count($file) . "</strong></h3>";

			echo "
            <p>
                <a onclick=\"aggiungiFile('{$bEnc}', {$owner})\" title='Aggiungi file' style='cursor:pointer;'>
                    <img src='images/add.png' alt='Aggiungi' style='width:20px; vertical-align:middle;'> 
                    <strong>Aggiungi file alla bacheca</strong>
                </a>
            </p>
            ";

			if ($file) {
				echo "<table border='1'><tr>";
				foreach (array_keys($file[0]) as $col) echo "<th>" . htmlspecialchars($col) . "</th>";
				echo "<th>Azioni</th></tr>";
				foreach ($file as $f) {
					echo "<tr>";
					foreach ($f as $v) echo "<td>" . htmlspecialchars((string)$v) . "</td>";

					echo "<td style='text-align:center;'>";
					echo "<img src='images/trash.png' style='width:16px; cursor:pointer;' onclick=\"rimuoviFile('{$bEnc}', {$owner}, {$f['ID File']})\">";
					echo "</td></tr>";
				}
				echo "</table>";
			} else {
				echo "<p>Nessun file pubblicato.</p>";
			}

			// =========================================================
			// VISTA PRINCIPALE
			// =========================================================
		} else {

			$where  = [];
			$params = [];

			if (!empty($_GET['titolo'])) {
				$where[]           = "b.nome LIKE :titolo";
				$params[':titolo'] = '%' . $_GET['titolo'] . '%';
			}

			if (!empty($_GET['proprietario'])) {
				$where[]                 = "u.nickname LIKE :proprietario";
				$params[':proprietario'] = '%' . $_GET['proprietario'] . '%';
			}

			if (!empty($_GET['data'])) {
				$dataConvertita = DateTime::createFromFormat('d/m/Y', $_GET['data']);
				if ($dataConvertita) {
					$where[]         = "DATE(b.dataCreazione) >= :data";
					$params[':data'] = $dataConvertita->format('Y-m-d');
				}
			}

			// --- Paginazione ---
			$elementiPerPagina = 50;
			$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
			$offset = ($pagina - 1) * $elementiPerPagina;

			// --- Conteggio ---
			$sqlCount = "
                SELECT COUNT(*) AS totale
                FROM Bacheca b
                LEFT JOIN Utente u ON u.codice = b.codiceUtente
            ";
			if ($where) $sqlCount .= " WHERE " . implode(" AND ", $where);
			$stmtCount = $pdo->prepare($sqlCount);
			$stmtCount->execute($params);
			$totaleRisultati = $stmtCount->fetch(PDO::FETCH_ASSOC)['totale'];
			$totalePagine    = ceil($totaleRisultati / $elementiPerPagina);

			// --- Query principale ---
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
			$sql .= " GROUP BY b.codiceUtente, u.nickname, b.nome, b.dataCreazione LIMIT :limit OFFSET :offset";

			$stmt = $pdo->prepare($sql);
			foreach ($params as $chiave => $valore) {
				$stmt->bindValue($chiave, $valore);
			}
			$stmt->bindValue(':limit',  $elementiPerPagina, PDO::PARAM_INT);
			$stmt->bindValue(':offset', $offset,            PDO::PARAM_INT);
			$stmt->execute();
			$righe = $stmt->fetchAll(PDO::FETCH_ASSOC);

			echo "<p>Trovate <strong>$totaleRisultati</strong> bacheche ($elementiPerPagina per pagina).</p>";

			echo "
            <p>
                <a onclick='aggiungiBacheca()' title='Aggiungi Bacheca' style='cursor:pointer;'>
                    <img src='images/add.png' alt='Aggiungi' style='width:20px; vertical-align:middle;'> 
                    <strong>Aggiungi una nuova bacheca</strong>
                </a>
            </p>
            ";

			if (empty($righe)) {
				echo "<p>Nessuna bacheca trovata.</p>";
			} else {
				echo "<table border='1'><tr>";
				foreach (array_keys($righe[0]) as $colonna) {
					if ($colonna === 'owner') continue;
					echo "<th>" . htmlspecialchars($colonna) . "</th>";
				}
				echo "<th>Azioni</th>";
				echo "</tr>";

				foreach ($righe as $riga) {
					echo "<tr>";
					$queryCorrente = $_GET;

					foreach ($riga as $colonna => $valore) {
						$val = (string)$valore;

						if ($colonna === 'owner') {
							continue;
						} elseif ($colonna === 'Nome Bacheca') {
							$p = $queryCorrente;
							$p['vista']   = 'dettaglio';
							$p['bacheca'] = $riga['Nome Bacheca'];
							$p['owner']   = $riga['owner'];
							$url = 'bacheche.php?' . http_build_query($p);
							echo "<td><a href='$url'>" . htmlspecialchars($val) . "</a></td>";
						} elseif ($colonna === 'Numero Utenti') {
							$p = $queryCorrente;
							$p['vista']   = 'utenti';
							$p['bacheca'] = $riga['Nome Bacheca'];
							$p['owner']   = $riga['owner'];
							$url = 'bacheche.php?' . http_build_query($p);
							echo "<td class='numero'><a href='$url'>" . htmlspecialchars($val) . "</a></td>";
						} elseif ($colonna === 'Numero File') {
							$p = $queryCorrente;
							$p['vista']   = 'file';
							$p['bacheca'] = $riga['Nome Bacheca'];
							$p['owner']   = $riga['owner'];
							$url = 'bacheche.php?' . http_build_query($p);
							echo "<td class='numero'><a href='$url'>" . htmlspecialchars($val) . "</a></td>";
						} elseif (is_numeric($val)) {
							echo "<td class='numero'>" . htmlspecialchars($val) . "</td>";
						} elseif (isData($val)) {
							echo "<td class='data'>" . formattaData($val) . "</td>";
						} else {
							echo "<td>" . htmlspecialchars($val) . "</td>";
						}
					}
					// --- Icone azioni ---
					$nomeEnc  = htmlspecialchars(addslashes($riga['Nome Bacheca']), ENT_QUOTES);
					$ownerEnc = (int) $riga['owner'];
					echo "
                    <td style='text-align:center; white-space:nowrap;'>
                        <span title='Modifica' style='cursor:pointer; font-size:1.1rem; margin-right:8px;'
                                onclick=\"modificaBacheca('{$nomeEnc}', {$ownerEnc})\">
                                <img src='images/edit.png' alt='Modifica' style='width:16px; height:16px;'>
                            </span>
                            <span title='Elimina' style='cursor:pointer; font-size:1.1rem;'
                                onclick=\"eliminaBacheca('{$nomeEnc}', {$ownerEnc})\">
                            <img src='images/trash.png' alt='Elimina' style='width:16px; height:16px;'>
                        </span>
                    </td>
                    ";
					echo "</tr>";
				}
				echo "</table>";

				// --- Navigazione pagine ---
				echo "<div style='margin-top:20px;'>";
				$queryParams = $_GET;

				if ($pagina > 1) {
					$queryParams['pagina'] = $pagina - 1;
					echo "<a href='?" . http_build_query($queryParams) . "'>&larr;</a>";
				}

				echo "<span style='margin:0 10px;'>Pagina $pagina di $totalePagine</span>";

				if ($pagina < $totalePagine) {
					$queryParams['pagina'] = $pagina + 1;
					echo "<a href='?" . http_build_query($queryParams) . "'>&rarr;</a>";
				}

				echo "</div>";
			}
		}
		?>
	</div>

	<?php include 'footer.html'; ?>

</body>

</html>