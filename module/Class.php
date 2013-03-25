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
		$memcache = new Memcache;
		for ($this->curr = reset($this->servers); !empty($this->curr) && empty($this->info); $this->curr = next($this->servers))
		{
			$memcache -> connect($this->curr, 11211);
			$this->info = $memcache -> get($this->getTown().$this->getDate());
		}
	}

	//Разбираем дату на части
	public function separationDate()
	{
		if (!$this->isWorkServer() or !$this->info)
		{
			$this->del = explode("-", $this->date);
			// Распределяем по переменным
			list ($this->year, $this->month, $this->day) = $this->del;
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

	public function isIncorrectDate()
	{
		//Создаем объект с исключением (если объект не создался) - фиксируем ошибку
		try
		{
			$this->dateUser = new DateTime($this->getDate());
			$this->isErrorIncorrectDate=false;
		}
		catch (Exception $e)
		{
			$this->isErrorIncorrectDate=true;
		}
		return $this->isIncorrectDate();
	}

	//Создаем объект с текущей датой
	public function createObjectDate()
	{
		//Объект с текущей датой
		$this->dateToday = new DateTime();
		date_time_set($this->dateToday, 00, 00, 00);
		//Добавляем к текущей дате 7 дней
		$this->date7 = new DateTime('+7 days');
	}

	public function setRequestMode()
	{
		echo "Нажатие кнопки. Перед установкой города и даты<br>";
		parent::loadServers();
		parent::connect();
		parent::separationDate();
	}

	public function doOutput()
	{
		echo "Данные после обработки<br>";
		var_dump($this->isIncorrectDate());

	}
}

class Weather_today extends BaseWeather
{
}

class Weather_tomorrow extends BaseWeather
{

}
?>