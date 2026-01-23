<?php

if (!function_exists('calculateTax')) {
    function calculateTax($price, $taxRate, $isInclusive = true)
    {
        $taxRate = $taxRate / 100; // Convert percentage to decimal

        if ($isInclusive) {
            // Calculate price exclusive of tax
            $exclusivePrice = $price / (1 + $taxRate);
            $taxAmount = $price - $exclusivePrice;
            $inclusivePrice = $price;
        } else {
            // Calculate price inclusive of tax
            $exclusivePrice = $price;
            $taxAmount = $price * $taxRate;
            $inclusivePrice = $price + $taxAmount;
        }

        return [
            'exclusive_price' => round($exclusivePrice, 2),
            'inclusive_price' => round($inclusivePrice, 2),
            'tax_amount' => round($taxAmount, 2)
        ];
    }
}

if (!function_exists('calculatePrice')) {
    function calculatePrice($price, $taxRate, $needInclusive = true)
    {
        $taxArray = calculateTax($price, $taxRate, $needInclusive);

        return $needInclusive ? $taxArray['exclusive_price'] : $taxArray['inclusive_price'];
    }
}
