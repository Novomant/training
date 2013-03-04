<?php
include 'module/Class.php';

//Загружаем запрошенную страницу
if  ($_SERVER['REQUEST_URI'] == "/training/")
	{
		include "index.php";
	}
	if ($_SERVER['REQUEST_URI'] !="/training/")
	{
		$loadPage = ltrim($_SERVER['REQUEST_URI'], "/training/");
		include $loadPage;
	}

if (isset ($_REQUEST['ok']))
{
	//Сохраняем данные пользователя в переменные
	$town = $_POST['town'];
	$date = $_POST['date'];

	//Выбираем загружаемый класс
	$loadClass = rtrim($loadPage, ".php");
	$loadClass = ucfirst($loadClass);
	$work = new $loadClass;
	$work->setTown($town);
}


?>