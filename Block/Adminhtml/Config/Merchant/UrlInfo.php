<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * For the full copyright and license information, please view the NOTICE
 * and LICENSE files that were distributed with this source code.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Block\Adminhtml\Config\Merchant;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Url;

class UrlInfo extends Field
{
    const SUCCESS_ROUTE = 'monripayments/redirect/success';
    const SUCCESS_CODE = 'success';

    const CANCEL_ROUTE = 'monripayments/redirect/cancel';
    const CANCEL_CODE = 'cancel';

    const CALLBACK_ROUTE = 'monripayments/callback';
    const CALLBACK_CODE = 'callback';

    protected $_routes = [
        self::SUCCESS_CODE  => self::SUCCESS_ROUTE,
        self::CANCEL_CODE   => self::CANCEL_ROUTE,
        self::CALLBACK_CODE => self::CALLBACK_ROUTE,
    ];

    /**
     * @var Url
     */
    private $frontendUrlBuilder;

    public function __construct(
        Context $context,
        Url $frontendUrlBuilder,
        array $data = []
    ) {
        $this->frontendUrlBuilder = $frontendUrlBuilder;

        parent::__construct($context, $data);
    }

    /**
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

    private function generateUrls()
    {
        $urls = [];

        foreach ($this->_routes as $routeName => $route) {
            $urls[$routeName] = $this->frontendUrlBuilder->getUrl($route);
        }

        return $urls;
    }

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
