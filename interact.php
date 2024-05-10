<?php

function storeView(array $store, int &$state): void
{
    $isStoreEmpty = count($store) <= 0;

    echo "STORE VIEW\n";
    if ($isStoreEmpty) {
        echo "There are no more items left in the store!\n";
    } else {
        tableDisplay(
            makeContainerTable($store)
        );
    }

    if ($isStoreEmpty) {
        echo "1) View cart\n";
        echo "2) Finalize purchase\n";
        switch (getUserChoiceFromArray(["1", "2"], "choice")) {
            case 1:
                $state = STATE::CART_VIEW;
                return;
            case 2:
                $state = STATE::PURCHASE;
                return;
        }
    }

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

}

function storeTake(array &$store, array &$cart, int &$state): void
{
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

function cartView(array $cart, int &$state): void
{
    $isCartEmpty = count($cart) <= 0;

    echo "CART VIEW\n";
    if ($isCartEmpty) {
        echo "Your cart is empty!\n";
    } else {
        tableDisplay(
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
}

function cartTake(array &$store, array &$cart, int &$state): void
{
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

function purchase(array $cart, int &$state): void
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

    tableDisplay(
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
