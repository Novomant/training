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
//Подключаем расширение Memcache
$memcache = new Memcache;
$memcache->connect('localhost', 11211);
    if (isset($_REQUEST['ok']))
    {
        $town = $_POST['town'];
        $date = $_POST['date'];
        //Устанавливаем уникальное значение ключа для хэша (город+дата)
        $key = $date."and".$town;
        //Получаем данные из хэша
        $get = $memcache->get($key);
        echo $get;
        //Если хэш пуст, делаем запрос на сервер погоды
        if ($get == null)
        {
            $xmlStr = file_get_contents('http://free.worldweatheronline.com/feed/weather.ashx?cc=no&num_of_days=2&q='.$town.'&date='.$date.'&key=50635796a4181608121312&format=xml');
            //Превращаем полученную строку xml в объект
            $xml = simplexml_load_string($xmlStr);
            //В случае неверного запроса придет одно из этих двух значений. Выводим ошибку
            if (isset($xml->weather[1]) or (isset($xml->error->msg)) )
            {
                echo "Ошибка! Неправильно введена дата или города нет в базе данных";
            }
            //Если запрос корректен, то выводим информацию по городу и дате
            else
            {
                 //Записываем данные по городу и дате в переменную, которая является значением ключа хэша
                $info ="<br>Вы выбрали город: ".$xml->request->query->asXML()."<br>Вы выбрали дату: ".$xml->weather[0]->date->asXML()."<br>Максимальная температура в этот день: ".$xml->weather[0]->tempMaxC->asXML()."<br>Минимальная температура в этот день: ".$xml->weather[0]->tempMinC->asXML();
                $set = $memcache->set($key, $info);
                echo "<br>Вы выбрали город: ".$xml->request->query;
                echo "<br>Вы выбрали дату: ".$xml->weather->date;
                echo "<br>Максимальная температура в этот день: ".$xml->weather->tempMaxC;
                echo "<br>Минимальная температура в этот день: ".$xml->weather->tempMinC;
            }
        }
    }
?>
    </body>
</html>
