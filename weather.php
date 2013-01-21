<!DOCTYPE html>
<html>
  	<head>
			<title>Погода</title>
		</head>
	<body>
		<form method="POST" action="/">
			Город: <input type="text" name="town">
			<br>
			Дата в формате ГГГГ-ММ-ДД: <input type="text" name="date">
			<br><br>
			<input type="submit" name="ok" value="Отправить">
		</form>
<?php
if (isset($_REQUEST['ok']))
{
	//Устанавливаем уникальное значение ключа для кэша (город+дата)
	$town = $_POST['town'];
	$date = $_POST['date'];
	$key = $date.$town;
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
		$xmlStr = file_get_contents('http://free.worldweatheronline.com/feed/weather.ashx?cc=no&num_of_days=2&q='.$town.'&date='.$date.'&key=50635796a4181608121312&format=xml');
		//Превращаем полученную строку xml в объект
		$xml = simplexml_load_string($xmlStr);
		//Массив с ошибками
		$info = array (
			"error_1" => $xml->weather[1],
			"error_2" => $xml->error->msg
		);
		$isErrorData = (isset($info["error_1"]) or isset($info["error_2"]));
		// Если ошибка есть
		if ($isErrorData)
		{
			echo "Ошибка! Неправильно введена дата или города нет в базе данных";
		}
		//Если ошибки нет
		if (!$isErrorData)
			//Создаем массив, значения которого запишутся в ключ переменной для кэша
			$info = array(
			"town" => $xml->request->query->asXML(),
			"date" => $xml->weather[0]->date->asXML(),
			"error_1" => $xml->weather[1],
			"error_2" => $xml->error->msg,
			"t_max" => $xml->weather->tempMaxC->asXML(),
			"t_min" => $xml->weather->tempMinC->asXML()
		);
		unset($xml);

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
			//Записываем данные в кэш
			$set = $memcache->set($key, $info, 604000);
		}
	}

	// Если в данных полученные из кэша либо с сервера нет ошибки - выведем их
	if (!$isErrorData)
	{
		echo "<br>Вы выбрали город: ".$info["town"];
		echo "<br>Вы выбрали дату: ".$info["date"];
		echo "<br>Максимальная температура в этот день: ".$info["t_max"];
		echo "<br>Минимальная температура в этот день: ".$info["t_min"];
	}
}
?>
	</body>
</html>
