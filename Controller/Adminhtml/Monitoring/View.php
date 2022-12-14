<?php
/**
 * Copyright 2022 Vipps
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED
 * TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL
 * THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

namespace Vipps\Checkout\Controller\Adminhtml\Monitoring;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Vipps\Checkout\Model\QuoteRepository as VippsQuoteRepository;

/**
 * Class View
 */
class View extends Action
{
    /**
     * @var PageFactory
     */
    private $resultPageFactory;
    /**
     * @var VippsQuoteRepository
     */
    private $quoteRepository;
    /**
     * @var Registry
     */
    private $registry;

    /**
     * View constructor.
     *
     * @param VippsQuoteRepository $quoteRepository
     * @param Registry $registry
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        VippsQuoteRepository $quoteRepository,
        Registry $registry,
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->quoteRepository = $quoteRepository;
        $this->registry = $registry;
    }

    /**
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        try {
            $vippsQuote = $this->quoteRepository->load($this->getRequest()->getParam('entity_id'));
            $this->registry->register('vipps_quote', $vippsQuote);
        } catch (\Throwable $e) {
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $this->messageManager->addErrorMessage($e->getMessage());
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('*/*');

            return $resultRedirect;
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Vipps_Checkout::vipps_monitoring');
        return $resultPage;
    }
}
