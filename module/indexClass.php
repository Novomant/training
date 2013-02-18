<?php
class Index
{
	function loadPageIndex()
	{
		include 'index.php';
	}
}

class BaseWeather
{
	public function GetData()
	{
		if (isset($_REQUEST['ok']))
		{
			echo "Кнопка нажата";
		}
	}

}
class WeatherToday extends BaseWeather
{
	public function InputValue()
	{
	parent::GetData();
	}
}
?>