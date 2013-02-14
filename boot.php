<?php
class index
{
	function outIndex()
	{
		include 'index.php';
	}
}
echo $_SERVER['REQUEST_URI'];
$objIndex = new index();
$url = $_SERVER['REQUEST_URI'];
if ($url=='/training/')
{
	$objIndex->outIndex();
}
if ($url=='/training/today.php')
{
	include 'indexClass.php';
}

?>