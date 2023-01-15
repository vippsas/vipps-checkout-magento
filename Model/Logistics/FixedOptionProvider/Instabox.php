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

namespace Vipps\Checkout\Model\Logistics\FixedOptionProvider;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Vipps\Checkout\Api\Logistics\FixedOptionProviderInterface;

class Instabox implements FixedOptionProviderInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * Porterbuddy constructor.
     *
     * @param ConfigInterface $config
     */
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
    }

    public function get(OrderAdapterInterface $order): array
    {
        if (!$this->config->getValue('checkout_instabox_active')) {
            return [];
        }

        return [
            [
                'brand' => 'INSTABOX',
                 'amount' => [
                    'value' => 0,
                    'currency' => $order->getCurrencyCode()
                ],
                'id' => 'instabox-4',
                'priority' => 0,
                'isDefault' => false,
                'title' => $this->config->getValue('checkout_instabox_method_name'),
                'description' => $this->config->getValue('checkout_instabox_title')
            ]
        ];
    }
}
