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

require_once ("Table.php");
require_once ("json.php");
require_once ("userInput.php");

function formatCurrency($amount): string
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

function makeStoreTable($products): array
{
    $productsView = copyProducts($products);
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

function makeCartTable($cart): array
{
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
        Table::createColumn("Quantity", array_map(function ($v) {
            return (string)$v;
        }, array_column($cartView, "quantity")))
    ];
}

function clearScreen()
{
    return;
    // I can't test other methods on Windows, so I'm using the hacky method just because I know it works.
    for ($i = 0; $i < 50; $i++) {
        echo "\n";
    }
    echo "\r";
}

class Simulate
{
    static function store(&$products, &$cart, &$state)
    {
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
                    $thingsInStore[] = (string)($index + 1); // + 1 due to range starting at 1 instead of 0
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
                    removeFromContainer($products, $products[$userChoice], $quantity);
                    addToContainer($cart, $products[$userChoice], $quantity);
                    echo "$quantity $productName added to cart!\n";
                }
            }
            $state = STATE::STORE_VIEW;
        }
    }

    static function cart($products, &$cart, &$state)
    {
        $emptyCart = count($cart) <= 0;

        echo "CART VIEW\n";
        if ($emptyCart) {
            echo "Your cart is empty!\n";
        } else {
            Table::display(
                makeCartTable($cart)
            );
            $totalPrice = formatCurrency(calculateProductTotalPrice($cart));

            echo "The total sum is $totalPrice\n";
        }


        if ($emptyCart) {
            echo "1) Back to store view\n";
            if (getUserChoiceFromArray(["1"], "choice") == 1) {
                $state = STATE::STORE_VIEW;
                return;
            }
        }

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
                    $thingsInCart[] = (string)($index + 1); // + 1 due to range starting at 1 instead of 0
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
                    removeFromContainer($cart, $cart[$userChoice], $quantity);
                    addtoContainer($products, $cart[$userChoice], $quantity);
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
            echo "1) Back to store view\n";
            if (getUserChoiceFromArray(["1"], "choice") == 1) {
                $state = STATE::STORE_VIEW;
                return;
            }
        }

        Table::display(
            makeCartTable($cart)
        );

        $totalPrice = formatCurrency(calculateProductTotalPrice($cart));
        echo "The total sum is $totalPrice\n";

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

function addToContainer(&$container, $product, $quantity)
{
    foreach ($container as $item) {
        if ($item->name === $product->name) {
            $item->quantity += $quantity;
            return;
        }
    }
    $item = new stdClass();
    $index = array_push($container, $item) - 1;
    $container[$index]->name = $product->name;
    $container[$index]->quantity = $quantity;
    $container[$index]->price = $product->price;
}

function removeFromContainer(&$container, $product, $quantity)
{
    foreach ($container as $item) {
        if ($item->name === $product->name) {
            $item->quantity -= $quantity;
            if ($item->quantity <= 0) {
                unset($item);
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

$products = getProductsFromJSON('products.json');
validateProductsFromJSON($products);

$cart = [];

class STATE
{
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
