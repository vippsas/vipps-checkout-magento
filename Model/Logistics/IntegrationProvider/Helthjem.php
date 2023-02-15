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

use Magento\Shipping\Helper\Carrier;
use Vipps\Checkout\Api\Logistics\IntegrationProviderInterface;

class Helthjem implements IntegrationProviderInterface
{
    public const CARRIER_CODE = 'vipps_helthjem';
    /**
     * @var Carrier
     */
    private $config;

    /**
     * Porterbuddy constructor.
     *
     * @param Carrier $config
     */
    public function __construct(Carrier $config)
    {
        $this->config = $config;
    }

    public function get(): array
    {
        if (!$this->getValue('active')) {
            return [];
        }

        return [
            'helthjem' => [
                'username' => $this->getValue('username'),
                'password' => $this->getValue('password'),
                'shopId' => $this->getValue('shop_id'),
            ]
        ];
    }

    /**
     * @param $path
     *
     * @return string
     */
    public function getValue($path): string
    {
        return $this->config->getCarrierConfigValue(self::CARRIER_CODE, $path);
    }
}
