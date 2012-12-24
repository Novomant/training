<?php
if (!class_exists('Memcache'))
{
    echo "Расширение Memcache не установлено. Завершение работы...";
}
else
{
    echo "Расширение Memcache установлено";
    $memcache = new Memcache;
    $memcache->connect('localhost', 11211) or die ("Не могу подключиться");
    $rand = rand();
    $memcache->set('rand', $rand, 600);

    echo "<br>Случайное число записано в хеш. Число равно: ".$rand;
    $get = $memcache ->get('rand');

    echo "<br> Получаем данные из хэша. Случайное число равно: ".$get;
}
?>
