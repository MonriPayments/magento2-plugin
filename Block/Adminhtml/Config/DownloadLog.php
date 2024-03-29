<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

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

    /**
     * @var bool
     */
    private $components;

    /**
     * DownloadLog constructor.
     *
     * @param Context $context
     * @param Base $loggerHandler
     * @param File $fileDriver
     * @param array $data
     * @param bool $components
     */
    public function __construct(
        Context $context,
        Base $loggerHandler,
        File $fileDriver,
        array $data = [],
        bool $components = false
    ) {
        parent::__construct($context, $data);
        $this->loggerHandler = $loggerHandler;
        $this->fileDriver = $fileDriver;
        $this->components = $components;
    }

    /**
     * Render field
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element)
    {
        $element->unsetData('scope')
            ->unsetData('can_use_website_value')
            ->unsetData('can_use_default_value');
        return parent::render($element);
    }

    /**
     * Customize element html
     *
     * @param AbstractElement $element
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
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

                $downloadUrl = $this->_urlBuilder->getUrl('monripayments/log/download', [
                    '_query'=>['components'=>$this->components]]);

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
