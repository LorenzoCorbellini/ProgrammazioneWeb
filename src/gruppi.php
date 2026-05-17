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
		<h1 id="hcod1">Gruppi</h1>
	</header>
	
	<div class="main-container">
	<aside class="sidebar">
		<?php
		include 'nav.html';
		?>
	</aside>

	<div id="content">
		
		<?php
		
			$sql = "
			SELECT 
				utente.nickname,
				gruppo.nome,
				gruppo.dataCreazione
			FROM gruppo
			JOIN utente
			ON gruppo.creatoDa = utente.codice
			";

			$stmt = $pdo->query($sql);
			$stmt->setFetchMode(PDO::FETCH_ASSOC);

			echo "<table border='1'>";
			echo "
			<tr>
		
				<th>Creato da</th>
				<th>Nome gruppo</th>
				<th>Data creazione</th>
			</tr>
			";

			foreach($stmt as $row){

				echo "<tr>";

				echo "<td>".$row['codice']."</td>";
				echo "<td>".$row['nickname']."</td>";
				echo "<td>".$row['nome']."</td>";
				echo "<td>".$row['dataCreazione']."</td>";

				echo "</tr>";
			}

			echo "</table>";
		?>
	</div>
	</div>

	<?php include 'footer.html'; ?>
</body>

</html>