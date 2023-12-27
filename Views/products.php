<?php
require_once '../Models/model.php'; // Укажите правильный путь к вашему файлу model.php

$model = new Model();
$products = $model->getProductsFromDatabase();

// Группировка товаров по названию
$groupedProducts = [];
foreach ($products as $product) {
    $name = $product['product_name'];
    $price = $product['product_price'];
    $groupedProducts[$name][] = $price;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
    <style>
        table {
            border-collapse: collapse;
            width: 50%;
            margin: 20px;
        }
        th, td {
            border: 1px solid #dddddd;
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>

<h2>Products</h2>

<table>
    <tr>
        <th>Name</th>
        <th>Price</th>
        <th>Discount</th>
    </tr>

    <?php foreach ($groupedProducts as $name => $prices): ?>
        <?php
        $maxPrice = max($prices); // Находим максимальную цену
        ?>
        <?php $firstPrice = array_shift($prices); ?>
        <tr>
            <td rowspan="<?php echo count($prices) + 1; ?>"><?php echo $name; ?></td>
            <td><?php echo $firstPrice; ?></td>
            <td><?php echo ($maxPrice === $firstPrice) ? '0%' : ''; ?></td>
        </tr>
        <?php foreach ($prices as $price): ?>
            <?php
            $discount = ($maxPrice - $price) / $maxPrice * 100; // Вычисляем скидку
            ?>
            <tr>
                <td><?php echo $price; ?></td>
                <td><?php echo ($discount === 0) ? '' : number_format($discount, 2) . '%'; ?></td>
            </tr>
        <?php endforeach; ?>
    <?php endforeach; ?>

</table>

</body>
</html>
