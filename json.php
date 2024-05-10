<?php

function getProductsFromJSON(string $filePath): array
{
    if (!file_exists($filePath)) {
        throw new Exception("$filePath JSON file not found");
    }

    $products = json_decode(file_get_contents($filePath));

    if (!isset($products)) {
        throw new Exception("JSON file - $filePath could not be decoded");
    }

    if (!is_array($products)) {
        throw new Exception("Incorrect JSON file format - $filePath");
    }

    return $products;
}

function validateProductsFromJSON($products)
{
    $templateProduct = new stdClass();
    $templateProduct->name = "product";
    $templateProduct->price = 1;
    $templateProduct->quantity = 1;

    if (gettype($products) != "array") {
        throw new Exception("JSON products must be in an array");
    }

    $productCount = count($products);

    foreach ($products as $index => $product) {
        if (gettype($product) !== gettype($templateProduct)) {
            throw new Exception ("JSON product #$index/$productCount is not an object");
        }
        foreach ($templateProduct as $templateAttributeKey => $templateAttributeValue) {
            foreach ($product as $productAttributeKey => $productAttributeValue) {
                if ($productAttributeKey === $templateAttributeKey) {
                    if (gettype($productAttributeValue) !== gettype($templateAttributeValue)) {
                        throw new Exception ("JSON product #$index/$productCount has incorrect data type for $templateAttributeKey");
                    }
                    continue 2;
                }
            }
            throw new Exception ("JSON product #$index/$productCount is missing $templateAttributeKey");
        }
    }
}
