<?php

include __DIR__ . '/../Models/model.php';

class controller
{
    private $productModel;

    public function __construct()
    {
        $this->productModel = new Model();
    }

    public function showView($view)
    {
        include __DIR__ . '/../Views/' . $view . '.php';
    }

    public function showProductsView()
    {
        $productsFromWeb = $this->productModel->getProducts();
        $productsFromDatabase = $this->productModel->getProductsFromDatabase();

        $this->showView('view');
        $this->showView('products');
    }

    public function showDynamicView()
    {
        $this->showView('dynamic');
    }
}
