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
namespace Vipps\Checkout\Controller\Vipps;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Session\SessionManagerInterface as CheckoutSession;
use Vipps\Checkout\Model\SessionManager;

class Session implements ActionInterface
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var SessionManager
     */
    private $sessionManager;
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * Session constructor.
     *
     * @param CheckoutSession $checkoutSession
     * @param SessionManager $sessionManager
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        SessionManager $sessionManager,
        ResultFactory $resultFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->sessionManager = $sessionManager;
        $this->resultFactory = $resultFactory;
    }

    public function execute()
    {
        $token = false;

        $quote = $this->checkoutSession->getQuote();
        if ($quote) {
            $token = $this->sessionManager->getSessionToken($quote);
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('checkout/vipps', ['_query' => ['token' => $token]]);

        return $resultRedirect;
    }
}
