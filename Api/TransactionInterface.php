<?php
namespace Onetwothreecoinpays\Onetwothreecoinpays\Api;

interface TransactionInterface
{
    /**
     * @param string $cartId
     * @param string $currency
     * @param float $value
     * @return boolean
     */
    public function createTransaction($cartId, $currency, $value);
}