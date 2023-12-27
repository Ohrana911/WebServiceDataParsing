<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключение к базе данных
$dsn = 'mysql:host=localhost;dbname=allproducts';
$username = 'root';
$password = '';

try {
    $db = new PDO($dsn, $username, $password);
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

// Запрос для получения уникальных Product ID из базы данных
$query = "SELECT DISTINCT product_id FROM price_history";
$stmt = $db->prepare($query);
$stmt->execute();
$productIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Подготовка данных для передачи в JavaScript
$chartData = [];

foreach ($productIds as $productId) {
    // Запрос для получения данных
    $query = "SELECT timestamp, price FROM price_history WHERE product_id = :product_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $productId);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Подготовка данных для передачи в JavaScript
    $chartData[$productId] = [
        'labels' => [],
        'prices' => []
    ];

    foreach ($data as $index => $row) {
        $chartData[$productId]['labels'][] = $index;
        $chartData[$productId]['prices'][] = $row['price'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Price History Chart</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<h1>Price History Chart</h1>

<!-- Отображение графиков для каждого Product ID -->
<?php foreach ($chartData as $productId => $data): ?>
    <h2>Product ID: <?php echo $productId; ?></h2>
    <canvas id="priceChart_<?php echo $productId; ?>" width="800" height="400"></canvas>

    <script>
        // Получение данных из PHP
        var data_<?php echo $productId; ?> = <?php echo json_encode($data); ?>;

        // Получение контекста холста
        var ctx_<?php echo $productId; ?> = document.getElementById('priceChart_<?php echo $productId; ?>').getContext('2d');

        // Создание графика
        var priceChart_<?php echo $productId; ?> = new Chart(ctx_<?php echo $productId; ?>, {
            type: 'line',
            data: {
                labels: data_<?php echo $productId; ?>.labels,
                datasets: [{
                    label: 'Price History',
                    data: data_<?php echo $productId; ?>.prices,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 2,
                    fill: false
                }]
            },
            options: {
                scales: {
                    x: {
                        type: 'linear',
                        position: 'bottom'
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
<?php endforeach; ?>
</body>
</html>
