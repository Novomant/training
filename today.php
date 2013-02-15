<!DOCTYPE html>
<html>
		<head>
			<title>Погода на сегодня</title>
		</head>
	<body>
		<h1 align="center">Узнай погоду на сегодня в своем городе</h1>
			<p>Сегодня
				<?php
				$dateToday=new DateTime();
				$dateTodayString=date_format($dateToday, 'Y-m-d');
				echo $dateTodayString;
				?>
			</p>
				<form method="POST" action="today.php">
					Укажите ваш город: <input type="text" name="town">
					<br><br>
					<input type="submit" name="ok" value="Отправить">
				</form>
<?php
include 'module/indexClass.php';

if (isset($_REQUEST['ok']))
{
	//Создаем базовые и дневные классы погоды
	$objBaseWeather = new BaseWeather();
	echo $objBaseWeather->town;
}

?>
	</body>
</html>