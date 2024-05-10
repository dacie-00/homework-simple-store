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

function sortProducts(array &$products): void {
    usort($products, function ($a, $b) {return $a->name < $b->name ? -1 : 1;});
}

class Simulate
{
    static function store(&$store, &$cart, &$state): void
    {

        echo "STORE VIEW\n";
        Table::display(
            makeContainerTable($store)
        );

        if ($state === STATE::STORE_VIEW) {
            echo "1) Add item to cart\n";
            echo "2) View cart\n";
            echo "3) Finalize purchase\n";
            switch (getUserChoiceFromArray(["1", "2", "3"], "choice")) {
                case 1:
                    if (count($store) <= 0) {
                        echo "There are no more items left in the store!\n";
                        $state = STATE::STORE_VIEW;
                        return;
                    }
                    $state = STATE::STORE_TAKE;
                    return;
                case 2:
                    $state = STATE::CART_VIEW;
                    return;
                case 3:
                    $state = STATE::PURCHASE;
                    return;
            }
        }

        if ($state === STATE::STORE_TAKE) {
            $thingsInStore = [];
            foreach ($store as $index => $product) {
                if ($product->quantity > 0) {
                    $thingsInStore[] = (string)($index + 1); // + 1 due to range starting at 1 instead of 0
                }
            }
            echo "Enter the ID of the product you wish to add to your cart ('n' to cancel)\n";
            $thingsInStore[] = "n";
            $userChoice = getUserChoiceFromArray($thingsInStore, "product");
            if ($userChoice !== "n") {
                $userChoice -= 1; // - 1 due to range starting at 1 instead of 0
                $productName = $store[$userChoice]->name;
                $availableQuantity = $store[$userChoice]->quantity;
                echo "Enter the quantity (1-$availableQuantity) of $productName you wish to add to your cart ('n' to cancel)\n";
                $quantity = getUserChoiceFromRange(1, $availableQuantity, "n", "quantity");
                if ($quantity !== "n") {
                    addToContainer($cart, $store[$userChoice], $quantity);
                    removeFromContainer($store, $store[$userChoice], $quantity);
                    echo "$quantity $productName added to cart!\n";
                }
            }
            $state = STATE::STORE_VIEW;
        }
    }

    static function cart(array &$store, array &$cart, int &$state): void
    {
        $isCartEmpty = count($cart) <= 0;

        echo "CART VIEW\n";
        if ($isCartEmpty) {
            echo "Your cart is empty!\n";
        } else {
            Table::display(
                makeContainerTable($cart, true)
            );
            $totalPrice = formatCurrency(calculateProductTotalPrice($cart));

            echo "The total sum is $totalPrice\n";
        }


        if ($isCartEmpty) {
            echo "1) Back to store view\n";
            if (getUserChoiceFromArray(["1"], "choice") == 1) {
                $state = STATE::STORE_VIEW;
                return;
            }
        }

        echo "1) Remove item from cart\n";
        echo "2) View available store items\n";
        echo "3) Finalize purchase\n";
        switch (getUserChoiceFromArray(["1", "2", "3"], "choice")) {
            case 1:
                if (count($cart) <= 0) {
                    echo "There are no items in the cart.\n";
                    $state = STATE::CART_VIEW;
                    return;
                }
                $state = STATE::CART_TAKE;
                return;
            case 2:
                $state = STATE::STORE_VIEW;
                return;
            case 3:
                $state = STATE::PURCHASE;
                return;
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
                    addtoContainer($store, $cart[$userChoice], $quantity);
                    removeFromContainer($cart, $cart[$userChoice], $quantity);
                    sortProducts($store);
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
            makeContainerTable($cart, true)
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

$storeProducts = getProductsFromJSON('products.json');
validateProductsFromJSON($storeProducts);
sortProducts($storeProducts);

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
            Simulate::store($storeProducts, $cart, $state);
            break;
        case STATE::CART_VIEW:
            Simulate::cart($storeProducts, $cart, $state);
            break;
        case STATE::PURCHASE:
            Simulate::purchase($cart, $state);
            break;
    }
}
