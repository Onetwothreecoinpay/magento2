<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Onetwothreecoinpays\Onetwothreecoinpays\Block\Form;

use Magento\Customer\Helper\Session\CurrentCustomer;

class Coinpayments extends \Magento\Payment\Block\Form
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_methodCode = 'coin_payments';

    /**
     * @var null
     */
    protected $_config;

    /**
     * @var CurrentCustomer
     */
    protected $currentCustomer;

    protected function _construct()
    {
        $template = 'Onetwothreecoinpays_Onetwothreecoinpays::coinpayments/form/coinpayments.phtml';
        $this->setTemplate($template);

        parent::__construct();
    }
}
