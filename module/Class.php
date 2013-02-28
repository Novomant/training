<?php
class Base
{
	//Массив с пользовательским запросом города и даты
	public $valueUser;

	public function __construct($valueUser)
	{
		$this->valueUser = $valueUser;
	}
}

class Index
{
	//Загружаем страницу в зависимости от url
	public function loadPage()
	{
	if ($_SERVER['REQUEST_URI'] == "/training/")
	{
		include "index.php";
	}
	if ($_SERVER['REQUEST_URI'] !="/training/")
	{
		$loadPage = ltrim($_SERVER['REQUEST_URI'], "/training/");
		include $loadPage;
	}
	//При запросе погоды загружать страницы не будем
	if (isset ($_REQUEST['ok']))
	{
		unset ($loadPage);
	}
	}
}

class BaseWeather extends Base
{
}

class Weather_Day extends BaseWeather
{
}

class Weather_Today extends BaseWeather
{
}

class Weather_Tomorrow extends BaseWeather
{
}
?>