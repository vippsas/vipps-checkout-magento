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
namespace Vipps\Checkout\Gateway\Http;

use Vipps\Checkout\Gateway\Http\Client\ClientInterface;
use Vipps\Checkout\Model\UrlResolver;

/**
 * Class TransferFactory
 * @package Vipps\Checkout\Gateway\Http
 */
class TransferFactory implements TransferFactoryInterface
{
    /**
     * @var string
     */
    private $endpointUrl;

    /**
     * @var string
     */
    private $method;

    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * @var UrlResolver
     */
    private $urlResolver;

    /**
     * @var array
     */
    private $urlParams = [];

    /**
     * @var array
     */
    private $allowedBodyKeys;

    /**
     * TransferFactory constructor.
     *
     * @param TransferBuilder $transferBuilder
     * @param UrlResolver $urlResolver
     * @param string $method
     * @param string $endpointUrl
     * @param array $urlParams
     * @param array $allowedBodyKeys
     */
    public function __construct(
        TransferBuilder $transferBuilder,
        UrlResolver $urlResolver,
        string $method,
        string $endpointUrl,
        array $urlParams = [],
        array $allowedBodyKeys = []
    ) {
        $this->transferBuilder = $transferBuilder;
        $this->urlResolver = $urlResolver;
        $this->method = $method;
        $this->endpointUrl = $endpointUrl;
        $this->urlParams = $urlParams;
        $this->allowedBodyKeys = $allowedBodyKeys;
    }

    /**
     * Builds gateway transfer object
     *
     * @param array $request
     *
     * @return TransferInterface
     * @throws \Exception
     */
    public function create(array $request)
    {
        $this->transferBuilder->setHeaders([
            ClientInterface::HEADER_PARAM_IDEMPOTENCY_KEY =>
                $request[ClientInterface::HEADER_PARAM_IDEMPOTENCY_KEY] ?? $this->generateRequestId()
        ]);

        $this->transferBuilder
            ->setBody($this->getBody($request))
            ->setMethod($this->method)
            ->setUri($this->getUrl($request))
            ->setUrlParameters($this->getUrlParameters());

        return $this->transferBuilder->build();
    }

    /**
     * Generating Url.
     *
     * @param $request
     *
     * @return string
     */
    private function getUrl(array $request = [])
    {
        $endpointUrl = $this->endpointUrl;
        /** Binding url parameters if they were specified */
        foreach ($this->urlParams as $paramValue) {
            if (isset($request[$paramValue])) {
                $endpointUrl = str_replace(':' . $paramValue, $request[$paramValue], $this->endpointUrl);
                $this->urlParams[$paramValue] = $request[$paramValue];
            }
        }
        return $this->urlResolver->getUrl($endpointUrl);
    }

    /**
     * Method to get needed content body from request.
     *
     * @param array $request
     *
     * @return array
     */
    private function getBody(array $request = [])
    {
        $body = [];
        foreach ($this->allowedBodyKeys as $key) {
            if (isset($request[$key])) {
                $body[$key] = $request[$key];
            }
        }

        return $body;
    }

    private function getUrlParameters()
    {
        return $this->urlParams;
    }

    /**
     * Generate value of request id for current request
     *
     * @return string
     */
    private function generateRequestId()
    {
        return uniqid('req-id-', true);
    }
}
