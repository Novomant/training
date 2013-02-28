<!DOCTYPE html>
<html>
		<head>
			<title>Погода</title>
		</head>
	<body>
		<h1 align="center">Узнай погоду на 7 дней вперед в любой день и в любом городе</h1>
			<form method="POST" action="weather_day.php">
				Город: <input type="text" name="town">
				<br>
				Дата в формате ГГГГ-ММ-ДД: <input type="text" name="date">
				<br><br>
				<input type="submit" name="ok" value="Отправить">
			</form>
<?php

?>
		</body>
</html>