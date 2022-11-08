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
namespace Vipps\Checkout\Model\Profiling;

use Laminas\Http\Response;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Json\DecoderInterface;
use Magento\Store\Model\ScopeInterface;
use Vipps\Checkout\Api\Profiling\Data\ItemInterface;
use Vipps\Checkout\Api\Profiling\Data\ItemInterfaceFactory;
use Vipps\Checkout\Api\Profiling\ItemRepositoryInterface;
use Vipps\Checkout\Gateway\Http\TransferInterface;
use Vipps\Checkout\Model\Gdpr\Compliance;

class Profiler implements ProfilerInterface
{
    /**
     * @var ScopeConfigInterface
     */
    private $config;
    /**
     * @var ItemInterfaceFactory
     */
    private $dataItemFactory;
    /**
     * @var ItemRepositoryInterface
     */
    private $itemRepository;
    /**
     * @var DecoderInterface
     */
    private $jsonDecoder;
    /**
     * @var Compliance
     */
    private $gdprCompliance;
    /**
     * @var string
     */
    private $type;

    /**
     * Profiler constructor.
     *
     * @param ScopeConfigInterface $config
     * @param ItemInterfaceFactory $dataItemFactory
     * @param ItemRepositoryInterface $itemRepository
     * @param DecoderInterface $jsonDecoder
     * @param Compliance $gdprCompliance
     * @param $type
     */
    public function __construct(
        ScopeConfigInterface $config,
        ItemInterfaceFactory $dataItemFactory,
        ItemRepositoryInterface $itemRepository,
        DecoderInterface $jsonDecoder,
        Compliance $gdprCompliance,
        string $type = 'undefined'
    ) {
        $this->config = $config;
        $this->dataItemFactory = $dataItemFactory;
        $this->itemRepository = $itemRepository;
        $this->jsonDecoder = $jsonDecoder;
        $this->gdprCompliance = $gdprCompliance;
        $this->type = $type;
    }

    /**
     * @param TransferInterface $transfer
     * @param Response $response
     *
     * @return string|null
     */
    public function save(TransferInterface $transfer, Response $response)
    {
        if (!$this->isProfilingEnabled()) {
            return null;
        }
        /** @var ItemInterface $itemDO */
        $itemDO = $this->dataItemFactory->create();

        if ($transfer->getUrlParameters('order_id')) {
            $orderId = $transfer->getUrlParameters('order_id');
        } else {
            $orderId = $this->parseOrderId($response);
        }

        $itemDO->setRequestType($this->type);
        $itemDO->setRequest($this->packArray(
            array_merge(['headers' => $transfer->getHeaders()], ['body' => $transfer->getBody()])
        ));

        $itemDO->setStatusCode($response->getStatusCode());
        $itemDO->setIncrementId($orderId);
        $itemDO->setResponse($this->packArray($this->parseResponse($response)));
        $itemDO->setCreatedAt(gmdate(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));

        $item = $this->itemRepository->save($itemDO);
        return $item->getEntityId();
    }

    /**
     * Parse order id from response object
     *
     * @param Response $response
     *
     * @return string|null
     */
    private function parseOrderId(Response $response)
    {
        $content = $this->jsonDecoder->decode($response->getContent());
        return $content['orderId'] ?? ($content['reference'] ?? null);
    }

    /**
     * Parse response data for profiler from response
     *
     * @param Response $response
     *
     * @return array
     */
    private function parseResponse(Response $response)
    {
        try {
            $result = $this->jsonDecoder->decode($response->getContent());
        } catch (\Exception $e) {
            $result = [];
        }
        return $this->depersonalizedResponse($result);
    }

    /**
     * Depersonalize response
     *
     * @param array $response
     *
     * @return array
     */
    private function depersonalizedResponse($response)
    {
        if (isset($response['url'])) {
            unset($response['url']);
        }

        return $this->gdprCompliance->process($response);
    }

    /**
     * Check whether profiler enabled or not
     *
     * @return bool
     */
    private function isProfilingEnabled()
    {
        return (bool)$this->config->getValue('payment/vipps/profiling', ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param $data
     *
     * @return string
     */
    private function packArray($data)
    {
        $recursive = function ($data, $indent = '') use (&$recursive) {
            $output = '{' . PHP_EOL;
            foreach ((array)$data as $key => $value) {
                if (is_array($value)) {
                    $output .= $indent . '    ' . $key . ': ' . $recursive($value, $indent . '    ') . PHP_EOL;
                } elseif (!is_object($value)) {
                    $output .= $indent . '    ' . $key . ': ' . ($value ? $value : '""') . PHP_EOL;
                }
            }
            $output .= $indent . '}' . PHP_EOL;
            return $output;
        };

        return $recursive($data);
    }
}
