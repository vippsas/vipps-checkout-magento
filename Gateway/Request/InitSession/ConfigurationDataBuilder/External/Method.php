<?php

declare(strict_types=1);

namespace Vipps\Checkout\Gateway\Request\InitSession\ConfigurationDataBuilder\External;

use Magento\Framework\UrlInterface;
use Vipps\Checkout\Api\Data\ExternalPaymentMethodInterface;

class Method implements ExternalPaymentMethodInterface
{
    private UrlInterface $urlBuilder;
    private string $url;
    private string $name;

    public function __construct(
        string $url,
        string $name,
        UrlInterface $urlBuilder
    )
    {
        $this->urlBuilder = $urlBuilder;
        $this->url = $url;
        $this->name = $name;
    }

    public function getUrl(): string
    {
        return $this->urlBuilder->getUrl($this->url, ['_secure' => 1]);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
