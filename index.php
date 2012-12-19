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
    </body>
<?php
@$town = $_POST['town'];
@$date = $_POST['date']; //Переменные для данных, введенные пользователем. Оператор @ использован по причине остутствия ошибки, так как переменные еще не определены в начале выполнения сценария
if (isset($town) and isset($date)) //Если пользователь ввел данные в оба поля
{
    $xmlStr = file_get_contents('http://free.worldweatheronline.com/feed/weather.ashx?q='.$town.'&date='.$date.'&key=50635796a4181608121312&format=xml');// Вставляем переменные, введеные пользователем, в адрес запроса. Возвращаем строку, в которой находится xml
    $xml = simplexml_load_string($xmlStr); //Превращаем полученную строку xml в объект
         if (!isset($xml->weather[0]->tempMaxC)) // В случае отсутсвия данных выдаем ошибку
            {
                exit ("Ошибка! Нет такого города в базе данных или неправильно введены данные");
            }
    echo "Вы выбрали дату: ".$xml->weather[0]->date;
    echo "<br>Максимальная температура в этот день: ".$xml->weather[0]->tempMaxC;
    echo "<br>Минимальная температура в этот день: ".$xml->weather[0]->tempMinC;
}
?>
</html>
