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

require_once("table.php");
require_once("json.php");
require_once("userInput.php");
require_once("interact.php");
require_once("helpers.php");
require_once("state.php");

$storeProducts = getProductsFromJSON('products.json');
validateProductsFromJSON($storeProducts);
sortProducts($storeProducts);

$state = STATE::STORE_VIEW;
$cart = [];

while (true) {
    switch ($state) {
        case STATE::STORE_VIEW:
            storeView($storeProducts, $state);
            break;
        case STATE::STORE_TAKE:
            storeTake($storeProducts, $cart, $state);
            break;
        case STATE::CART_VIEW:
            cartView($cart, $state);
            break;
        case STATE::CART_TAKE:
            cartTake($storeProducts, $cart, $state);
            break;
        case STATE::PURCHASE:
            purchase($cart, $state);
            break;
    }
}
