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
    //Проверяем, установлен ли Memcache
    if (!class_exists('Memcache'))
    {
        echo "Расширение Memcache не установлено.";
    }
    else
    {
        echo "Расширение Memcache установлено";
    }
    //Подключаем расширение Memcache
    $memcache = new Memcache;
    //Подключаем список серверов
    include 'servers.php';
    //Устанавливаем уникальное значение ключа для кэша (город+дата)
    $town = $_POST['town'];
    $date = $_POST['date'];
    $key = $date.$town;
    //Подключаемся к первому серверу
    $memcache -> connect($server_1, 11211);
    //Получаем данные из кэша
	$info = $memcache -> get($key);
	//Если данных нет на первом сервере, то обращаемся к остальным, по очереди
	if (!$info)
	{
		$memcache -> connect($server_2, 11211);
		$info = $memcache -> get($key);
		if (!$info)
		{
			$memcache -> connect($server_3, 11211);
			$info = $memcache -> get($key);
			if (!$info)
			{
				$memcache -> connect($server_4, 11211);
				$info = $memcache -> get($key);
			}
		}
	}

    //Если кэш всех серверов пуст, делаем запрос на сервер погоды
    if (!$info)
    {
        //Подключаемся к случайному серверу для записи данных
        $rand = rand(1, 4);
        if ($rand == 1)
        {
            $memcache -> connect($server_1, 11211);
        }
        if ($rand == 2)
        {
            $memcache -> connect($server_2, 11211);
        }
        if ($rand == 3)
        {
            $memcache -> connect($server_3, 11211);
        }
        if ($rand == 4)
        {
            $memcache -> connect($server_4, 11211);
        }
        $xmlStr = file_get_contents('http://free.worldweatheronline.com/feed/weather.ashx?cc=no&num_of_days=2&q='.$town.'&date='.$date.'&key=50635796a4181608121312&format=xml');
        //Превращаем полученную строку xml в объект
        $xml = simplexml_load_string($xmlStr);
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

        // Проверим, нет ли ошибки в данных
        $isErrorData = (isset($info["error_1"]) || isset($info["error_2"]));
        // Если в данных полученных с сервера нет ошибки - сохраним их
        if (!$isErrorData)
        {
            //записываем данные по городу и дате в переменную, которая является значением ключа кэша
            $set = $memcache->set($key, $info, 604000);
        }
    }
    else
    {
        // В данных полученных из кеша ошибки быть не может, мы их туда просто не сохраняем.
        $isErrorData = false;
    }

    // Если в данных полученные из кеша либо с сервера нет ошибки - выведем их
    if (!$isErrorData)
    {
        echo "<br>Вы выбрали город: ".$info["town"];
        echo "<br>Вы выбрали дату: ".$info["date"];
        echo "<br>Максимальная температура в этот день: ".$info["t_max"];
        echo "<br>Минимальная температура в этот день: ".$info["t_min"];
    }
    // Если есть ошибка - выведем ее
    else
    {
        echo "Ошибка! Неправильно введена дата или города нет в базе данных";
    }
}
?>
	</body>
</html>
