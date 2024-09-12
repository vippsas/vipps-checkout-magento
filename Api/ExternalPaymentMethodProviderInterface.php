<?php

declare(strict_types=1);

namespace Vipps\Checkout\Api;

interface ExternalPaymentMethodProviderInterface
{
    public function get(): array;
}
