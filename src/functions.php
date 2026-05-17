<?php
function isData(string $val): bool
{
    return (bool) preg_match('/^\d{4}-\d{2}-\d{2}/', $val);
}

function formattaData(string $val): string
{
    $d = DateTime::createFromFormat('Y-m-d', substr($val, 0, 10));
    return $d ? $d->format('d/m/Y') : htmlspecialchars($val);
}

// Aggiunto array opzionale $customHeaders per permettere HTML nei titoli
function stampaTabella(array $righe, array $htmlColumns = [], array $customHeaders = []): void
{
    if (empty($righe)) {
        echo "<p>Nessun risultato trovato.</p>";
        return;
    }
    echo "<table border='1'><tr>";
    foreach (array_keys($righe[0]) as $colonna) {
        // Se c'è un header personalizzato usa quello, altrimenti usa il nome della colonna testuale
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

// Helper per link di ritorno
function urlRitorno(): string {
	$p = $_GET;
	unset($p['vista'], $p['bacheca'], $p['owner']);
	$q = http_build_query($p);
	return 'bacheche.php' . ($q ? "?$q" : '');
}

function get_media_table(array $righe, int $numero_records, int $limit): string {
    if (empty($righe)) {
        return "<p>Nessun file trovato.</p>";
    }

    $html = "<p>Trovati <strong>$numero_records</strong> file ($limit per pagina).</p>";

    $html .= "<table border='1'><tr>";

    $mappa_colonne = [
        'title'     => 'File',
        'size' 		=> 'Dimensione',
        'type'      => 'Tipo',
        'nickname'  => 'Proprietario'
    ];
    
    $blacklist = ['owner', 'file_id', 'url', 'type'];
    foreach (array_keys($righe[0]) as $colonna) {
        if (in_array($colonna, $blacklist, true)) continue;
        $titolo_visibile = $mappa_colonne[$colonna] ?? ucfirst($colonna);
        $html .= "<th>" . htmlspecialchars($titolo_visibile) . "</th>";
    }

    $html .= "</tr>";
    
    $icon_types = [
        'immagine' => 'images/image.png',
        'video' => 'images/video.png',
        'audio' => 'images/headphones.png',
        'default' => 'images/document.png'
    ];

    foreach ($righe as $riga) {
        $html .= "<tr>";

        foreach ($riga as $colonna => $valore) {
            $val = (string) $valore;
            if (in_array($colonna, $blacklist, true)) {
                continue;
            } elseif ($colonna === 'title') {
                $title = preg_replace('/\d{3}$/', '', $riga['title']);
                $icon_path = $icon_types[$riga['type']] ?? $icon_types['default'];
                
                $html .= "<td class='titolo'>";
                $html .= "<img class='icona icona-filetype' src='" . htmlspecialchars($icon_path) . "' alt='" . htmlspecialchars($riga['type']) . "'>";
                $html .= "<a href='" . htmlspecialchars($riga['url']) .  "'>" . htmlspecialchars($title) . "</a>";
                $html .= "</td>";
            } elseif ($colonna === 'nickname') {
                $owner_link = "utenti.php?utente=" . urlencode($riga['owner']);
                $html .= "<td class='titolo'><a href='" . htmlspecialchars($owner_link) .  "'>" . htmlspecialchars($val) . "</a></td>";
            } elseif (is_numeric($val)) {
                $html .= "<td class='numero'>" . htmlspecialchars($val) . "</td>";
            } elseif (isData($val)) {
                $html .= "<td class='data'>"   . htmlspecialchars($val) . "</td>";
            } else {
                $html .= "<td>"                . htmlspecialchars($val) . "</td>";
            }
        }
        $html .= "</tr>";
    }
    $html .= "</table>";
    return $html;
}
?>