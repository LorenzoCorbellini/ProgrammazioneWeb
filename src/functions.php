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
            elseif (isData($val))  echo "<td class='data'>"   . formattaData($val) . "</td>";
            else                   echo "<td>"                 . htmlspecialchars($val) . "</td>";
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