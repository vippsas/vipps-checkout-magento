<?php

declare(strict_types=1);

namespace Vipps\Checkout\Api\Data;

interface ExternalPaymentMethodInterface
{
    public function getName(): string;

    public function getUrl(): string;
}
