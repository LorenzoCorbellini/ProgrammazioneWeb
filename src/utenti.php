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
	<header><h1 id="hcod1">Utenti</h1></header>
<?php	
	include 'nav.html';
	include 'footer.html';
?>
	<div id="content">     
		<?php
			$stmt = $pdo->query("SELECT * FROM utente");
	echo "<table border='1'>
			<tr>
				<th>Nickname</th>
				<th>Nome</th>
				<th>Cognome</th>
				<th>Data nascita</th>
			</tr>";
				
		foreach($stmt as $row){

		echo "<tr>";
			echo "<td>".$row['nickname']."</td>";
			echo "<td>".$row['nome']."</td>";
			echo "<td>".$row['cognome']."</td>";
			echo "<td>".$row['dataNascita']."</td>";
		echo "</tr>";
		}

	echo "</table>";
		?>
	</div>

</body>
</html>
