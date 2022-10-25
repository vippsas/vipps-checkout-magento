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

namespace Vipps\Checkout\Model\Quote;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Psr\Log\LoggerInterface;
use Vipps\Checkout\Api\CheckoutCommandManagerInterface;
use Vipps\Checkout\Api\Data\QuoteInterface;
use Vipps\Checkout\Api\Data\QuoteStatusInterface;
use Vipps\Checkout\Api\PaymentCommandManagerInterface;
use Vipps\Checkout\Api\Quote\CancelFacadeInterface;
use Vipps\Checkout\Model\OrderLocator;
use Vipps\Checkout\Model\Quote;
use Vipps\Checkout\Model\QuoteRepository;

/**
 * Quote Cancellation Facade.
 * It cancels the quote. Provides an ability to send cancellation request to Vipps.
 */
class CancelFacade implements CancelFacadeInterface
{
    /**
     * @var PaymentCommandManagerInterface
     */
    private $paymentCommandManager;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var AttemptManagement
     */
    private $attemptManagement;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderLocator
     */
    private $orderLocator;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CancelFacade constructor.
     *
     * @param PaymentCommandManagerInterface $paymentCommandManager
     * @param OrderManagementInterface $orderManagement
     * @param QuoteRepository $quoteRepository
     * @param AttemptManagement $attemptManagement
     * @param CartRepositoryInterface $cartRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderLocator $orderLocator
     * @param LoggerInterface $logger
     */
    public function __construct(
        PaymentCommandManagerInterface $paymentCommandManager,
        OrderManagementInterface $orderManagement,
        QuoteRepository $quoteRepository,
        AttemptManagement $attemptManagement,
        CartRepositoryInterface $cartRepository,
        OrderRepositoryInterface $orderRepository,
        OrderLocator $orderLocator,
        LoggerInterface $logger
    ) {
        $this->paymentCommandManager = $paymentCommandManager;
        $this->orderManagement = $orderManagement;
        $this->quoteRepository = $quoteRepository;
        $this->attemptManagement = $attemptManagement;
        $this->cartRepository = $cartRepository;
        $this->orderRepository = $orderRepository;
        $this->orderLocator = $orderLocator;
        $this->logger = $logger;
    }

    /**
     * @param QuoteInterface|Quote $vippsQuote
     *
     * @throws CouldNotSaveException
     */
    public function cancel(QuoteInterface $vippsQuote)
    {
        try {
            $order = $this->orderLocator->get($vippsQuote->getReservedOrderId());
            if ($order) {
                $this->orderManagement->cancel($order->getEntityId());
                $this->paymentCommandManager->cancel($order->getPayment());
            } else {
                /** @var \Magento\Quote\Model\Quote $quote */
                $quote = $this->cartRepository->get($vippsQuote->getQuoteId());
                $this->paymentCommandManager->cancel($quote->getPayment());
            }

            $vippsQuote->setStatus(QuoteStatusInterface::STATUS_CANCELED);
            $this->quoteRepository->save($vippsQuote);
        } catch (\Throwable $t) {
            $this->logger->critical($t);
            $vippsQuote->setStatus(QuoteStatusInterface::STATUS_CANCEL_FAILED);
            $this->quoteRepository->save($vippsQuote);

            $attempt = $this->attemptManagement->createAttempt($vippsQuote);
            $attempt->setMessage($t->getMessage());
            $this->attemptManagement->save($attempt);
        }
    }
}
