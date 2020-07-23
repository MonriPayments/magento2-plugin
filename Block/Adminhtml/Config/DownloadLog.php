<?php

namespace Monri\Payments\Block\Adminhtml\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Logger\Handler\Base;

class DownloadLog extends Field
{
    /**
     * @var Base
     */
    private $loggerHandler;

    /**
     * @var File
     */
    private $fileDriver;

    public function __construct(
        Context $context,
        Base $loggerHandler,
        File $fileDriver,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->loggerHandler = $loggerHandler;
        $this->fileDriver = $fileDriver;
    }

    public function render(AbstractElement $element)
    {
        $element->unsetData('scope')
            ->unsetData('can_use_website_value')
            ->unsetData('can_use_default_value');
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        /** @var Button $downloadButton */
        try {
            $downloadButton = $this->getLayout()->createBlock(Button::class);
        } catch (LocalizedException $e) {
            return '';
        }

        // Path to the log file on disk.
        $logFilePath = (string) $this->loggerHandler->getUrl();

        try {
            if ($this->fileDriver->isExists($logFilePath) !== false) {
                $downloadUrl = $this->_urlBuilder->getUrl('monripayments/log/download');
                $action = "window.open('$downloadUrl', '_blank')";
                $downloadButton
                    ->setData('label', __('Download Log'))
                    ->setData('on_click', $action);
            } else {
                $downloadButton
                    ->setData('label', __('Download Log (no file)'))
                    ->setData('disabled', true);
            }
        } catch (FileSystemException $e) {
            return '';
        }

        return $downloadButton->toHtml();
    }
}
