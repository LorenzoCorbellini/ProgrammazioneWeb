<?php
// =========================================================
// HELPER PER RECUPERARE UTENTI
// =========================================================
function getUtentiBacheca($pdo, $bacheca, $owner, $bEnc)
{
    $stmt = $pdo->prepare("
                SELECT u.codice, u.nickname, u.nome, u.cognome, u.dataNascita
                FROM UtenteAutorizzatoBacheca uab
                JOIN Utente u ON u.codice = uab.utenteAutorizzato
                WHERE uab.nomeBacheca = :bacheca AND uab.codUtente = :owner
            ");
    $stmt->execute([':bacheca' => $bacheca, ':owner' => $owner]);
    $utenti = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $datiUtenti = [];
    foreach ($utenti as $u) {
        $azioni = ((int)$u['codice'] !== (int)$owner)
            ? "<div style='text-align:center;'><img src='images/trash.png' alt='Elimina' style='width:16px; cursor:pointer;' onclick=\"rimuoviAutorizzato('{$bEnc}', {$owner}, {$u['codice']})\"></div>"
            : "<div style='text-align:center;'><small style='color:gray;'>Proprietario</small></div>";

        $user_link = "utenti.php?utente=" . urlencode($u['codice']);
        $htmlNickname = "<a href='" . htmlspecialchars($user_link) .  "'>" . htmlspecialchars($u['nickname']) . "</a>";

        $datiUtenti[] = [
            'Nickname' => $htmlNickname,
            'Nome' => $u['nome'],
            'Cognome' => $u['cognome'],
            'Data Nascita' => $u['dataNascita'],
            'Azioni' => $azioni
        ];
    }
    return [$datiUtenti, count($utenti)];
}

// =========================================================
// HELPER PER RECUPERARE FILE
// =========================================================
function getFileBacheca($pdo, $bacheca, $owner, $bEnc)
{
    $stmt = $pdo->prepare("
                SELECT fm.numero, fm.titolo, u.codice as caricatoDa, u.nickname, fm.dimensione, fm.URL, fm.tipo
                FROM FilePubblicatoBacheca fb
                JOIN FileMultimediale fm ON fm.numero = fb.file
                JOIN Utente u ON u.codice = fm.caricatoDa
                WHERE fb.nomeBacheca = :bacheca AND fb.codUtente = :owner
            ");
    $stmt->execute([':bacheca' => $bacheca, ':owner' => $owner]);
    $file = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $icon_types = [
        'immagine' => 'images/image.png',
        'video' => 'images/video.png',
        'audio' => 'images/headphones.png',
        'default' => 'images/document.png'
    ];

    $datiFile = [];
    foreach ($file as $f) {
        $tipoStr = strtolower($f['tipo']);
        $icon_path = $icon_types[$tipoStr] ?? $icon_types['default'];

        $title = preg_replace('/\d{3}$/', '', $f['titolo']);

        $htmlFile = "<img class='icona icona-filetype' src='" . htmlspecialchars($icon_path) . "' alt='" . htmlspecialchars($tipoStr) . "'>";
        $htmlFile .= "<a href='" . htmlspecialchars($f['URL']) . "' target='_blank'>" . htmlspecialchars($title) . "</a>";

        $owner_link = "utenti.php?utente=" . urlencode($f['caricatoDa']);
        $htmlOwner = "<a href='" . htmlspecialchars($owner_link) .  "'>" . htmlspecialchars($f['nickname']) . "</a>";

        $azioni   = "<div style='text-align:center;'><img src='images/trash.png' alt='Elimina' style='width:16px; cursor:pointer;' onclick=\"rimuoviFile('{$bEnc}', {$owner}, {$f['numero']})\"></div>";

        $datiFile[] = [
            'File' => $htmlFile,
            'Dimensione(MB)' => $f['dimensione'],
            'Proprietario' => $htmlOwner,
            'Azioni' => $azioni
        ];
    }
    return [$datiFile, count($file)];
}

function isData(string $val): bool
{
    return (bool) preg_match('/^\d{4}-\d{2}-\d{2}/', $val);
}

function formattaData(string $val): string
{
    $d = DateTime::createFromFormat('Y-m-d', substr($val, 0, 10));
    return $d ? $d->format('d/m/Y') : htmlspecialchars($val);
}

function stampaTabella(array $righe, array $htmlColumns = [], array $customHeaders = []): void
{
    if (empty($righe)) {
        echo "<p>Nessun risultato trovato.</p>";
        return;
    }
    echo "<table border='1'><tr>";
    foreach (array_keys($righe[0]) as $colonna) {
        $titolo = isset($customHeaders[$colonna]) ? $customHeaders[$colonna] : htmlspecialchars($colonna);
        echo "<th>" . $titolo . "</th>";
    }
    echo "</tr>";
    foreach ($righe as $riga) {
        echo "<tr>";
        foreach ($riga as $colonna => $valore) {
            $val = (string) $valore;

            // Se la colonna permette codice HTML (link, bottoni), non fa l'escape
            if (in_array($colonna, $htmlColumns, true)) {
                echo "<td>" . $val . "</td>";
            } elseif (is_numeric($val)) {
                echo "<td class='numero'>" . htmlspecialchars($val) . "</td>";
            } elseif (isData($val)) {
                echo "<td class='data'>"   . formattaData($val) . "</td>";
            } else {
                echo "<td>"                 . htmlspecialchars($val) . "</td>";
            }
        }
        echo "</tr>";
    }
    echo "</table>";
}

function urlRitorno(): string
{
    $p = $_GET;
    unset($p['vista'], $p['bacheca'], $p['owner']);
    $q = http_build_query($p);
    return 'bacheche.php' . ($q ? "?$q" : '');
}
