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
use Magento\Framework\View\Result\Page;
use Vipps\Checkout\Model\Quote\Command\ManualCancelFactory;
use Vipps\Checkout\Model\QuoteRepository;

/**
 * Class Cancel
 * @package Vipps\Checkout\Controller\Adminhtml\Monitoring
 */
class Cancel extends Action
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;
    /**
     * @var ManualCancelFactory
     */
    private $manualCancelFactory;

    /**
     * Restart constructor.
     *
     * @param Context $context
     * @param QuoteRepository $quoteRepository
     * @param ManualCancelFactory $manualCancelFactory
     */
    public function __construct(
        Context $context,
        QuoteRepository $quoteRepository,
        ManualCancelFactory $manualCancelFactory
    ) {
        parent::__construct($context);
        $this->quoteRepository = $quoteRepository;
        $this->manualCancelFactory = $manualCancelFactory;
    }

    /**
     * @return ResponseInterface|ResultInterface|Page
     */
    public function execute()
    {
        try {
            $this
                ->getManualCancelCommand()
                ->execute();
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $this
            ->resultRedirectFactory
            ->create()
            ->setUrl($this->_redirect->getRefererUrl());
    }

    /**
     * @return \Vipps\Checkout\Model\Quote\Command\ManualCancel
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getManualCancelCommand()
    {
        $vippsQuote = $this
            ->quoteRepository
            ->load($this->getRequest()->getParam('entity_id'));

        return $this->manualCancelFactory->create($vippsQuote);
    }
}
