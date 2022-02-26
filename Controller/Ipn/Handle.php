<?php

namespace Onetwothreecoinpays\Onetwothreecoinpays\Controller\Ipn;

use Onetwothreecoinpays\Onetwothreecoinpays\Model\Ipn;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Handle extends Action implements CsrfAwareActionInterface
{

    protected $_orderModel;

    protected $_ipnModel;

    protected $_jsonResultFactory;

    public function __construct(
        Context $context,
        Order $orderModel,
        Ipn $ipnModel,
        JsonFactory $jsonResultFactory
    )
    {
        parent::__construct($context);
        $this->_orderModel = $orderModel;
        $this->_ipnModel = $ipnModel;
        $this->_jsonResultFactory = $jsonResultFactory;

    }

    public function execute()
    {
        // stdClass Object
        $requestData = (object)$this->getRequest()->getParams();

        $result = $this->_jsonResultFactory->create();

        if (!$requestData) {
            $result->setData([
                'error' => __('Invalid Data Sent')
            ]);
            return $result;
        }

        // echo 1122;
        // exit();

        $errors =  $this->_ipnModel->processIpnRequest($requestData);

        if (!empty($errors)) {
            $result->setData($errors);
            return $result;
        }

        return $result->setData(['error' => 'OK']);
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

}
