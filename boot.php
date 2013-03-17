<?php
include 'module/Class.php';

//Загружаем запрошенную страницу
if  ($_SERVER['REQUEST_URI'] == "/training/")
{
		$loadClass = 'Index';
}
else
{
		$loadClass = ltrim($_SERVER['REQUEST_URI'], "/training/");
		$loadClass = rtrim($loadClass, ".php");
		$loadClass = ucfirst($loadClass);
}

$work = new $loadClass;

if (isset ($_REQUEST['ok']) && ($work instanceOf BaseWeather))
{
		$work->setRequestMode();
		if (isset($_POST['town']))
		{
				$work->setTown($_POST['town']);
		}
		if (isset($_POST['date']))
		{
				$work->setDate($_POST['date']);
		}
}

$work->doOutput();
?>