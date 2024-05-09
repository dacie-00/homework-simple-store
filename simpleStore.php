<?php

//Create a simple store that allows user to:
//
//
//- Display list of products, their names, price tag
//- Add item to a cart (not purchase right away as single item) (select product and enter amount)
//- Display items in the cart, their price tag and total amount for cart (make sure you count in amount of items)
//- Purchase cart when items in the cart
//
//!!!!!!!
//Products within the store MUST come from a FILE and not defined as inline objects, that means you should check about
//reading file and using JSON format (there is a link and video about JSON format in the Materials section)
//There must be VALIDATION for every possible scenario you can think of. It's NOT required to have customer/payer object
//that contains cash as assumption is that the customer CAN afford whole cart.
//
//!!!! THIS MUST BE DONE IN SEPARATE REPOSITORY !!!!

declare(strict_types=1);

function getProductsFromJSON(string $filePath): array {
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

class Table {
    static function calculateColumnWidths(array $columns): array
    {
        $widths = [];
        foreach ($columns as $column) {
            $titleWidth = strlen($column->title);
            $maxWidth = array_reduce($column->content, function ($carry, $item) {return max(strlen($item), $carry);}, 0);
            $widths[] = max($maxWidth, $titleWidth);
        }
        return $widths;
    }

    static function displayRow(array $elements, array $widths, string $padString, $separator): void {
        foreach ($elements as $index => $element) {
            $maxLength = $widths[$index];
            $wordLength = strlen($element);
            if ($wordLength != $maxLength) {
                $padLeft = str_repeat($padString, (int) floor(($maxLength - $wordLength) / 2 + 1));
                $padRight = str_repeat($padString, (int) ceil(($maxLength - $wordLength) / 2 + 1));
                echo "{$padLeft}$element{$padRight}$separator";
                continue;
            }
            echo "{$padString}$element{$padString}$separator";
        }
    }

    static function display(array $columns): void {
        $padString = " ";
        $separator = "|";

        $titles = [];
        foreach ($columns as $column) {
            $titles[] = $column->title;
        }

        $widths = self::calculateColumnWidths($columns);
        $totalWidth = array_sum($widths) + count($columns) * strlen($padString) * 2 + count($columns);

        self::displayRow($titles, $widths, $padString, $separator);
        echo "\n";
        echo str_repeat("=", $totalWidth);
        echo "\n";

        $rowCount = count($columns[0]->content);
        for ($i = 0; $i < $rowCount; $i++) {
            $row = [];
            foreach ($columns as $column) {
                $row[] = $column->content[$i];
            }
            self::displayRow($row, $widths, $padString, $separator);
            echo "\n";
        }
    }

    static function createColumn($title, $content): stdClass {
        $column = new stdClass();
        $column->title = $title;
        $column->content = $content;
        return $column;
    }
}

function validateProduct(stdClass $product, int $index): void {
    if (!isset($product->name)) {
        throw new Exception("Product name is required for product #$index");
    }
    if (!isset($product->price)) {
        throw new Exception("Product '$product->name' has missing price");
    }
    if (!isset($product->quantity)) {
        throw new Exception("Product '$product->name' has missing quantity");
    }
    if (strlen($product->name) === 0) {
        throw new Exception("Product name cannot be empty string for product #$index");
    }
    if ($product->quantity <= 0) {
        throw new Exception("Product '$product->name' has invalid quantity");
    }
    if ($product->price <= 0) {
        throw new Exception("Product '$product->name' has invalid price");
    }
}

function sanitizeProduct(stdClass $product): void {
    if (isset($product->price)) {
        $product->price = (int) $product->price;
    }
    if (isset($product->quantity)) {
        $product->quantity = (int) $product->quantity;
    }
    if (isset($product->name)) {
        $product->name = (string) $product->name;
    }
}

function validateStoreProducts(array $products): void {
    foreach ($products as $index => $product) {
        validateProduct($product, $index);
    }
}

function sanitizeStoreProducts(array $products): void {
    foreach ($products as $product) {
        sanitizeProduct($product);
    }
}

function formatCurrency($amount): string {
    return '$' . number_format($amount / 100, 2);
}

function formatCurrencyForProducts(array $products): void {
    foreach ($products as $product) {
        $product->price = formatCurrency($product->price);
    }
}

function addProductIds(array $products): void {
    foreach ($products as $index => $product) {
        $product->id = (string) ($index + 1);
    }
}

function copyProducts(array $products): array {
    $copy = [];
    foreach ($products as $product) {
        $copy[] = clone $product;
    }
    return $copy;
}

function makeStoreTable($products): array {
    $productsView = copyProducts($products);
    formatCurrencyForProducts($productsView);
    addProductIds($productsView);
    return [
            Table::createColumn("ID", array_column($productsView, "id")),
            Table::createColumn("Name", array_column($productsView, "name")),
            Table::createColumn("Price", array_column($productsView, "price")),
            Table::createColumn("Quantity", array_map(function($v){return (string) $v;}, array_column($productsView, "quantity")))
           ];
}

function makeCartTable($cart): array {
    $cartView = copyProducts($cart);
    foreach ($cartView as $product) {
        $product->price = $product->price * $product->quantity;
    }
    formatCurrencyForProducts($cartView);
    addProductIds($cartView);
    return [
        Table::createColumn("ID", array_column($cartView, "id")),
        Table::createColumn("Name", array_column($cartView, "name")),
        Table::createColumn("Price", array_column($cartView, "price")),
        Table::createColumn("Quantity", array_map(function($v){return (string) $v;}, array_column($cartView, "quantity")))
    ];
}

// I can't test other methods on Windows, so I'm using the cheap method just because I know it works.
function clearScreen()
{
    for ($i = 0; $i < 50; $i++) {
        echo "\n";
    }
    echo "\r";
}

class Simulate {
    static function store(&$products, &$cart, &$state)
    {
        clearScreen();
        echo "STORE VIEW\n";
        Table::display(
            makeStoreTable($products)
        );

        if ($state === STATE::STORE_VIEW) {
            echo "1) Add item to cart\n";
            echo "2) View cart\n";
            echo "3) Finalize purchase\n";
            switch (getUserChoiceFromArray(["1", "2", "3"], "choice")) {
                case 1:
                    if (count($products) <= 0) {
                        echo "There are no more items left in the store!\n";
                        $state = STATE::STORE_VIEW;
                        break;
                    }
                    $state = STATE::STORE_TAKE;
                    break;
                case 2:
                    $state = STATE::CART_VIEW;
                    break;
                case 3:
                    $state = STATE::PURCHASE;
                    break;
            }
        }

        if ($state === STATE::STORE_TAKE) {
            $thingsInStore = [];
            foreach ($products as $index => $product) {
                if ($product->quantity > 0) {
                    $thingsInStore[] = (string) ($index + 1); // + 1 due to range starting at 1 instead of 0
                }
            }
            echo "Enter the ID of the product you wish to add to your cart ('n' to cancel)\n";
            $thingsInStore[] = "n";
            $userChoice = getUserChoiceFromArray($thingsInStore, "product");
            if ($userChoice !== "n") {
                $userChoice -= 1; // - 1 due to range starting at 1 instead of 0
                $productName = $products[$userChoice]->name;
                $availableQuantity = $products[$userChoice]->quantity;
                echo "Enter the quantity (1-$availableQuantity) of $productName you wish to add to your cart ('n' to cancel)\n";
                $quantity = getUserChoiceFromRange(1, $availableQuantity, "n", "quantity");
                if ($quantity !== "n") {
                    $products[$userChoice]->quantity -= $quantity;
                    addToCart($cart, $products[$userChoice], $quantity);
                    echo "$quantity $productName added to cart!\n";
                }
            }
            $state = STATE::STORE_VIEW;
        }
    }

    static function cart($products, &$cart, &$state)
    {
//        clearScreen();
        system("clear");
        echo "CART VIEW\n";
        Table::display(
            makeCartTable($cart)
        );

        $totalPrice = formatCurrency(calculateProductTotalPrice($cart));

        echo "The total sum is $totalPrice\n";

        echo "1) Remove item from cart\n";
        echo "2) View available store items\n";
        switch (getUserChoiceFromArray(["1", "2"], "choice")) {
            case 1:
                if (count($cart) <= 0) {
                    echo "There are no items in the cart.\n";
                    $state = STATE::CART_VIEW;
                    break;
                }
                $state = STATE::CART_TAKE;
                break;
            case 2:
                $state = STATE::STORE_VIEW;
                break;
        }

        if ($state === STATE::CART_TAKE) {
            $thingsInCart = [];
            foreach ($cart as $index => $product) {
                if ($product->quantity > 0) {
                    $thingsInCart[] = (string) ($index + 1); // + 1 due to range starting at 1 instead of 0
                }
            }
            echo "Enter the ID of the product you wish to remove from your cart ('n' to cancel)\n";
            $thingsInCart[] = "n";
            $userChoice = getUserChoiceFromArray($thingsInCart, "product");
            if ($userChoice !== "n") {
                $userChoice -= 1; // - 1 due to range starting at 1 instead of 0
                $productName = $cart[$userChoice]->name;
                $availableQuantity = $cart[$userChoice]->quantity;
                echo "Enter the quantity (1-$availableQuantity) of $productName you wish to remove from your cart ('n' to cancel)\n";
                $quantity = getUserChoiceFromRange(1, $availableQuantity, "n", "quantity");
                if ($quantity !== "n") {
                    removeFromCart($cart, $cart[$userChoice], $quantity);
                    $products[$userChoice]->quantity += $quantity;
                    echo "$quantity $productName removed from cart!\n";
                }
            }
            $state = STATE::CART_VIEW;
        }
    }

    static function purchase(array $cart, int &$state)
    {
        echo "CHECKOUT VIEW, YOUR CART\n";
        if (count($cart) === 0) {
            echo "You have no items in your cart!\n";
            $state = STATE::STORE_VIEW;
            return;
        }
        Table::display(
            makeCartTable($cart)
        );

        echo "1) Purchase!\n";
        echo "2) Cancel\n";
        switch (getUserChoiceFromArray(["1", "2"], "choice")) {
            case 1:
                echo "Thank you for your purchase!\n";
                exit();
            case 2:
                $state = STATE::STORE_VIEW;
                break;
        }

    }
}

function addToCart(&$cart, $product, $quantity) {
    foreach ($cart as $item) {
        if ($item->name === $product->name) {
            $cart[$product->quantity] += $quantity;
            return;
        }
    }
    $item = new stdClass();
    $index = array_push($cart, $item) - 1;
    $cart[$index]->name = $product->name;
    $cart[$index]->quantity = $quantity;
    $cart[$index]->price = $product->price;
}

function removeFromCart(&$cart, $product, $quantity) {
    foreach ($cart as $item) {
        if ($item->name === $product->name) {
            $item->quantity -= $quantity;
            if ($item->quantity <= 0) {
                unset($item);
                return;
            }
            return;
        }
    }
    throw new InvalidArgumentException("Product not found in cart");
}

function calculateProductTotalPrice($products)
{
    $totalPrice = 0;
    foreach ($products as $item) {
        $totalPrice += $item->price * $item->quantity;
    }
    return $totalPrice;
}

function getUserChoiceFromArray(array $choices, string $promptMessage = "input") {
    while (true) {
        $choice = readline(ucfirst("$promptMessage - "));
        if (!in_array($choice, $choices, true)) {
            echo "Invalid $promptMessage!\n";
            continue;
        }
        return $choice;
    }
}

function getUserChoiceFromRange(int $min, int $max, string $cancel = null, string $promptMessage = "input") {
    while (true) {
        $choice = readline(ucfirst("$promptMessage - "));
        if ($choice === $cancel) {
            return $choice;
        }
        if (!is_numeric($choice)) {
            echo "Invalid $promptMessage!\n";
            continue;
        }
        $choice = (int) $choice;
        if ($choice < $min || $choice > $max) {
            echo "Invalid $promptMessage!\n";
            continue;
        }
        return $choice;
    }
}

$products = getProductsFromJSON('products.json');
sanitizeStoreProducts($products);
validateStoreProducts($products);

$cart = [];

class STATE {
    const STORE_VIEW = 0;
    const CART_VIEW = 1;
    const STORE_TAKE = 2;
    const CART_TAKE = 3;
    const PURCHASE = 4;
}

$state = STATE::STORE_VIEW;

while (true) {
    switch ($state) {
        case STATE::STORE_VIEW:
            Simulate::store($products, $cart, $state);
            break;
        case STATE::CART_VIEW:
            Simulate::cart($products, $cart, $state);
            break;
        case STATE::PURCHASE:
            Simulate::purchase($cart, $state);
            break;
    }
}
