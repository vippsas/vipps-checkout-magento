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
use Magento\Framework\UrlInterface;
use Vipps\Checkout\Gateway\Request\SubjectReader;
use Vipps\Checkout\Api\Logistics\IntegrationsProviderInterface;

/**
 * Class LogisticsDataBuilder
 * @package Vipps\Checkout\Gateway\Request\InitSession
 */
class LogisticsDataBuilder implements BuilderInterface
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;
    /**
     * @var SubjectReader
     */
    private $subjectReader;
    /**
     * @var IntegrationsProviderInterface
     */
    private $integrationsProvider;

    /**
     * LogisticsDataBuilder constructor.
     *
     * @param UrlInterface $urlBuilder
     * @param SubjectReader $subjectReader
     * @param IntegrationsProviderInterface $integrationsProvider
     */
    public function __construct(
        UrlInterface $urlBuilder,
        SubjectReader $subjectReader,
        IntegrationsProviderInterface $integrationsProvider
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->subjectReader = $subjectReader;
        $this->integrationsProvider = $integrationsProvider;
    }

    /**
     * Get merchant related data for session request.
     *
     * Possible values for fixed options brand field:
     *
     * POSTEN
     * POSTNORD
     * PORTERBUDDY
     * INSTABOX
     * HELTHJEM
     * OTHER
     *
     * @param array $buildSubject
     *
     * @return array
     * @throws \Exception
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $reference = $paymentDO->getOrder()->getOrderIncrementId();

        $result = [
            'logistics' => [
                'dynamicOptionsCallback' => $this->urlBuilder->getUrl('checkout/vipps/logistics', ['reference' => $reference]),
                'fixedOptions' => [
                    [
                        'brand' => 'OTHER',
                        'amount' => [
                            'currency' => 'NOK',
                            'value' => 0
                        ],
                        'id' => 'freeshipping_freeshipping',
                        'priority' => 0,
                        'isDefault' => true,
                        'title' => 'Free Shipping',
                        'description' => 'Free Shipping'
                    ]
                ]
            ]
        ];

        $integrations = $this->integrationsProvider->get();
        if ($integrations) {
            $result['logistics']['integrations'] = $integrations;
        }

        return $result;
    }
}
