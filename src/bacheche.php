<!DOCTYPE html>
<html>

<head>
	<meta charset="utf-8">
	<meta name="generator" content="AlterVista - Editor HTML" />
	<title>SalMeet</title>
	<link rel="stylesheet" href="./css/lightmode.css">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Elms+Sans:wght@700&display=swap" rel="stylesheet">
	<link rel="icon" type="image/x-icon" href="/images/favicon.png">

	<script type="text/javascript" src="./js/jquery-2.0.0.js"></script>
</head>

<body>
	<header>
		<h1 id="hcod1">Bacheche</h1>
	</header>
	<?php
	include 'nav.html';
	include 'footer.html';
	?>

	<div id="content">
		<?php
		require_once __DIR__ . '/config/db.php';

		$stmt = $pdo->query("SELECT * FROM Bacheca");
		$bacheca = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if (empty($bacheca)) {
			echo "<p>Nessuna bachecha trovata.</p>";
		} else {
			echo "<table border='1'>";
			echo "<tr>";
			foreach (array_keys($bacheca[0]) as $colonna) {
				echo "<th>" . htmlspecialchars($colonna) . "</th>";
			}
			echo "</tr>";

			foreach ($bacheca as $riga) {
				echo "<tr>";
				foreach ($riga as $valore) {
					echo "<td>" . htmlspecialchars($valore) . "</td>";
				}
				echo "</tr>";
			}

			echo "</table>";
		}
		?>
	</div>
</body>

</html>