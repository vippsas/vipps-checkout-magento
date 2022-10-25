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

use Magento\Framework\Session\SessionManagerInterface as CheckoutSession;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Response\RedirectInterface;

/**
 * Class LoadVippsCheckout
 * @package Vipps\Checkout\Observer
 */
class LoadVippsCheckout implements ObserverInterface
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var ConfigInterface
     */
    protected $config;

    private $redirect;

    /**
     * LoadVippsCheckout constructor.
     *
     * @param CheckoutSession $checkoutSession
     * @param ConfigInterface $config
     * @param RedirectInterface $redirect
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        ConfigInterface $config,
        RedirectInterface $redirect
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->config = $config;
        $this->redirect = $redirect;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->config->getValue('checkout_active')) {
            $this->redirect->redirect($observer->getControllerAction()->getResponse(), 'checkout/vipps');
        }
    }
}
