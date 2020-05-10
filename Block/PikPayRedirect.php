<?php

namespace Leftor\PikPay\Block;

use Magento\Framework\Locale\Resolver;
use Magento\Framework\View\Element\Template;


class PikPayRedirect extends Template
{
    protected $_checkoutSession;
    protected $_orderFactory;
    protected $_customerSession;
    protected $_quote;
    protected $_coreRegistry;

    protected $_model;

    /**
     * @var \Magento\Framework\Locale\Resolver
     */
    private $localeResolver;

    public function __construct(
        Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Leftor\PikPay\Model\PikPay $model,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Locale\Resolver $localeResolver,
        array $data = []
    )
    {
        $this->_checkoutSession = $checkoutSession;
        $this->_customerSession = $customerSession;
        $this->_orderFactory  = $orderFactory;
        $this->_model = $model;
        $this->_coreRegistry = $coreRegistry;
        $this->localeResolver = $localeResolver;
        parent::__construct($context, $data);
    }

    protected function getOrder()
    {
        return $this->_orderFactory->create()->loadByIncrementId(
            $this->_checkoutSession->getLastRealOrderId()
        );
    }

    protected function getQuote()
    {
        if (!$this->_quote)
        {
            $this->_quote = $this->getCheckoutSession()->getQuote();
        }
        return $this->_quote;
    }

    public function getPostParams()
    {
        return $this->getRequest()->getParams();
    }

    protected function getCheckoutSession()
    {
        return $this->_checkoutSession;
    }

    public function getModel()
    {
        return $this->_model;
    }

    public function getProcesor()
    {
        return $this->getModel()->getConfigData("procesor");
    }

    public function isTestMode()
    {
        if($this->getModel()->getConfigData("test_mode") == 1)
        {
            return true;
        }
        else
            return false;
    }

    public function getParam()
    {
        return $this->_coreRegistry->registry('param');
    }

    public function getPaymentType()
    {
        return $this->getModel()->getPaymentType();
    }

    public function getDirectPaymentUrl()
    {
        return $this->_urlBuilder->getUrl('pikpay/standard/redirect');
    }

    public function checkCardUrl()
    {
        return $this->_urlBuilder->getUrl('pikpay/standard/check');
    }

    public function canPayInInstallments()
    {
        $installmentsMinimum = $this->getModel()->getInstallmentsMinimum();
        $haveInstallments = $this->getModel()->canPayInInstallments();
        $orderTotal = $this->getOrder()->getGrandTotal();

        if($haveInstallments && $orderTotal >= $installmentsMinimum)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function getInstallmentsNumber()
    {
        $limit = $this->getModel()->numberOfInstallments();
        return range(2, $limit);
    }

    public function getCcBins()
    {
        return $this->getModel()->getCcBins();
    }

    public function getMediaUrl() {
        return $this->_urlBuilder->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]);
    }


    public function getRateTranslation($rates)
    {
        $isEnglish = $this->localeResolver->getLocale() === Resolver::DEFAULT_LOCALE;

        if ($isEnglish) {
            return $rates > 1 ? __('%1 rate', $rates) : __('%1 rates', $rates);
        }

        // 1 rata, 2 rate, 5 rata...
        return $rates == 1 || $rates >= 5 ? __('%1 rate', $rates) : __('%1 rates', $rates);
    }
}
