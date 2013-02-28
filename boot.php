<?php
include 'module/Class.php';

//Массив $_POST должен содержать только пользовательские данные, поэтому удаляем $_REQUEST['ok']
if (isset ($_REQUEST['ok']))
{
	$delOk = array_search("Отправить", $_POST);
	if ($delOk !== false)
	{
		unset ($_POST[$delOk]);
	}
	$valueUser = $_POST;
	//Выбираем класс, в зависимости от того, какой запрос сделал пользователь
	if (isset($_POST['town']) and isset($_POST['date']))
	{
		$worker = new Weather_Day($valueUser);
	}
	if (isset($dateToday))
	{
		$worker = new Weather_Today($valueUser);
	}
	if (isset($dateTomorrow))
	{
		$worker = new Weather_Tomorrow($valueUser);
	}
}

//Класс загрузки страниц в зависимости от запроса в url
$loadPage = new Index();
$loadPage->loadPage();
?>