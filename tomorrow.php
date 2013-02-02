<!DOCTYPE html>
<html>
		<head>
			<title>������ �� ������</title>
		</head>
	<body>
		<h1 align="center">����� ������ �� ������ � ����� ������</h1>
			<p>������
				<?
				$dateTomorrow=new DateTime('+1 days');
				$dateTomorrowString=date_format($dateTomorrow, 'Y-m-d');
				echo $dateTomorrowString;
				?>
			</p>
				<form method="POST" action="tomorrow.php">
					������� ��� �����: <input type="text" name="town">
					<br><br>
					<input type="submit" name="ok" value="���������">
				</form>
<?
if (isset($_REQUEST['ok']))
{
	$town = $_POST['town'];
	//������������� ���������� �������� ����� ��� ���� (�����)
	$key = $town.$dateTomorrowString;
	$del = explode("-", $dateTomorrowString);
	// ������������ �� ����������
	list ($year, $month, $day) = $del;

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
			'http://free.worldweatheronline.com/feed/weather.ashx?cc=no&num_of_days=2&q='.$town.'&date='.$dateTomorrowString.'&key=50635796a4181608121312&format=xml'
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
					"t_max" => $xml->weather->tempMaxC->asXML(),
					"t_min" => $xml->weather->tempMinC->asXML());
				unset($xml);
			}
		}

		// ���� ���� ������, �� ������� ��������������
		if ($isErrorData)
		{
			echo "������! ������ ��� � ���� ������";
		}
		// ���� � ������ ���������� � ������� ��� ������ � Memcache �������� - �������� �� �� ��������� � ��������� ������������ �������
		if (!$isErrorData and $isWorksServer==true)
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
			//���������� ������ � ��� � ������ ��� ������ �����
			$set = $memcache->set($key, $info, 86400);
		}
	}
	// ���� � ������ ���������� �� ���� ���� � ������� ��� ������ - ������� ��
	if (!$isErrorData)
	{
		echo "<br>�� ������� �����: ".$info["town"];
		echo "<br>������������ ����������� � ���� ����: ".$info["t_max"];
		echo "<br>����������� ����������� � ���� ����: ".$info["t_min"];
	}
}
?>
	</body>
</html>