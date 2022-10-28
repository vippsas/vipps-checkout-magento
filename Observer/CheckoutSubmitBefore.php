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
declare(strict_types=1);

namespace Vipps\Checkout\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Psr\Log\LoggerInterface;
use Vipps\Checkout\Model\Method\Vipps;
use Vipps\Checkout\Model\QuoteRepository;

/**
 * Class CheckoutSubmitBefore
 * @package Vipps\Checkout\Observer
 */
class CheckoutSubmitBefore implements ObserverInterface
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CheckoutSubmitBefore constructor.
     *
     * @param CartRepositoryInterface $cartRepository
     * @param QuoteRepository $quoteRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        QuoteRepository $quoteRepository,
        LoggerInterface $logger
    ) {
        $this->cartRepository = $cartRepository;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
    }

    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        /** @var CartInterface $quote */
        $quote = $observer->getData('quote');
        if (!$quote || !($quote instanceof CartInterface)) {
            return;
        }

        $payment  = $quote->getPayment();
        if (!$payment || !($payment instanceof PaymentInterface)) {
            return;
        }

        if ($payment->getMethod() === Vipps::METHOD_CODE) {
            try {
                $vippsQuote = $this->quoteRepository->loadNewByQuote($quote->getId());

                if ($quote->getReservedOrderId() !== $vippsQuote->getReservedOrderId()) {
                    $quote->setReservedOrderId($vippsQuote->getReservedOrderId());
                    $this->cartRepository->save($quote);
                }
            } catch (\Throwable $t) {
                $this->logger->error($t->getMessage());
            }
        }
    }
}
