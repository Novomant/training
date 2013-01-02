<!DOCTYPE html>
<html>
        <head>
            <title>Погода</title>
        </head>
    <body>
        <form method="POST" action="/">
            Город: <input type="text" name="town">
            <br>
            Дата в формате ГГГГ-ММ-ДД: <input type="text" name="date">
            <br><br>
            <input type="submit" name="ok" value="Отправить">
        </form>
<?php
if (isset($_REQUEST['ok']))
{
    //Подключаем расширение Memcache
    $memcache = new Memcache;
    $memcache -> connect('localhost', 11211);
    $town = $_POST['town'];
    $date = $_POST['date'];
    //Устанавливаем уникальное значение ключа для хэша (город+дата)
    $key = $date."and".$town;
    //Получаем данные из хэша
    $get = $memcache -> get($key);
    //В случае наличия ячейки в полученном из хэша массиве выводим данные о погоде
    if ($get["t_max"] != null)
    {
        echo "<br>Вы выбрали город: ".$get["town"];
        echo "<br>Вы выбрали дату: ".$get["date"];
        echo "<br>Максимальная температура в этот день: ".$get["t_max"];
        echo "<br>Минимальная температура в этот день: ".$get["t_min"];
    }
    //Если хэш пуст, делаем запрос на сервер погоды
    if ($get == null)
    {
        $xmlStr = file_get_contents('http://free.worldweatheronline.com/feed/weather.ashx?cc=no&num_of_days=2&q='.$town.'&date='.$date.'&key=50635796a4181608121312&format=xml');
        //Превращаем полученную строку xml в объект
        $xml = simplexml_load_string($xmlStr);
        //Создаем массив, значения которого запишутся в ключ переменной для хэша
        $info = array(
        "town" => $xml->request->query->asXML(),
        "date" => $xml->weather[0]->date->asXML(),
        "error_1" => $xml->weather[1],
        "error_2" => $xml->error->msg,
        "t_max" => $xml->weather->tempMaxC->asXML(),
        "t_min" => $xml->weather->tempMinC->asXML());
        unset($xml);
        //В случае неверного запроса придет одно из этих двух значений. Выводим ошибку
        if (isset($info["error_1"]) or (isset($info["error_2"])))
        {
            echo "Ошибка! Неправильно введена дата или города нет в базе данных";
        }
        //Если запрос к серверу погоды корректен, то
        else
        {
             //записываем данные по городу и дате в переменную, которая является значением ключа хэша
            $set = $memcache->set($key, $info, 604000);
            //и выводим данные по городу и дате
            echo "<br>Вы выбрали город: ".$info["town"];
            echo "<br>Вы выбрали дату: ".$info["date"];
            echo "<br>Максимальная температура в этот день: ".$info["t_max"];
            echo "<br>Минимальная температура в этот день: ".$info["t_min"];
        }
    }
}
?>
    </body>
</html>
