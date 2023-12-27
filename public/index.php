<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Создаем контроллер
$productController = new Controller();

// Проверяем, какое действие было запрошено
if (isset($_GET['action']) && method_exists($productController, $_GET['action'])) {
    $action = $_GET['action'];
    $productController->$action(); // Вызываем соответствующий метод контроллера
} else {
    // Если действие не указано, отображаем домашнюю страницу
    include __DIR__ . '/../Views/index.php';
}

?>
