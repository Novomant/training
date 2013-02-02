<!DOCTYPE html>
<html>
		<head>
			<title>������</title>
		</head>
	<body>
		<h1 align="center">����� ������ �� 7 ���� ������ � ����� ���� � � ����� ������</h1>
			<form method="POST" action="weather.php">
				�����: <input type="text" name="town">
				<br>
				���� � ������� ����-��-��: <input type="text" name="date">
				<br><br>
				<input type="submit" name="ok" value="���������">
			</form>
<?php
if (isset($_REQUEST['ok']))
{
	$town = $_POST['town'];
	$date = $_POST['date'];
	$del = explode("-", $date);
	// ������������ �� ����������
	list ($year, $month, $day) = $del;
	//������������� ���������� �������� ����� ��� ���� (�����+����)
	$key = $date.$town;

	//������� ������ � ����������� (���� ������ �� ��������) - ��������� ������, ���������� ������� ���, ����� � �����
	try
	{
		$dateUser = new DateTime($date);
	}
	catch (Exception $e)
	{
		$isErrorIncorrectDate=true;
	}

	//������ � ������� �����
	$dateToday = new DateTime();
	date_time_set($dateToday, 00, 00, 00);
	//��������� � ������� ���� 7 ����
	$date7 = new DateTime('+7 days');
	//���� ���� ������������ ������ ��� ����� �� 7 ���� ������ � ������ ��� ����� ������� ����, �� ������ ���
	if ($dateUser<=$date7 and $dateUser>=$dateToday)
	{
		$isErrorData=false;
	}
	else
	{
		$isErrorData=true;;
	}

	//����������, �������� �� Memcache
	if (!class_exists('Memcache'))
	{
		$isWorksServer=false;
	}
	else
	{
		$isWorksServer=true;
		//���������� ���������� Memcache
		$memcache = new Memcache;
		//���������� ������ ��������
		include 'servers.php';
		//�������� ��������� ������� �������
		$endServer=end($servers);
		//������������ � ������� ������� �� �������. ���� ������ ����� ����������� ��� ��������� ��������� ������� �������, �� ��������� ����
		for ($curr = reset($servers); !empty($curr) && empty($info); $curr = next($servers))
		{
			$memcache -> connect($curr, 11211);
			$info = $memcache -> get($key);
		}
	}
	//���� ��� ���� �������� ���� ��� Memcache �� ��������, �� ������ ������ �� ������ ������
	if (!$info or $isWorksServer==false)
	{
		//������ Api
		$apiUrl=array(
			'http://dev.4otaku.ru/weather.php?city='.$town.'&year='.$year.'&month='.$month.'&day='.$day.'',
			'http://free.worldweatheronline.com/feed/weather.ashx?cc=no&num_of_days=2&q='.$town.'&date='.$date.'&key=50635796a4181608121312&format=xml'
		);
		//�������� ������ � ������� ������, ���������� �������� � ������ � ������, ���� ����������� ����� �� �����������
		for (true; !isset($loadApi) or $loadApi=="Fuck off, capitalist pig!"; $loadApi=file_get_contents ($apiUrl[$randApi]))
		{
			$randApi=rand(0,1);
		}
		//���� �� ������ ����� Api 1, �� �������� � �������� json
		if ($randApi==0)
		{
			//���������� ������ jons
			$json = json_decode($loadApi);
			//��������� ������ �������
			$info = array(
				"town" => $town,
				"date" => $date,
				"t_max" => $json->{'Max'},
				"t_min" => $json->{'Min'});
			//��������� ����������� � ����� �������
			$info["t_max"]=($info["t_max"] - 32)*(5/9);
			$info["t_min"]=($info["t_min"] - 32)*(5/9);
			$info["t_max"]=intval($info["t_max"]);
			$info["t_min"]=intval($info["t_min"]);
			unset($json);
		}
		//���� �� ������ ����� Api 2, �� �������� � �������� xml
		if ($randApi==1)
		{
			//���������� ���������� ������ xml � ������
			$xml = simplexml_load_string($loadApi);
			//������ � ��������
			$info = array (
				"error_1" => $xml->weather[1],
				"error_2" => $xml->error->msg);
			$isErrorData = (isset($info["error_1"]) or isset($info["error_2"]));
			//���� ��� ������, �� ��������� ������
			if (!$isErrorData)
			{
				$info = array(
					"town" => $xml->request->query->asXML(),
					"date" => $xml->weather[0]->date->asXML(),
					"t_max" => $xml->weather->tempMaxC->asXML(),
					"t_min" => $xml->weather->tempMinC->asXML());
				unset($xml);
			}
		}

		// ���� ���� ������, �� ������� ��������������
		if ($isErrorData or $isErrorIncorrectDate)
		{
			echo "������! ����������� ������� ���� ��� ������ ��� � ���� ������";
		}
		// ���� � ������ ���������� � ������� ��� ������, ���� ������� ��������� � Memcache �������� - �������� �� �� ��������� � ��������� ������������ �������
		if (!$isErrorData and !$isErrorIncorrectDate and $isWorksServer==true)
		{
			//������ ��� �������, � �������� ����� ������������
			$maxServ = count($servers)-1;
			$randServ = rand (0, $maxServ);
			//���� ������ �� ����� �� ��������� ������ (� �������� �� ��� ����������), �� ����������� �� ���� � ������������ � ����������
			if ($maxServ != $randServ)
			{
				$memcache->close($servers[$maxServ]);
				$memcache -> connect($servers[$randServ], 11211);
			}
			//���������� ������ � ��� � ������ ��� ������ ������
			$set = $memcache->set($key, $info, 604000);
		}
	}
	// ���� � ������ ���������� �� ���� ���� � ������� ��� ������ � ���� ���������� ������� �������� - ������� ��
	if (!$isErrorData and !$isErrorIncorrectDate)
	{
		echo "<br>�� ������� �����: ".$info["town"];
		echo "<br>�� ������� ����: ".$info["date"];
		echo "<br>������������ ����������� � ���� ����: ".$info["t_max"];
		echo "<br>����������� ����������� � ���� ����: ".$info["t_min"];
	}
}
?>
	</body>
</html>