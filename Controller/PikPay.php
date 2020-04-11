<?php

namespace Leftor\PikPay\Controller;

abstract class PikPay extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Quote\Model\Quote
     */
    protected $_quote;

    /**
     * @var \Leftor\PikPay\Model\PikPay
     */
    protected $_paymentMethod;

    /**
     * @var \Leftor\PikPay\Helper\PikPay
     */
    protected $_checkoutHelper;
    protected $_creditCardHelper;

    /**
     * @var \Magento\Quote\Api\CartManagementInterface
     */
    protected $cartManagement;

    protected $resultPageFactory;

    protected $_coreRegistry;

    protected $_transactionBuilder;

    protected $_orderPayment;


    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Leftor\PikPay\Model\PikPay $paymentMethod
     * @param \Leftor\PikPay\Helper\PikPay $checkoutHelper
     * @param \Leftor\PikPay\Helper\CreditCard $creditCardHelper
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagement
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Leftor\PikPay\Model\PikPay $paymentMethod,
        \Leftor\PikPay\Helper\PikPay $checkoutHelper,
        \Leftor\PikPay\Helper\CreditCard $creditCardHelper,
        \Magento\Quote\Api\CartManagementInterface $cartManagement,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $trans,
        \Magento\Sales\Model\Order\Payment $orderPayment
    ) {
        $this->_customerSession = $customerSession;
        $this->_checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->_orderFactory = $orderFactory;
        $this->_paymentMethod = $paymentMethod;
        $this->_checkoutHelper = $checkoutHelper;
        $this->_creditCardHelper = $creditCardHelper;
        $this->cartManagement = $cartManagement;
        $this->_logger = $logger;
        $this->resultPageFactory = $resultPageFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->_transactionBuilder = $trans;
        $this->_orderPayment = $orderPayment;
        parent::__construct($context);
    }

    /**
     * Instantiate quote and checkout
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function initCheckout()
    {
        $quote = $this->getQuote();
        if (!$quote->hasItems() || $quote->getHasError()) {
            $this->getResponse()->setStatusHeader(403, '1.1', 'Forbidden');
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t initialize checkout.'));
        }
    }

    /**
     * Cancel order, return quote to customer
     *
     * @param string $errorMsg
     * @return false|string
     */
    protected function _cancelPayment($errorMsg = '')
    {
        $gotoSection = false;
        $this->_checkoutHelper->cancelCurrentOrder($errorMsg);
        if ($this->_checkoutSession->restoreQuote()) {
            //Redirect to payment step
            $gotoSection = 'paymentMethod';
        }

        return $gotoSection;
    }

    /**
     * Get order object
     *
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrderById($order_id)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->get('Magento\Sales\Model\Order');
        $order_info = $order->loadByIncrementId($order_id);
        return $order_info;
    }

    /**
     * Get order object
     *
     * @return \Magento\Sales\Model\Order
     */
    protected function getOrder()
    {
        return $this->_orderFactory->create()->loadByIncrementId(
            $this->_checkoutSession->getLastRealOrderId()
        );
    }

    protected function getQuote()
    {
        if (!$this->_quote) {
            $this->_quote = $this->getCheckoutSession()->getQuote();
        }
        return $this->_quote;
    }

    protected function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    public function getCustomerSession()
    {
        return $this->_customerSession;
    }

    public function getPaymentMethod()
    {
        return $this->_paymentMethod;
    }

    protected function getCheckoutHelper()
    {
        return $this->_checkoutHelper;
    }

    public function getCcHelper()
    {
        return $this->_creditCardHelper;
    }

    public function getResponseDigest($orderNumber) {
        return sha1($this->getPaymentMethod()->getKey().$orderNumber);
    }

    public function getResponseDigestV2($query) {
        return hash('sha512',$this->getPaymentMethod()->getKey().$query);
    }

    public function getResponseCode($code) {
        $result = $this->getPaymentMethod()->responseCodes();
        return $result[$code];
    }

    public function updateOrder($id,$status,$comment) {
        
        if($status == 'success'){
            $setStatus = $this->getPaymentMethod()->getOrderStatusSuccess();
        }
        elseif ($status = 'fail'){
            $setStatus = $this->getPaymentMethod()->getOrderStatusFail();
        }
        
        $objectManager = $this->_objectManager->get('Magento\Sales\Model\Order');
        $order = $this->getOrderById((int)$id);
        $order->setStatus($setStatus);
        $order->addStatusHistoryComment($comment,$setStatus);
        
        if($objectManager->save($order)) {
            return true;
        } 
        else {
            return false;
        }
    }

    /**
     * @param $orderId
     * @param array $paymentData
     */
    public function makeInvoice($orderId, $paymentData = array()) {

        $trans = $this->_transactionBuilder;
        $order = $this->getOrderById($orderId);

        if($order->canInvoice()) {
            $invoice = $this->_objectManager->create('Magento\Sales\Model\Service\InvoiceService')->prepareInvoice($order);

            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_OFFLINE);
            $invoice->register();

            $transaction = $this->_objectManager->create('Magento\Framework\DB\Transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());
            $transaction->save();
            
            $paymentTransaction = $trans->setPayment($order->getPayment())
                ->setOrder($order)
                ->setTransactionId($orderId)
                ->setFailSafe(true)
                ->setAdditionalInformation([\Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => (array) $paymentData])
                ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

            $paymentTransaction->save();

            $order->addStatusHistoryComment('Invoice uspješno kreiran!')->save();
        }
    }

    public function secureVerification($acs_url,$pareq,$url,$authToken)
    {
        $secureVerification = '<!DOCTYPE html>
					<html>
					  <head>
					    <title>PikPay 3D Secure Verification</title>
					    <script language="Javascript">
					      function OnLoadEvent() { document.form.submit(); }
					    </script>
					  </head>
					  <body OnLoad="OnLoadEvent();">
					    Invoking 3-D secure form, please wait ...
					    <form name="form" action="' . $acs_url . '" method="post">
					      <input type="hidden" name="PaReq" value="' . $pareq . '">
					      <input type="hidden" name="TermUrl" value="' . $url . '">
					      <input type="hidden" name="MD" value="' . $authToken . '">
					      <noscript>
					        <p>Please click</p><input id="to-asc-button" type="submit">
					      </noscript>
					    </form>
					    </body>
					</html>'; // Output 3DS Verification
        return $secureVerification;
    }

    /**
     * @return float|int
     */
    public function getOrderPercentage()
    {
        return $this->_paymentMethod->getOrderPercentage();
    }

    /**
     * @return float|int
     */
    public function getOrderPercentageForInstallments()
    {
        return $this->_paymentMethod->getOrderPercentageForInstallments();
    }
}
