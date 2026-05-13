<?php
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