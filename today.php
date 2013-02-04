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
if (isset($_REQUEST['ok']))
{
	$town = $_POST['town'];
	//Устанавливаем уникальное значение ключа для кэша (город+дата)
	$key = $town.$dateTodayString;
	$del = explode("-", $dateTodayString);
	// Распределяем по переменным
	list ($year, $month, $day) = $del;

	//Определяем, работает ли Memcache
	if (!class_exists('Memcache'))
	{
		$isWorksServer=false;
	}
	else
	{
		$isWorksServer=true;
		//Подключаем расширение Memcache
		$memcache = new Memcache;
		//Подключаем список серверов
		include 'servers.php';
		//Получаем последний элемент массива
		$endServer=end($servers);
		//Подключаемся к каждому серверу по очереди. Если данные ключа заполнились или достигнут последний элемент массива, то прерываем цикл
		for ($curr = reset($servers); !empty($curr) && empty($info); $curr = next($servers))
		{
			$memcache -> connect($curr, 11211);
			$info = $memcache -> get($key);
		}
	}

	//Если кэш всех серверов пуст или Memcache не работает, то делаем запрос на сервер погоды
	if (!$info or $isWorksServer==false)
	{
		//Список Api
		$apiUrl=array(
			'http://dev.4otaku.ru/weather.php?city='.$town.'&year='.$year.'&month='.$month.'&day='.$day.'',
			'http://free.worldweatheronline.com/feed/weather.ashx?cc=no&num_of_days=2&q='.$town.'&date='.$dateTodayString.'&key=50635796a4181608121312&format=xml'
		);
		//Получаем данные с сервера погоды, выбранного случайно и только в случае, если запрошенный город не иностранный
		for (true; !isset($loadApi) or $loadApi=="Fuck off, capitalist pig!"; $loadApi=file_get_contents ($apiUrl[$randApi]))
		{
			$randApi=rand(0,1);
		}
		//Если на рандом выпал Api 1, то работаем с форматом json
		if ($randApi==0)
		{
			//Декодируем строку jons
			$json = json_decode($loadApi);
			//Заполняем массив данными
			$info = array(
				"town" => $town,
				"t_max" => $json->{'Max'},
				"t_min" => $json->{'Min'});
			//Переводим температуру в шкалу Цельсия
			$info["t_max"]=($info["t_max"] - 32)*(5/9);
			$info["t_min"]=($info["t_min"] - 32)*(5/9);
			$info["t_max"]=intval($info["t_max"]);
			$info["t_min"]=intval($info["t_min"]);
			unset($json);
		}
		//Если на рандом выпал Api 2, то работаем с форматом xml
		if ($randApi==1)
		{
			//Превращаем полученную строку xml в объект
			$xml = simplexml_load_string($loadApi);
			//Массив с ошибками
			$info = array (
				"error_1" => $xml->weather[1],
				"error_2" => $xml->error->msg);
			$isErrorData = (isset($info["error_1"]) or isset($info["error_2"]));
			//Если нет ошибки, то заполняем массив
			if (!$isErrorData)
			{
				$info = array(
					"town" => $xml->request->query->asXML(),
					"t_max" => $xml->weather->tempMaxC->asXML(),
					"t_min" => $xml->weather->tempMinC->asXML());
				unset($xml);
			}
		}

		// Если есть ошибки, то выводим предупреждение
		if ($isErrorData)
		{
			echo "Ошибка! Города нет в базе данных";
		}
		// Если в данных полученных с сервера нет ошибки и Memcache работает - сохраним их на случайном и последнем подключенном сервере
		if (!$isErrorData and $isWorksServer==true)
		{
			//Рандом для сервера, к которому будем подключаться
			$maxServ = count($servers)-1;
			$randServ = rand (0, $maxServ);
			//Если рандом не выпал на последний сервер (к которому мы уже подключены), то отключаемся от него и подключаемся к рандомному
			if ($maxServ != $randServ)
			{
				$memcache->close($servers[$maxServ]);
				$memcache -> connect($servers[$randServ], 11211);
			}
			//Записываем данные в кэш и храним эти данные сутки
			$set = $memcache->set($key, $info, 86400);
		}
	}
	// Если в данных полученные из кэша либо с сервера нет ошибки - выведем их
	if (!$isErrorData)
	{
		echo "<br>Вы выбрали город: ".$info["town"];
		echo "<br>Максимальная температура в этот день: ".$info["t_max"];
		echo "<br>Минимальная температура в этот день: ".$info["t_min"];
	}
}
?>
	</body>
</html>