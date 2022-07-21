<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Block\Adminhtml\Config\Merchant;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Url;

class UrlInfo extends Field
{
    private const SUCCESS_ROUTE = 'monripayments/redirect/success';
    private const SUCCESS_CODE = 'success';

    private const CANCEL_ROUTE = 'monripayments/redirect/cancel';
    private const CANCEL_CODE = 'cancel';

    private const CALLBACK_ROUTE = 'monripayments/gateway/callback';
    private const CALLBACK_CODE = 'callback';

    /**
     * @var string[]
     */
    protected $_routes = [
        self::SUCCESS_CODE  => self::SUCCESS_ROUTE,
        self::CANCEL_CODE   => self::CANCEL_ROUTE,
        self::CALLBACK_CODE => self::CALLBACK_ROUTE,
    ];

    /**
     * @var Url
     */
    private $frontendUrlBuilder;

    /**
     * @var Http
     */
    private $request;

    /**
     * UrlInfo constructor.
     *
     * @param Context $context
     * @param Url $frontendUrlBuilder
     * @param Http $request
     * @param array $data
     */
    public function __construct(
        Context $context,
        Url $frontendUrlBuilder,
        Http $request,
        array $data = []
    ) {
        $this->frontendUrlBuilder = $frontendUrlBuilder;

        parent::__construct($context, $data);
        $this->request = $request;
    }

    /**
     * Get custom html
     *
     * @param AbstractElement $element
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $html = '';

        foreach ($this->generateUrls() as $urlCode => $url) {
            $text = $this->getUrlText($urlCode);

            $url = $this->escapeHtml($url);
            $html .= "{$text}: $url" . '<br/>';
        }

        return $html;
    }

    /**
     * Generate urls
     *
     * @return array
     */
    private function generateUrls()
    {
        $storeId = $this->_storeManager->getDefaultStoreView() ?
            $this->_storeManager->getDefaultStoreView()->getId() :
            0;

        $urls = [];

        foreach ($this->_routes as $routeName => $route) {
            $urls[$routeName] = $this->frontendUrlBuilder->getUrl($route, [
                '_nosid' => true,
                '_scope' => $storeId
            ]);
        }

        return $urls;
    }

    /**
     * Resolve url text
     *
     * @param string $code
     * @return \Magento\Framework\Phrase|string
     */
    private function getUrlText($code)
    {
        switch ($code) {
            case self::SUCCESS_CODE:
                return __('Success URL');

            case self::CANCEL_CODE:
                return __('Cancel URL');

            case self::CALLBACK_CODE:
                return __('Callback URL');
        }

        return '';
    }
}
