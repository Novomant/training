<?php
include 'module/indexClass.php';
$url = $_SERVER['REQUEST_URI'];

if ($url=='/training/')
{
	$obj = new Index();
	$obj->loadPageIndex();
}
?>