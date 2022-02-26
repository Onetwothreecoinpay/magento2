<?php

namespace Onetwothreecoinpays\Onetwothreecoinpays\Model;

interface IpnInterface
{
    /**
     * @param $data
     * @param $hmac
     * @return mixed
     */
    public function processIpnRequest($data);
}
