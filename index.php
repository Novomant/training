<?php $a = microtime(true); ?>
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
//Проверка нажатия кнопки
if (isset($_REQUEST['ok']))
{
    $town = $_POST['town'];
    $date = $_POST['date'];
    // Вставляем переменные, введеные пользователем, в адрес запроса. Возвращаем строку, в которой находится xml
$b = microtime(true);
    $xmlStr = file_get_contents('http://free.worldweatheronline.com/feed/weather.ashx?cc=no&num_of_days=2&q='.$town.'&date='.$date.'&key=50635796a4181608121312&format=xml');
$c = microtime(true);
    //Превращаем полученную строку xml в объект
    $xml = simplexml_load_string($xmlStr);
    // Если пользователь вводит неверную дату или города нет в базе
    if (isset($xml->weather[1]) or (isset($xml->error->msg)) )
    {
        echo "Ошибка! Неправильно введена дата или города нет в базе данных";
    }
    //Если же пользователь ввел все верно, выдаем данные о запрашиваемой дате и погоду
    else
    {
        echo "Вы выбрали дату: ".$xml->weather[0]->date;
        echo "<br>Максимальная температура в этот день: ".$xml->weather[0]->tempMaxC;
        echo "<br>Минимальная температура в этот день: ".$xml->weather[0]->tempMinC;
    }
}
?>
    </body>
</html>
<?php 
if (isset($_REQUEST['ok']))
{
    $d = microtime(true); 
    echo sprintf("<br />Времени потрачено всего %01.2f секунд, из них на связь с api ушло %01.4f%% времени.", $d - $a, 100 * ($c - $b) / ($d - $a));
}
