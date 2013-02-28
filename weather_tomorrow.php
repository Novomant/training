<!DOCTYPE html>
<html>
		<head>
			<title>Погода на завтра</title>
		</head>
	<body>
		<h1 align="center">Узнай погоду на завтра в своем городе</h1>
			<p>Завтра
				<?php
				$dateTomorrow = new DateTime('+1 days');
				$dateTomorrowString=date_format($dateTomorrow, 'Y-m-d');
				echo $dateTomorrowString;
				?>
			</p>
				<form method="POST" action="weather_tomorrow.php">
					Укажите ваш город: <input type="text" name="town">
					<br><br>
					<input type="submit" name="ok" value="Отправить">
				</form>
<?php

?>
	</body>
</html>