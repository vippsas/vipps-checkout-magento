<?php

declare(strict_types=1);

namespace Vipps\Checkout\Gateway\Request\InitSession\ConfigurationDataBuilder;

use Vipps\Checkout\Api\Data\ExternalPaymentMethodInterface;
use Vipps\Checkout\Api\ExternalPaymentMethodProviderInterface;
use Vipps\Checkout\Gateway\Config\Config;

class ExternalPaymentMethodsProvider implements ExternalPaymentMethodProviderInterface
{
    /**
     * @var ExternalPaymentMethodInterface[]
     */
    private array $pool;
    private Config $config;

    public function __construct(
        array  $pool,
        Config $config
    ) {
        $this->pool = $pool;
        $this->config = $config;
    }

    public function get(): array
    {
        $enabledExternalMethods = $this->config->getValue('external_payment_methods') ?? '';
        $enabledExternalMethods = explode(',', $enabledExternalMethods);

        $data = [];

        foreach ($enabledExternalMethods as $enabledExternalMethod) {
            if (isset($this->pool[$enabledExternalMethod])) {
                $externalMethod = $this->pool[$enabledExternalMethod];
                if (!is_subclass_of($externalMethod, ExternalPaymentMethodInterface::class)) {
                    throw new \Exception("Method provider is not an instance of \Vipps\Checkout\Api\Data\ExternalPaymentMethodInterface");
                }

                $data[] = [
                    "paymentMethod" => $externalMethod->getName(), // Required
                    "redirectUrl"   => $externalMethod->getUrl()
                ];
            }
        }

        return $data;
    }
}
