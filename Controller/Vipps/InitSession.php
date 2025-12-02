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

use Magento\Framework\App\Http\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Framework\Session\SessionManagerInterface as CheckoutSession;
use Vipps\Checkout\Model\SessionManager;
use Magento\Framework\App\RequestInterface;


class InitSession implements ActionInterface
{
    public function __construct(
        private CheckoutSession $checkoutSession,
        private SessionManager $sessionManager,
        private ResultFactory $resultFactory,
        private RequestInterface $request,
        private Context $context,
        private MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        private CartRepositoryInterface $cartRepository
    ) {
    }

    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $data = $this->request->getPost();

        $cartId = $this->context->getValue('customer_logged_in')
            ? $data['cartId']
            : $this->maskedQuoteIdToQuoteId->execute($data['cartId']);


        try {
            $quote = $this->cartRepository->get($cartId);
            if (!$quote || !$quote->getId()) {
                throw new LocalizedException(__('No active quote found.'));
            }

            $token = $this->sessionManager->getSessionToken($quote);

            return $resultJson->setData([
                'success' => true,
                'token' => $token
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
