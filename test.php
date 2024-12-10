<?php
use grewi\blockip;
include scandir(__DIR__) . '/blockip.php';

//Использование со значениями по умолчанию
$blockip = new blockip();

if(!$blockip->start()){
    // Блокируем!
}


//Указываем параметры вручную
$blockip = new blockip();
$blockip->setDirFiles($dir); // Указать директорию с txt файлами 
$blockip->setIpList($txt); //Загрузить список ip текстом с разделением через запятую
$blockip->start();