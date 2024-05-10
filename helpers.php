<?php

function formatCurrency(int $amount): string
{
    return '$' . number_format($amount / 100, 2);
}

function formatCurrencyForProducts(array $products): void
{
    foreach ($products as $product) {
        $product->price = formatCurrency($product->price);
    }
}

function addProductIds(array $products): void
{
    foreach ($products as $index => $product) {
        $product->id = (string)($index + 1);
    }
}

function copyProducts(array $products): array
{
    $copy = [];
    foreach ($products as $product) {
        $copy[] = clone $product;
    }
    return $copy;
}

function makeContainerTable($products, $summedPrice = false): array
{
    $productsView = copyProducts($products);
    if ($summedPrice) {
        foreach ($productsView as $product) {
            $product->price = $product->price * $product->quantity;
        }
    }
    formatCurrencyForProducts($productsView);
    addProductIds($productsView);
    return [
        Table::createColumn("ID", array_column($productsView, "id")),
        Table::createColumn("Name", array_column($productsView, "name")),
        Table::createColumn("Price", array_column($productsView, "price")),
        Table::createColumn("Quantity", array_map(function ($v) {
            return (string)$v;
        }, array_column($productsView, "quantity")))
    ];
}

function clearScreen() // TODO: decide if this should be used
{
    // I can't test other methods on Windows, so I'm using the hacky method just because I know it works.
    for ($i = 0; $i < 50; $i++) {
        echo "\n";
    }
    echo "\r";
}

function sortProducts(array &$products): void
{
    usort($products, function ($a, $b) {
        return $a->name < $b->name ? -1 : 1;
    });
}

function addToContainer(&$container, $product, $quantity)
{
    foreach ($container as $item) {
        if ($item->name === $product->name) {
            $item->quantity += $quantity;
            return;
        }
    }
    $item = new stdClass();
    $item->name = $product->name;
    $item->quantity = $quantity;
    $item->price = $product->price;
    $container[] = $item;
}

function removeFromContainer(&$container, $product, $quantity)
{
    foreach ($container as $index => $item) {
        if ($item->name === $product->name) {
            $item->quantity -= $quantity;
            if ($item->quantity <= 0) {
                echo "item removed\n";
                unset($container[$index]);
                $container = array_values($container); // Reindex
                return;
            }
            return;
        }
    }
    throw new InvalidArgumentException("Product not found in container");
}

function calculateProductTotalPrice($products)
{
    $totalPrice = 0;
    foreach ($products as $item) {
        $totalPrice += $item->price * $item->quantity;
    }
    return $totalPrice;
}
