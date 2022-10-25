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

namespace Vipps\Checkout\Plugin\Quote\Model;

use Magento\Quote\Api\CartRepositoryInterface as Subject;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Vipps\Checkout\Api\CheckoutCommandManagerInterface;
use Vipps\Checkout\Api\QuoteRepositoryInterface;
use Vipps\Checkout\Model\Method\Vipps;

class CartRepositoryPlugin
{
    private CheckoutCommandManagerInterface $checkoutCommandManager;
    private QuoteRepositoryInterface $vippsQuoteRepository;

    public function __construct(
        CheckoutCommandManagerInterface $checkoutCommandManager,
        QuoteRepositoryInterface $vippsQuoteRepository
    ) {
        $this->checkoutCommandManager = $checkoutCommandManager;
        $this->vippsQuoteRepository = $vippsQuoteRepository;
    }

    /**
     * @param Subject $subject
     * @param \Closure $proceed
     * @param CartInterface|Quote $quote
     *
     * @return mixed
     */
    public function beforeSave(Subject $subject, CartInterface $quote)
    {
        if ($quote->getPayment()->getMethod() !== Vipps::METHOD_CODE) {
            return [$quote];
        }

        $origCouponCode = $quote->getOrigData('coupon_code');
        $couponCode = $quote->getData('coupon_code');

        $needAdjustAmount = ($origCouponCode !== $couponCode);
        if ($needAdjustAmount) {
            try {
                // clean token and session to start new one if discount applied
                $vippsQuote = $this->vippsQuoteRepository->loadNewByQuote($quote->getId());
                $vippsQuote->setCheckoutToken(null);
                $vippsQuote->setCheckoutSessionId(null);
                $this->vippsQuoteRepository->save($vippsQuote);
            } catch (\Exception $e) {
                // if vipps quote does not exists nothing to do
            }
        }

        return [$quote];
    }
}
