<?php
namespace Onetwothreecoinpays\Onetwothreecoinpays\Api;

interface CurrencyManagementInterface
{
    /**
     * @param string $cartId
     * @param string $currency
     * @param float $value
     * @return boolean
     */
    public function saveCurrency($cartId, $currency, $value);
}