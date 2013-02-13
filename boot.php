<?php
class MyClass
{
	const ConstValue = 'Значение константы';
}

class OtherClass extends MyClass
{
	public static $my_static = 'Статическая переменная';

	public static function doubleColon()
	{
		echo parent::ConstValue."<br>";
		echo self::$my_static."<br>";
	}
}

$classname = 'OtherClass';
echo $classname::doubleColon();

OtherClass::doubleColon();
?>