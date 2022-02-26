<?php

namespace Onetwothreecoinpays\Onetwothreecoinpays\Model;

use Onetwothreecoinpays\Onetwothreecoinpays\Helper\Data;
use Magento\Sales\Model\Order;
use Onetwothreecoinpays\Onetwothreecoinpays\Logger\Logger;
use Onetwothreecoinpays\Onetwothreecoinpays\Model\Methods\Coinpayments;


class AbstractIpn
{
    protected $_orderModel;

    protected $_helper;

    protected $_logger;

    protected $_transactionStatus;

    protected $_data;

    protected $_hmac;

    protected $_coinpaymentsModel;

    protected $_transactionBuilder;

    /**
     * @var Order
     */
    protected $_currentOrder;

    public function __construct(
        Order $orderModel,
        Data $helper,
        Logger $logger,
        Coinpayments $coinpaymentsModel,
        Order\Payment\Transaction\BuilderInterface $transactionBuilder
    )
    {
        $this->_orderModel = $orderModel;
        $this->_helper = $helper;
        $this->_logger = $logger;
        $this->_coinpaymentsModel = $coinpaymentsModel;
        $this->_transactionBuilder = $transactionBuilder;

    }

    protected function filterPaymentStatus($status)
    {
        switch ($status) {
            case -2:
                return Info::PAYMENT_STATUS_REFUND;
            case -1:
                return Info::PAYMENT_STATUS_CANCELLED;
            case 0:
                return Info::PAYMENT_STATUS_WAITING_FOR_FUNDS;
            case 1:
                return Info::PAYMENT_STATUS_COIN_CONFIRMED;
            case 2:
                return Info::PAYMENT_STATUS_QUEUE;
            case 3:
                return Info::PAYMENT_STATUS_HOLD;
            case 100:
                return Info::PAYMENT_STATUS_COMPLETE;
            default:
                return null;

        }
    }

    protected function filterIpnType()
    {
        switch ($this->_data->ipn_type) {
            case 'simple':
                return Info::IPN_TYPE_SIMPLE;
            case 'button':
                return Info::IPN_TYPE_BUTTON;
            case 'cart':
                return Info::IPN_TYPE_CART;
            case 'donation':
                return Info::IPN_TYPE_DONATION;
            case 'deposit':
                return Info::IPN_TYPE_DEPOSIT;
            case 'api':
                return Info::IPN_TYPE_API;
            default:
                return null;

        }
    }

    protected function checkHmac()
    {
        if (!$this->_hmac) {
            return false;
        }

        $serverHmac = hash_hmac(
            "sha512",
            http_build_query($this->_data),
            trim($this->_helper->getGeneralConfig('ipn_secret'))
        );
        if ($this->_hmac != $serverHmac) {
            return false;
        }
        return true;
    }

    function checkIpnValidate() {
        $error_msg = [];

        $request_id = null;
        if(!empty($this->_data->request_id)) {
            $request_id = $this->_data->request_id;
        }

        $sign = null;
        if(!empty($this->_data->sign)) {
            $sign = $this->_data->sign;
        }

        $merchant_id = trim($this->_helper->getGeneralConfig('merchant_id'));
        $merchant_key = trim($this->_helper->getGeneralConfig('ipn_secret'));

        if(empty($merchant_id) || empty($merchant_key)) {
            $error_msg = ['Please check merchant config.'];
        }

        if (
            !empty($sign)
            && !empty($request_id)
            && !empty($merchant_id)
            && !empty($merchant_key)
        ) {
            $pre = $merchant_id . $merchant_key . $request_id;
            $md5Signed = md5($pre);

            if($md5Signed == $sign) {
                return true;
            } else {
                $error_msg = ['signature does not match'];
            }

        }

        if(!empty($error_msg)) {
            return $error_msg;
        }
    }


    protected function getOrder()
    {
        $order = $this->_orderModel->loadByIncrementId($this->_data->request_id);
        if (!$order->getId()) {
            $order = $this->_orderModel->load($this->_data->request_id);
        }
        if (!$order->getId()) {
            return false;
        }
        $this->_currentOrder = $order;
        return $order;
    }

    /**
     * @param $status
     * @return $this
     */
    protected function updateOrderStatus()
    {
        if ($this->filterPaymentStatus($this->_data->status) == Info::PAYMENT_STATUS_COMPLETE) {
            $this->_currentOrder
                ->setState($this->_helper->getGeneralConfig('status_order_paid'))
                ->setStatus($this->_helper->getGeneralConfig('status_order_paid'));
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function updateOrderPayment()
    {
        if ($this->filterPaymentStatus($this->_data->status) == Info::PAYMENT_STATUS_COMPLETE) {
            $this->_currentOrder->setTotalPaid($this->_data->amount);
        }
        return $this;
    }

    protected function addTransactionToOrder()
    {
        if ($this->filterPaymentStatus($this->_data->status) != Info::PAYMENT_STATUS_COMPLETE) {
            return false;
        }
        try {
            $payment = $this->_currentOrder->getPayment();
            $payment->setMethod($this->_coinpaymentsModel->getCode());
            $payment->setLastTransId($this->_data->txn_id);
            $payment->setTransactionId($this->_data->txn_id);
            $payment->setAdditionalInformation([Order\Payment\Transaction::RAW_DETAILS => (array)$this->_data]);

            $formatedPrice = $this->_currentOrder->getBaseCurrency()->formatTxt($this->_currentOrder->getGrandTotal());

            /* @var Order\Payment\Transaction\BuilderInterface */
            $transaction = $this->_transactionBuilder
                ->setPayment($payment)
                ->setOrder($this->_currentOrder)
                ->setTransactionId($this->_data->txn_id)
                ->setAdditionalInformation([Order\Payment\Transaction::RAW_DETAILS => (array)$this->_data])
                ->setFailSafe(true)
                ->build(Order\Payment\Transaction::TYPE_CAPTURE);

            // Add transaction to payment
            $payment->addTransactionCommentsToOrder($transaction, __('The authorized amount is %1.', $formatedPrice));
            $payment->setParentTransactionId(null);

            // Save payment, transaction and order
            $payment->save();
            $this->_currentOrder->save();
            $transaction->save();
            $this->logAndDie("Create transaction. OrderId: " . $this->_currentOrder->getId() . "\n. Transaction Id: " . $transaction->getTransactionId());
            return  $transaction->getTransactionId();

        } catch (\Exception $e) {
            $this->logAndDie("Create transaction error. OrderId: " . $this->_currentOrder->getId() . "\n. Message: " . $e->getMessage());
        }

        return true;
    }

    /**
     * @return $this
     */
    protected function addToOrderHistory()
    {
        $str = '123coinpay.com Payment Status: <strong>' . $this->_data->status . '</strong> <br />';

        if ($this->_data->status == Info::PAYMENT_STATUS_COMPLETE) {
            $str .= 'Transaction ID: ' . $this->_data->txn_id
                . '<br />';
            $str .= 'Original Amount: ' . sprintf('%.08f', $this->_data->amount)
                . ' ' . $this->_data->coin . '<br />';

        }
        $this->_currentOrder->addStatusToHistory($this->_currentOrder->getStatus(), $str);

        return $this;
    }
    /**
     * @param int $status
     * @return null|string
     */
    protected function setTransactionStatus(int $status)
    {
        $this->_transactionStatus = $this->filterPaymentStatus($status);
        return $this->_transactionStatus;
    }

    /**
     * @return mixed
     */
    protected function getTransactionStatus()
    {
        return $this->_transactionStatus;
    }

    protected function setHmac($hmac)
    {
        $this->_hmac = $hmac;
        return $this->_hmac;
    }

    protected function getHmac()
    {
        return $this->_hmac;
    }

    protected function setRequestData($data)
    {
        $this->_data = $data;
        return $this->_data;
    }

    protected function getRequestData()
    {
        return $this->_data;
    }

    protected function logAndDie($msg)
    {
        if ($this->_helper->getGeneralConfig('debug')) {
            $message = "";
            if ($this->_currentOrder !== null) {
                $message = "Order ID: " . $this->_currentOrder->getId() . "\n";
            }
            $message .= $msg;
            $this->_logger->info($message);
        }
        return $this;
    }
}
