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
namespace Vipps\Checkout\Gateway\Request\InitSession;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Directory\Model\AllowedCountries;
use Vipps\Checkout\Api\ExternalPaymentMethodProviderInterface;

/**
 * Class ConfigurationDataBuilder
 * @package Vipps\Checkout\Gateway\Request\InitSession
 */
class ConfigurationDataBuilder implements BuilderInterface
{
    /**
     * @var AllowedCountries
     */
    private $allowedCountries;
    private ExternalPaymentMethodProviderInterface $externalPaymentMethods;

    /**
     * ConfigurationDataBuilder constructor.
     *
     * @param AllowedCountries $allowedCountries
     */
    public function __construct(
        AllowedCountries $allowedCountries,
        ExternalPaymentMethodProviderInterface $externalPaymentMethods,
    ) {
        $this->allowedCountries = $allowedCountries;
        $this->externalPaymentMethods = $externalPaymentMethods;
    }

    /**
     * Get related data for transaction section.
     *
     * @param array $buildSubject
     *
     * @return array
     * @throws \Exception
     */
    public function build(array $buildSubject)
    {
        $data = [];

        $data['customerInteraction'] = 'CUSTOMER_NOT_PRESENT';
        $data['elements'] = 'Full';
        $data['countries']['supported'] = $this->allowedCountries->getAllowedCountries();

        $data['configuration']['externalPaymentMethods'] = $this->externalPaymentMethods->get();

        return $data;
    }
}
