<?php
/**
 * LiqPay Extension for Magento 2
 *
 * @author     Volodymyr Konstanchuk http://konstanchuk.com
 * @copyright  Copyright (c) 2017 The authors
 * @license    http://www.opensource.org/licenses/mit-license.html  MIT License
 */

namespace LiqpayMagento\LiqPay\Model;

use LiqpayMagento\LiqPay\Sdk\LiqPay;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Framework\Validator\Exception;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;

class Payment extends AbstractMethod
{
    const METHOD_CODE = 'liqpaymagento_liqpay';

    protected $_code = self::METHOD_CODE;

    protected $_liqPay;

    protected $_canCapture              = true;
    protected $_canVoid                 = true;
    protected $_canUseForMultishipping  = false;
    protected $_canUseInternal          = false;
    protected $_isInitializeNeeded      = true;
    protected $_isGateway               = true;
    protected $_canAuthorize            = false;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canUseCheckout          = true;

    protected $_minOrderTotal = 0;
    protected $_supportedCurrencyCodes;

    /**
     * @var UrlInterface
     */
    protected $_urlBuilder;

    /**
     * Payment constructor.
     *
     * @param Context                    $context
     * @param Registry                   $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory      $customAttributeFactory
     * @param Data                       $paymentData
     * @param ScopeConfigInterface       $scopeConfig
     * @param Logger                     $logger
     * @param UrlInterface               $urlBuider
     * @param LiqPay                     $liqPay
     * @param array                      $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        UrlInterface $urlBuider,
        LiqPay $liqPay,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            null,
            null,
            $data
        );

        $this->_liqPay = $liqPay;
        $this->_supportedCurrencyCodes = $liqPay->getSupportedCurrencies();
        $this->_minOrderTotal = $this->getConfigData('min_order_total');
        $this->_urlBuilder = $urlBuider;
    }

    /**
     * @param string $currencyCode
     *
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        if (!in_array($currencyCode, $this->_supportedCurrencyCodes)) {
            return false;
        }

        return true;
    }

    /**
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return $this|AbstractMethod
     * @throws Exception
     */
    public function capture(InfoInterface $payment, $amount)
    {
        /** @var Order $order */
        $order = $payment->getOrder();
        try {
            $payment->setTransactionId('liqpay-' . $order->getId())->setIsTransactionClosed(0);

            return $this;
        } catch (\Exception $e) {
            $this->debugData(['exception' => $e->getMessage()]);
            throw new Exception(__('Payment capturing error.'));
        }
    }

    /**
     * @param CartInterface|null $quote
     *
     * @return bool
     */
    public function isAvailable(CartInterface $quote = null)
    {
        if (!$this->_liqPay->getHelper()->isEnabled()) {
            return false;
        }
        $this->_minOrderTotal = $this->getConfigData('min_order_total');
        if ($quote && $quote->getBaseGrandTotal() < $this->_minOrderTotal) {
            return false;
        }

        return parent::isAvailable($quote);
    }
}
