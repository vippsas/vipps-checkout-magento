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

namespace Vipps\Checkout\Model\Logistics\IntegrationProvider;

use Magento\Payment\Gateway\ConfigInterface;
use Vipps\Checkout\Api\Logistics\IntegrationProviderInterface;

class Porterbuddy implements IntegrationProviderInterface
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

    public function get(): array
    {
        if (!$this->config->getValue('checkout_porterbuddy_active')) {
            return [];
        }

        return [
            'porterbuddy' => [
                'publicToken' => $this->config->getValue('checkout_porterbuddy_public_token'),
                'apiKey' => $this->config->getValue('checkout_porterbuddy_api_key'),
                'origin' => [
                    'name' => $this->config->getValue('checkout_porterbuddy_origin_name'),
                    'email' => $this->config->getValue('checkout_porterbuddy_origin_email'),
                    'phoneNumber' => $this->config->getValue('checkout_porterbuddy_origin_phone'),
                    'address' => [
                        'StreetAddress' => $this->config->getValue('checkout_porterbuddy_origin_address_street'),
                        'PostalCode' => $this->config->getValue('checkout_porterbuddy_origin_address_zip'),
                        'City' => $this->config->getValue('checkout_porterbuddy_origin_address_city'),
                        'Country' => $this->config->getValue('checkout_porterbuddy_origin_address_country'),
                    ]
                ]
            ]
        ];
    }
}
