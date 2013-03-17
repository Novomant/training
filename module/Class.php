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
	public $isWorkServer;
	public $servers;

	public function setTown($town)
	{
		 $this->town = $town;
	}

	public function setDate($date)
	{
		$this->date = $date;
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
		return $this->getTown().$this->getDate();
	}

	//Проверяем, работает ли Мемкэш
	public function testMemcache()
	{
		if (!class_exists('Memcache'))
		{
			return $this->isWorkServer=false;
		}
		else
		{
			return $this->isWorkServer=true;
		}
	}

	//Если Мемкэш работает, запускаем его, подключаем список серверов
	public function loadServers()
	{
		if ($this->isWorkServer)
		{
			$memcache = new Memcache;
			//Подключаем список серверов
			include 'servers.php';
			$this->servers = $servers;
			//Подключаемся к каждому серверу по очереди. Если данные ключа заполнились или достигнут последний элемент массива, то прерываем цикл
			for ($this->curr = reset($this->servers); !empty($this->curr) && empty($this->info); $this->curr = next($this->servers))
			{
				$memcache -> connect($this->curr, 11211);
				$this->info = $memcache -> get($this->getTown().$this->getDate());
			}
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

	public function setRequestMode()
	{
		echo "Нажатие кнопки. Перед установкой города и даты<br>";
	}
	public function doOutput()
	{
		echo "Данные после обработки<br>";
		parent::testMemcache();
		parent::loadServers();
		var_dump( $this->servers);

	}
}

class Weather_today extends BaseWeather
{
}

class Weather_tomorrow extends BaseWeather
{

}
?>