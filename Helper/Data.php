<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Onetwothreecoinpays\Onetwothreecoinpays\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_CONFIG_COINPAYMENTS = 'payment/coin_payments/';

    /**
     * Data constructor.
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    /**
     * @param      $field
     * @param null $storeId
     *
     * @return mixed
     */
    private function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param      $code
     * @param null $storeId
     *
     * @return mixed
     */
    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_CONFIG_COINPAYMENTS . $code, $storeId);
    }

    public function getInvoiceTemplate()
    {
        if ($this->getConfigValue('payment/coin_payments/is_direct')) {
            $template =  'Onetwothreecoinpays_Onetwothreecoinpays::coinpayments/iframe.phtml';
        } else {
            $template = 'Onetwothreecoinpays_Onetwothreecoinpays::coinpayments/status.phtml';
        }

        return $template;
    }
}
