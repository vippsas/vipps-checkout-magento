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

namespace Vipps\Checkout\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Vipps\Checkout\Api\EnvironmentInterface;
use Magento\Checkout\Model\Session;

class CheckoutConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;
    /**
     * @var SessionManagerInterface|Session
     */
    private $checkoutSession;

    /**
     * CheckoutConfigProvider constructor.
     *
     * @param ConfigInterface $config
     * @param SessionManagerInterface $checkoutSession
     */
    public function __construct(
        ConfigInterface $config,
        SessionManagerInterface $checkoutSession
    ) {
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
    }

    public function getConfig()
    {
        $checkoutFrontendUrl = $this->config->getValue('environment') === EnvironmentInterface::ENVIRONMENT_PRODUCTION
            ? EnvironmentInterface::CHECKOUT_FRONTEND_URL_PRODUCTION
            : EnvironmentInterface::CHECKOUT_FRONTEND_URL_DEVELOP;

        return [
            'vippsCheckout' => [
                'checkoutFrontendUrl' => $checkoutFrontendUrl,
                'language' => 'no'
            ]
        ];
    }
}
