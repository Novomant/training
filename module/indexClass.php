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
	public $town='test';
}

class WeatherToday extends BaseWeather
{
	public function InputValue()
	{
	parent::InputTown();
	}
}
?>