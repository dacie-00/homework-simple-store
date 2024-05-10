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
    if (gettype($products) != "array") {
        throw new Exception("JSON products must be in an array");
    }

    $templateProduct = new stdClass();
    $templateProduct->name = "product";
    $templateProduct->price = 1;
    $templateProduct->quantity = 1;

    $productCount = count($products) - 1;

    foreach ($products as $index => $product) {
        $templateProductType = gettype($templateProduct);
        if (gettype($product) !== $templateProductType) {
            throw new Exception ("JSON product $index/$productCount must be $templateProductType");
        }
        foreach ($templateProduct as $templateAttributeKey => $templateAttributeValue) {
            foreach ($product as $productAttributeKey => $productAttributeValue) {
                if ($productAttributeKey === $templateAttributeKey) {
                    $templateAttributeValueType = gettype($templateAttributeValue);
                    if (gettype($productAttributeValue) !== gettype($templateAttributeValue)) {
                        throw new Exception ("JSON product $index/$productCount data type for $templateAttributeKey must be $templateAttributeValueType");
                    }
                    continue 2;
                }
            }
            throw new Exception ("JSON product $index/$productCount is missing $templateAttributeKey");
        }
    }
}
