<?php

class Base
{
	public function __construct()
	{
		echo "<!DOCTYPE html>
				<html>
					<head>
						<title>Погода</title>
					</head>
				<body>";
	}

	public function __destruct()
	{
		echo "</body>
				</html>";
	}
}

class Index extends Base
{
	public  function __construct()
	{
		parent::__construct();
		echo "<h1>Узнайте погоду за завтра, сегодня или на любой ближайший день на нашем сайте</h1>
			<ul>
				<li><a href='weather_today.php'>Погода на сегодня</a></li>
				<li><a href='weather_tomorrow.php'>Погода на завтра</a></li>
				<li><a href='weather_day.php'>Погода на конкретный день</a></li>
			</ul>";
	}

	public function doOutput(){}
}

class BaseWeather extends Base
{
	public $town;
	public $date;
	public $isErrorIncorrectDate = false;
	public $isErrorDate = false;
	public $isErrorXML = false;

	public function setTown($town)
	{
		 $this->town = $town;
	}

	public function setDate($date)
	{
		//Создаем объект с исключением (если объект не создался) - фиксируем ошибку
		try
		{
			$this->date = new DateTime($date);
		}
		catch (Exception $e)
		{
			$this->isErrorIncorrectDate = true;
		}
	}

	public function getTown()
	{
		return $this->town;
	}

	public function getDate()
	{
		return $this->date;
	}

	//Ключ для мемкэша
	public function getKeyMemcache()
	{
		return $this->getTown().$this->getDate()->format('Y-m-d');
	}

	//Проверяем, работает ли Мемкэш
	public function isWorkServer()
	{
		return class_exists('Memcache');
	}

	//Если Мемкэш работает, подключаем список серверов
	public function loadServers()
	{
		if ($this->isWorkServer())
		{
			//Подключаем список серверов
			include 'servers.php';
			$this->servers = $servers;
		}
	}

	//Подключаемся к каждому серверу по очереди. Если данные ключа заполнились или достигнут последний элемент массива, то прерываем цикл
	public function connect()
	{
		$this->memcache = new Memcache;
		for ($curr = reset($this->servers); !empty($curr) && empty($this->info); $curr = next($this->servers))
		{
			$this->memcache -> connect($curr, 11211);
			$this->info = $this->memcache -> get($this->getTown().$this->getDate()->format('Y-m-d'));
		}
	}

	public function loadApi()
	{
		//Если кэш всех серверов пуст или Memcache не работает, то делаем запрос на сервер погоды
		if (!$this->info or $this->isWorkServer() == false)
		{
			$Y = $this->getDate()->format('Y');
			$m = $this->getDate()->format('m');
			$d = $this->getDate()->format('d');
			$Ymd = $this->getDate()->format('Y-m-d');
			//Список Api
			$apiUrl = array(
				'http://dev.4otaku.ru/weather.php?city='.$this->getTown().'&year='.$Y.'&month='.$m.'&day='.$d.'',
				'http://free.worldweatheronline.com/feed/weather.ashx?cc=no&num_of_days=2&q='.$this->getTown().'&date='.$Ymd.'&key=50635796a4181608121312&format=xml'
			);
			//Получаем данные с сервера погоды, выбранного случайно и только в случае, если запрошенный город не иностранный
			for (true; !isset($this->loadApi) or $this->loadApi == "Fuck off, capitalist pig!"; $this->loadApi = file_get_contents ($apiUrl[$this->randApi]))
			{
				$this->randApi = rand(0,1);
			}
		}
	}

	public function decodeJson()
	{
		if (!$this->info or $this->isWorkServer() == false)
		{
			//Если на рандом выпал Api 1, то работаем с форматом json
			if ($this->randApi==0)
			{
				//Декодируем строку json
				$json = json_decode($this->loadApi);
				//Заполняем массив данными
				$this->info = array(
					"town" => $this->getTown(),
					"t_max" => $json->{'Max'},
					"t_min" => $json->{'Min'});
				//Переводим температуру в шкалу Цельсия
				$this->info["t_max"]=($this->info["t_max"] - 32)*(5/9);
				$this->info["t_min"]=($this->info["t_min"] - 32)*(5/9);
				$this->info["t_max"]=intval($this->info["t_max"]);
				$this->info["t_min"]=intval($this->info["t_min"]);
				unset($json);
			}
		}
	}

	public function decodeXml()
	{
		if (!$this->info or $this->isWorkServer() == false)
		{
			//Если на рандом выпал Api 2, то работаем с форматом xml
			if ($this->randApi==1)
			{
				//Превращаем полученную строку xml в объект
				$xml = simplexml_load_string($this->loadApi);
				#var_dump($xml);
				//Массив с ошибками
				$this->info = array (
					"error_1" => $xml->weather[1],
					"error_2" => $xml->error->msg);
				if (isset($this->info["error_1"]) or isset($this->info["error_2"]))
				{
					$this->isErrorXML = true;
				}
				//Если нет ошибки, то заполняем массив
				if (!$this->isErrorXML)
				{
					$this->info = array(
						"town" => $xml->request->query->asXML(),
						"t_max" => $xml->weather->tempMaxC->asXML(),
						"t_min" => $xml->weather->tempMinC->asXML());
					unset($xml);
				}
			}
		}
	}

	public function printError()
	{
		// Если есть ошибки, то выводим предупреждение
		if ($this->isErrorXML or $this->isErrorDate or $this->isErrorIncorrectDate)
		{
			echo "Ошибка! Города нет в базе данных или неверно введена дата.";
		}
	}

	public function saveInServer()
	{
		// Если в данных полученных с сервера нет ошибки и Memcache работает - сохраним их на случайном и последнем подключенном сервере
		if (!$this->isErrorXML and !$this->isErrorDate and !$this->isErrorIncorrectDate and $this->isWorkServer())
		{
			//Рандом для сервера, к которому будем подключаться
			$maxServ = count($this->servers)-1;
			$randServ = rand (0, $maxServ);
			//Если рандом не выпал на последний сервер (к которому мы уже подключены), то отключаемся от него и подключаемся к рандомному
			if ($maxServ != $randServ)
			{
				$this->memcache->close($this->servers[$maxServ]);
				$this->memcache -> connect($this->servers[$randServ], 11211);
			}
			//Записываем данные в кэш и храним эти данные сутки
			$this->memcache->set($this->getKeyMemcache(), $this->info, 86400);
		}
	}

	public function printInfo()
	{
		//Если нет ошибок - выводим данные
		if (!$this->isErrorXML and !$this->isErrorIncorrectDate and !$this->isErrorDate)
		{
			echo "<br>Вы выбрали город: ".$this->info["town"];
			echo "<br>Максимальная температура в этот день: ".$this->info["t_max"];
			echo "<br>Минимальная температура в этот день: ".$this->info["t_min"];
		}
	}
}
class Weather_day extends BaseWeather
{
	public  function __construct()
	{
		parent::__construct();
		echo '<h1 align="center">Узнай погоду на 7 дней вперед в любой день и в любом городе</h1>
					<form method="POST" action="weather_day.php">
						Город: <input type="text" name="town">
						<br>
						Дата в формате ГГГГ-ММ-ДД: <input type="text" name="date">
						<br><br>
						<input type="submit" name="ok" value="Отправить">
					</form>';
	}

	//Создаем объект с текущей датой
	public function isErrorInDate()
	{
		//Добавляем к текущей дате 7 дней
		$date7 = new DateTime('+7 days');
		date_time_set($date7, 00, 00, 00);
		$dateToday = new DateTime();
		date_time_set ($dateToday, 00, 00, 00);
		$nowDate = new DateTime();
		//Если полученная дата не входит в недельный интервал
		if ($this->getDate() <= $date7 and $this->getDate() >= $dateToday)
			{
				$this->isErrorDate = false;
			}
			else
			{
				$this->isErrorDate = true;;
			}
		//Если пользователь ничего не ввел в поле даты
		if ($this->getDate() == $nowDate)
		{
			$this->isErrorDate = true;
		}
	}

	public function setRequestMode()
	{

	}

	public function doOutput()
	{
		#var_dump($this->getDate());
		parent::isWorkServer();
		parent::loadServers();
		parent::connect();
		$this->isErrorInDate();
		parent::loadApi();
		parent::decodeJson();
		parent::decodeXml();
		parent::printError();
		parent::saveInServer();
		#$this->memcache->flush();
		parent::printInfo();
		#var_dump($this->isErrorDate);
		#var_dump($this->isErrorXML);
		#var_dump($this->isErrorIncorrectDate);
	}
}

class Weather_today extends BaseWeather
{
}

class Weather_tomorrow extends BaseWeather
{
}
?>