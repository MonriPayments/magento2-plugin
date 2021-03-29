<?php
/**
 * This file is part of the Monri Payments module
 *
 * (c) Monri Payments d.o.o.
 *
 * @author Favicode <contact@favicode.net>
 */

namespace Monri\Payments\Controller\Adminhtml\Log;

use Exception;
use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Logger\Handler\Base;

/**
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
class Download extends AbstractAction
{
    const ADMIN_RESOURCE = 'Magento_Backend::system';

    /**
     * @var File
     */
    private $fileClient;

    /**
     * @var Base
     */
    private $loggerHandler;

    /**
     * @var FileFactory
     */
    private $fileFactory;

    public function __construct(
        Action\Context $context,
        File $fileClient,
        FileFactory $fileFactory,
        Base $loggerHandler
    ) {
        parent::__construct($context);
        $this->fileClient = $fileClient;
        $this->loggerHandler = $loggerHandler;
        $this->fileFactory = $fileFactory;
    }

    /**
     * Download log for administrators
     *
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        $filePath = (string) $this->loggerHandler->getUrl();
        $fileName = $this->fileClient->getPathInfo($filePath)['basename'];

        try {
            if ($this->fileClient->fileExists($filePath) === false) {
                throw new NotFoundException(__('File not on disk.'));
            }

            return $this->fileFactory->create(
                $fileName,
                [
                    'type' => 'filename',
                    'value' => $filePath
                ]
            );
        } catch (Exception $e) {
            throw new NotFoundException(__($e->getMessage()));
        }
    }
}
