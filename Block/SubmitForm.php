<?php
/**
 * LiqPay Extension for Magento 2
 *
 * @author     Volodymyr Konstanchuk http://konstanchuk.com
 * @copyright  Copyright (c) 2017 The authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace LiqpayMagento\LiqPay\Block;

use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;
use LiqpayMagento\LiqPay\Sdk\LiqPay;
use LiqpayMagento\LiqPay\Helper\Data as Helper;

class SubmitForm extends Template
{
    protected $_order;

    /* @var $_liqPay LiqPay */
    protected $_liqPay;

    /* @var $_helper Helper */
    protected $_helper;

    public function __construct(
        Template\Context $context,
        LiqPay $liqPay,
        Helper $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_liqPay = $liqPay;
        $this->_helper = $helper;
    }

    /**
     * @return Order
     * @throws \Exception
     */
    public function getOrder()
    {
        if ($this->_order === null) {
            throw new \Exception('Order is not set');
        }

        return $this->_order;
    }

    /**
     * @param Order $order
     */
    public function setOrder(Order $order)
    {
        $this->_order = $order;
    }

    /**
     * @return bool|false|string
     */
    protected function _loadCache()
    {
        return false;
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function _toHtml()
    {
        $order = $this->getOrder();
        $html = $this->_liqPay->cnb_form([
            'action'      => 'pay',
            'amount'      => $order->getGrandTotal(),
            'currency'    => $order->getOrderCurrencyCode(),
            'description' => $this->_helper->getLiqPayDescription($order),
            'order_id'    => $order->getIncrementId(),
        ]);

        return $html;
    }
}
