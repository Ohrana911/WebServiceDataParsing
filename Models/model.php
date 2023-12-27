<?php

class Model
{
    private $db;

    public function __construct()
    {
        $dsn = 'mysql:host=localhost;dbname=allproducts';
        $username = 'root';
        $password = '';

        try {
            $this->db = new PDO($dsn, $username, $password);
        } catch (PDOException $e) {
            die('Connection failed: ' . $e->getMessage());
        }
    }

    public function getProducts()
    {
        // Парсим цены с localhost
        $localhostProducts = $this->parseLocalhostProducts();

        // Вставляем только новые товары в базу данных
        $this->insertNewProductsIntoDatabase($localhostProducts);

        return $localhostProducts;
    }

    public function getPriceHistory()
    {
        $stmt = $this->db->query('SELECT * FROM price_history');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getProductsFromDatabase()
    {
        $stmt = $this->db->query('SELECT * FROM products');
        $result = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }

        return $result;
    }

    private function parseLocalhostProducts()
    {
        libxml_use_internal_errors(true);

        $html = $this->curlGetPage('http://localhost:8086/read.php');
        $dom = new \DOMDocument;
        $dom->loadHTML($html);

        $rows = $dom->getElementsByTagName('tr');
        $products = [];

        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');
            if ($cells->length >= 4) {
                $idParsing = (int)$cells->item(0)->textContent;  // Предполагаем, что id_parsing находится в четвертом столбце
                $name = $cells->item(1)->textContent;
                $price = $cells->item(3)->textContent;  // Предполагаем, что цена находится в третьем столбце

                echo "Parsed Data: idParsing: $idParsing, name: $name, price: $price<br>";

                $products[] = [
                    'id_parsing' => $idParsing,
                    'name' => $name,
                    'price' => $price,
                ];
            }
        }


        libxml_clear_errors();

        return $products;
    }


    private function insertNewProductsIntoDatabase(array $parsedProducts)
    {
        $existingProducts = $this->getProductsFromDatabase();

        foreach ($parsedProducts as $product) {
            $idParsing = $product['id_parsing'];
            $name = $product['name'];
            $price = $product['price'];

            $existingProduct = $this->findProductByIdParsing($existingProducts, $idParsing);

            if ($existingProduct !== false) {
                // Продукт уже существует в базе данных
                if ($existingProduct['product_price'] != $price) {
                    // Цена изменилась, обновляем базу данных
                    $this->updateProductPrice($idParsing, $name, $price);
                }
            } else {
                // Продукта нет в базе данных, вставляем новый
                $this->insertProduct($idParsing, $name, $price);
            }
        }
    }

    private function findProductByIdParsing(array $products, $idParsing)
    {
        foreach ($products as $product) {
            if ($product['id_parsing'] == $idParsing) {
                return $product;
            }
        }

        return false;
    }


    private function insertProduct($idParsing, $name, $price)
    {
        echo "idParsing: $idParsing, name: $name, price: $price<br>";

        $stmt = $this->db->prepare('INSERT INTO products (id_parsing, product_name, product_price) VALUES (:id_parsing, :name, :price)');
        $stmt->bindParam(':id_parsing', $idParsing);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':price', $price);

        if (!$stmt->execute()) {
            echo "\nPDO::errorInfo():\n";
            print_r($stmt->errorInfo());
        }
    }

    private function findProductByName(array $products, $name)
    {
        foreach ($products as $product) {
            if ($product['product_name'] == $name) {
                return $product;
            }
        }

        return false;
    }



// Метод для вставки изменения цены в таблицу price_history
    private function insertPriceChangeHistory($product_id, $price)
    {
        $stmt = $this->db->prepare('INSERT INTO price_history (product_id, price) VALUES (:product_id, :price)');
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':price', $price);
        $stmt->execute();
        echo "Inserted into price_history for product id = $product_id, price = $price<br>";
    }


// Метод для обновления цены продукта
    private function updateProductPrice($idParsing, $name, $price)
    {
        $stmt = $this->db->prepare('UPDATE products SET product_price = :price WHERE id_parsing = :id_parsing');
        $stmt->bindParam(':id_parsing', $idParsing);
        $stmt->bindParam(':price', $price);
        $stmt->execute();

        // Вставляем изменение цены в историю
        $product = $this->getProductByIdParsing($idParsing);
        if ($product) {
            $this->insertPriceChangeHistory($product['id'], $price);
            echo "Inserted into price_history for product id = {$product['id']}, price = $price<br>";
        } else {
            echo "Product not found for id_parsing = $idParsing<br>";
        }
    }


// Метод для получения информации о продукте по id_parsing
    private function getProductByIdParsing($idParsing)
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE id_parsing = :id_parsing');
        $stmt->bindParam(':id_parsing', $idParsing);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }



    private function curlGetPage($url, $referer = 'https://google.com')
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_USERAGENT, 'HTTP_USER_AGENT - Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 YaBrowser/17.11.1.988 Yowser/2.5 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $referer);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);

        if ($response === false) {
            // Обработка ошибок при отправке запроса
            die(curl_error($ch));
        }

        curl_close($ch);

        return $response;
    }
}
?>