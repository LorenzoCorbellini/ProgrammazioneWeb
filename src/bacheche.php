<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/functions.php';

// =========================================================
// CRUD
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
	// AGGIUNGI BACHECA
	// ---------------------------------------------------------
	if ($azione === 'aggiungi') {
		$st = $pdo->prepare("SELECT COUNT(*) FROM Utente WHERE codice = ?");
		$st->execute([$owner]);
		if ($st->fetchColumn() == 0) {
			echo json_encode(['successo' => false, 'messaggio' => 'Utente non esistente.']);
			exit;
		}

		$st = $pdo->prepare("SELECT COUNT(*) FROM Bacheca WHERE nome = ? AND codiceUtente = ?");
		$st->execute([$nome, $owner]);
		if ($st->fetchColumn() > 0) {
			echo json_encode(['successo' => false, 'messaggio' => 'Bacheca già esistente per questo utente.']);
			exit;
		}

		try {
			$pdo->beginTransaction();

			$dataOggi = date('Y-m-d');
			$stmt1 = $pdo->prepare("INSERT INTO Bacheca (nome, codiceUtente, dataCreazione) VALUES (?, ?, ?)");
			$stmt1->execute([$nome, $owner, $dataOggi]);

			$stmt2 = $pdo->prepare("INSERT INTO UtenteAutorizzatoBacheca (nomeBacheca, codUtente, utenteAutorizzato) VALUES (?, ?, ?)");
			$stmt2->execute([$nome, $owner, $owner]);

			$pdo->commit();
			echo json_encode(['successo' => true]);
		} catch (Exception $e) {
			if ($pdo->inTransaction()) $pdo->rollBack();
			echo json_encode(['successo' => false, 'messaggio' => 'Errore: ' . $e->getMessage()]);
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

		$st = $pdo->prepare("SELECT COUNT(*) FROM Utente WHERE codice = ?");
		$st->execute([$nuovoUtente]);
		if ($st->fetchColumn() == 0) {
			echo json_encode(['successo' => false, 'messaggio' => 'L\'utente non esiste.']);
			exit;
		}

		$st = $pdo->prepare("SELECT COUNT(*) FROM UtenteAutorizzatoBacheca WHERE nomeBacheca = ? AND codUtente = ? AND utenteAutorizzato = ?");
		$st->execute([$nome, $owner, $nuovoUtente]);
		if ($st->fetchColumn() > 0) {
			echo json_encode(['successo' => false, 'messaggio' => 'Utente già autorizzato.']);
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

		if ($target === $owner) {
			echo json_encode(['successo' => false, 'messaggio' => 'Non puoi rimuovere il proprietario.']);
			exit;
		}

		try {
			$pdo->prepare("DELETE FROM UtenteAutorizzatoBacheca WHERE nomeBacheca = ? AND codUtente = ? AND utenteAutorizzato = ?")
			    ->execute([$nome, $owner, $target]);
			echo json_encode(['successo' => true]);
		} catch (Exception $e) {
			echo json_encode(['successo' => false, 'messaggio' => $e->getMessage()]);
		}
		exit;
	}

	// ---------------------------------------------------------
	// AGGIUNGI FILE
	// ---------------------------------------------------------
	if ($azione === 'aggiungi_file') {
		$nuovoFile = (int) ($input['nuovoFile'] ?? 0);

		if ($nuovoFile <= 0) {
			echo json_encode(['successo' => false, 'messaggio' => 'ID file non valido.']);
			exit;
		}

		$stFile = $pdo->prepare("SELECT caricatoDa FROM FileMultimediale WHERE numero = ?");
		$stFile->execute([$nuovoFile]);
		$fileData = $stFile->fetch(PDO::FETCH_ASSOC);

		if (!$fileData) {
			echo json_encode(['successo' => false, 'messaggio' => 'Il file non esiste.']);
			exit;
		}

		$creatoreFile = (int) $fileData['caricatoDa'];

		$stAuth = $pdo->prepare("SELECT COUNT(*) FROM UtenteAutorizzatoBacheca WHERE nomeBacheca = ? AND codUtente = ? AND utenteAutorizzato = ?");
		$stAuth->execute([$nome, $owner, $creatoreFile]);
		if ($stAuth->fetchColumn() == 0) {
			echo json_encode(['successo' => false, 'messaggio' => 'Creatore non autorizzato per questa bacheca.']);
			exit;
		}

		$st = $pdo->prepare("SELECT COUNT(*) FROM FilePubblicatoBacheca WHERE nomeBacheca = ? AND codUtente = ? AND file = ?");
		$st->execute([$nome, $owner, $nuovoFile]);
		if ($st->fetchColumn() > 0) {
			echo json_encode(['successo' => false, 'messaggio' => 'Il file è già stato pubblicato.']);
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
	// RIMUOVI FILE
	// ---------------------------------------------------------
	if ($azione === 'rimuovi_file') {
		$targetFile = (int) ($input['fileDaRimuovere'] ?? 0);
		try {
			$pdo->prepare("DELETE FROM FilePubblicatoBacheca WHERE nomeBacheca = ? AND codUtente = ? AND file = ?")
			    ->execute([$nome, $owner, $targetFile]);
			echo json_encode(['successo' => true]);
		} catch (Exception $e) {
			echo json_encode(['successo' => false, 'messaggio' => $e->getMessage()]);
		}
		exit;
	}

	// ---------------------------------------------------------
	// MODIFICA & ELIMINA
	// ---------------------------------------------------------
	$check = $pdo->prepare("SELECT COUNT(*) FROM Bacheca WHERE nome = :nome AND codiceUtente = :owner");
	$check->execute([':nome' => $nome, ':owner' => $owner]);

	if ($check->fetchColumn() == 0) {
		echo json_encode(['successo' => false, 'messaggio' => 'Bacheca non trovata.']);
		exit;
	}

	if ($azione === 'modifica') {
		$nuovoNome = trim($input['nuovoNome'] ?? '');
		if ($nuovoNome === '') {
			echo json_encode(['successo' => false, 'messaggio' => 'Nome non valido.']);
			exit;
		}

		$checkDup = $pdo->prepare("SELECT COUNT(*) FROM Bacheca WHERE nome = :nuovoNome AND codiceUtente = :owner");
		$checkDup->execute([':nuovoNome' => $nuovoNome, ':owner' => $owner]);
		if ($checkDup->fetchColumn() > 0) {
			echo json_encode(['successo' => false, 'messaggio' => 'Bacheca omonima esistente.']);
			exit;
		}

		try {
			$pdo->beginTransaction();
			$pdo->prepare("UPDATE Bacheca SET nome = :nuovoNome WHERE nome = :nome AND codiceUtente = :owner")
			    ->execute([':nuovoNome' => $nuovoNome, ':nome' => $nome, ':owner' => $owner]);
			$pdo->commit();
			echo json_encode(['successo' => true]);
		} catch (Exception $e) {
			$pdo->rollBack();
			echo json_encode(['successo' => false, 'messaggio' => $e->getMessage()]);
		}

	} elseif ($azione === 'elimina') {
		try {
			$pdo->beginTransaction();
			$pdo->prepare("DELETE FROM Bacheca WHERE nome = :nome AND codiceUtente = :owner")
			    ->execute([':nome' => $nome, ':owner' => $owner]);
			$pdo->commit();
			echo json_encode(['successo' => true]);
		} catch (Exception $e) {
			$pdo->rollBack();
			echo json_encode(['successo' => false, 'messaggio' => $e->getMessage()]);
		}
	} else {
		echo json_encode(['successo' => false, 'messaggio' => 'Azione non riconosciuta.']);
	}
	$pdo = null;
	exit;
}
?>

<!DOCTYPE html>
<html lang="it">

<head>
	<?php include 'head.html'; ?>
	<title>SalMeet</title>
	<script src="./js/bachecheCRUD.js" defer></script>
</head>

<body>

	<header>
		<h1 id="hcod1">Bacheche</h1>
	</header>

	<div class="main-container">
	<aside class="sidebar">
		<?php include 'nav.html'; ?>

		<?php
		$filtro_config = [
			'campi' => [
				['tipo' => 'text', 'name' => 'titolo',       'label' => 'Nome Bacheca'],
				['tipo' => 'text', 'name' => 'proprietario', 'label' => 'Proprietario (nickname)'],
				['tipo' => 'text', 'name' => 'data',         'label' => 'Data (gg/mm/aaaa)'],
			]
		];
		include 'filter.php';
		?>
	</aside>

	<div id="content">
		<?php
		// =========================================================
		// ROUTING VISTE
		// =========================================================
		if (!empty($_GET['vista']) && !empty($_GET['bacheca']) && !empty($_GET['owner'])) {
			$vista   = $_GET['vista'];
			$bacheca = $_GET['bacheca'];
			$owner   = $_GET['owner'];
			$bEnc    = htmlspecialchars(addslashes($bacheca), ENT_QUOTES);

			echo "<p><a href='" . urlRitorno() . "'>&larr; Torna alle bacheche</a></p>";
			echo "<h2>" . htmlspecialchars($bacheca) . "</h2>";

			if ($vista === 'dettaglio' || $vista === 'utenti') {
				list($datiUtenti, $countUtenti) = getUtentiBacheca($pdo, $bacheca, $owner, $bEnc);
				echo "<h3>Utenti autorizzati: <strong>{$countUtenti}</strong></h3>";
				echo "<p><a onclick=\"aggiungiAutorizzato('{$bEnc}', {$owner})\" style='cursor:pointer;'>
                    <img src='images/add.png' alt='Aggiungi' style='width:20px; vertical-align:middle;'> <strong>Aggiungi utente autorizzato</strong>
                </a></p>";
                stampaTabella($datiUtenti, ['Nickname', 'Azioni']);
			}

			if ($vista === 'dettaglio' || $vista === 'file') {
				list($datiFile, $countFile) = getFileBacheca($pdo, $bacheca, $owner, $bEnc);
				echo "<h3>File pubblicati: <strong>{$countFile}</strong></h3>";
				echo "<p><a onclick=\"aggiungiFile('{$bEnc}', {$owner})\" style='cursor:pointer;'>
                    <img src='images/add.png' alt='Aggiungi' style='width:20px; vertical-align:middle;'> <strong>Aggiungi file alla bacheca</strong>
                </a></p>";
                
                // Usiamo direttamente stampaTabella
                stampaTabella($datiFile, ['File', 'Proprietario', 'Azioni']);
			}

		} else {
			// =========================================================
			// VISTA PRINCIPALE (ELENCO BACHECHE)
			// =========================================================
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

			// --- GESTIONE ORDINAMENTO ---
			$sort_col = $_GET['sort'] ?? 'data';
			$sort_dir = strtoupper($_GET['dir'] ?? 'DESC');
			if ($sort_dir !== 'ASC' && $sort_dir !== 'DESC') $sort_dir = 'DESC';

			$allowed_sorts = [
				'nome' => 'b.nome',
				'data' => 'b.dataCreazione'
			];
			$sql_sort = $allowed_sorts[$sort_col] ?? 'b.dataCreazione';

			$elementiPerPagina = 50;
			$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
			$offset = ($pagina - 1) * $elementiPerPagina;

			$sqlCount = "SELECT COUNT(*) AS totale FROM Bacheca b LEFT JOIN Utente u ON u.codice = b.codiceUtente";
			if ($where) $sqlCount .= " WHERE " . implode(" AND ", $where);
			$stmtCount = $pdo->prepare($sqlCount);
			$stmtCount->execute($params);
			$totaleRisultati = $stmtCount->fetch(PDO::FETCH_ASSOC)['totale'];
			$totalePagine    = ceil($totaleRisultati / $elementiPerPagina);

			$sql = "
                SELECT
                    b.codiceUtente AS 'owner',
                    b.nome AS 'Nome Bacheca',
					u.nickname AS 'Proprietario',
                    b.dataCreazione AS 'Data Creazione',
                    COUNT(DISTINCT uab.utenteAutorizzato) AS 'Numero Utenti',
                    COUNT(DISTINCT f.file) AS 'Numero File'
                FROM Bacheca b
                LEFT JOIN UtenteAutorizzatoBacheca uab ON uab.codUtente = b.codiceUtente AND uab.nomeBacheca = b.nome
                LEFT JOIN FilePubblicatoBacheca f ON f.codUtente = b.codiceUtente AND f.nomeBacheca = b.nome
                LEFT JOIN Utente u ON u.codice = b.codiceUtente
            ";
			if ($where) $sql .= " WHERE " . implode(" AND ", $where);
			
			$sql .= " GROUP BY b.codiceUtente, u.nickname, b.nome, b.dataCreazione ORDER BY {$sql_sort} {$sort_dir} LIMIT :limit OFFSET :offset";

			$stmt = $pdo->prepare($sql);
			foreach ($params as $chiave => $valore) { $stmt->bindValue($chiave, $valore); }
			$stmt->bindValue(':limit',  $elementiPerPagina, PDO::PARAM_INT);
			$stmt->bindValue(':offset', $offset,            PDO::PARAM_INT);
			$stmt->execute();
			$righe = $stmt->fetchAll(PDO::FETCH_ASSOC);

			echo "<p>Trovate <strong>$totaleRisultati</strong> bacheche ($elementiPerPagina per pagina).</p>";
			echo "<p><a onclick='aggiungiBacheca()' style='cursor:pointer;'>
                    <img src='images/add.png' alt='Aggiungi' style='width:20px; vertical-align:middle;'> <strong>Aggiungi una nuova bacheca</strong>
                </a></p>";

            if (!empty($righe)) {
                $datiBacheche = [];
                foreach ($righe as $riga) {
                    $p = $_GET;
                    
                    $p['vista']   = 'dettaglio';
                    $p['bacheca'] = $riga['Nome Bacheca'];
                    $p['owner']   = $riga['owner'];
                    $htmlNome = "<a href='bacheche.php?" . http_build_query($p) . "'>" . htmlspecialchars($riga['Nome Bacheca']) . "</a>";

                    $p['vista']   = 'utenti';
                    $htmlUtenti = "<div style='text-align: right;'><a href='bacheche.php?" . http_build_query($p) . "'>" . htmlspecialchars($riga['Numero Utenti']) . "</a></div>";

                    $p['vista']   = 'file';
                    $htmlFile = "<div style='text-align: right;'><a href='bacheche.php?" . http_build_query($p) . "'>" . htmlspecialchars($riga['Numero File']) . "</a></div>";

                    $proprietarioLink = "utenti.php?utente=" . urlencode($riga['owner']);
                    $htmlProprietario = "<a href='" . htmlspecialchars($proprietarioLink) . "'>" . htmlspecialchars($riga['Proprietario']) . "</a>";

                    $nomeEnc  = htmlspecialchars(addslashes($riga['Nome Bacheca']), ENT_QUOTES);
                    $ownerEnc = (int) $riga['owner'];
                    $azioni = "<div style='text-align:center; white-space:nowrap;'>
                        <span title='Modifica' style='cursor:pointer; font-size:1.1rem; margin-right:8px;' onclick=\"modificaBacheca('{$nomeEnc}', {$ownerEnc})\">
                            <img src='images/edit.png' alt='Modifica' style='width:16px; height:16px;'>
                        </span>
                        <span title='Elimina' style='cursor:pointer; font-size:1.1rem;' onclick=\"eliminaBacheca('{$nomeEnc}', {$ownerEnc})\">
                            <img src='images/trash.png' alt='Elimina' style='width:16px; height:16px;'>
                        </span>
                    </div>";

                    $datiBacheche[] = [
                        'Nome Bacheca' => $htmlNome,
                        'Proprietario' => $htmlProprietario,
                        'Data Creazione' => $riga['Data Creazione'],
                        'Numero Utenti' => $htmlUtenti,
                        'Numero File' => $htmlFile,
                        'Azioni' => $azioni
                    ];
                }

				$paramsNome = $_GET;
				$paramsNome['sort'] = 'nome';
				$paramsNome['dir']  = ($sort_col === 'nome' && $sort_dir === 'ASC') ? 'DESC' : 'ASC';

				$paramsData = $_GET;
				$paramsData['sort'] = 'data';
				$paramsData['dir']  = ($sort_col === 'data' && $sort_dir === 'ASC') ? 'DESC' : 'ASC';

				$customHeaders = [
					'Nome Bacheca'   => "Nome Bacheca <a href='?" . http_build_query($paramsNome) . "'><img src='images/bi-directional-arrow.png' alt='Ordina' style='width:12px; margin-left:5px; vertical-align:middle;'></a>",
					'Data Creazione' => "Data Creazione <a href='?" . http_build_query($paramsData) . "'><img src='images/bi-directional-arrow.png' alt='Ordina' style='width:12px; margin-left:5px; vertical-align:middle;'></a>"
				];

                stampaTabella($datiBacheche, ['Nome Bacheca', 'Proprietario', 'Numero Utenti', 'Numero File', 'Azioni'], $customHeaders);

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
	</div>

	<?php include 'footer.html'; ?>

</body>
</html>