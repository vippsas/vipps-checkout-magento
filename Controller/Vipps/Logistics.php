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
declare(strict_types=1);

namespace Vipps\Checkout\Controller\Vipps;

use Laminas\Http\Response;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Result\Layout;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Quote\Api\ShipmentEstimationInterface;
use Psr\Log\LoggerInterface;
use Vipps\Checkout\Api\Data\QuoteInterface;
use Vipps\Checkout\Api\QuoteRepositoryInterface;
use Vipps\Checkout\Model\CountryCodeLocator;
use Vipps\Checkout\Model\Quote;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\ShippingMethodInterface;

/**
 * Class Logistics
 * @package Vipps\Checkout\Controller\Payment
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Logistics implements ActionInterface, CsrfAwareActionInterface
{
    public const MAP_CARRIER_CODES = [
        'bring' => 'POSTEN',
    ];
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var QuoteRepositoryInterface
     */
    private $quoteRepository;
    /**
     * @var \Vipps\Checkout\Model\Quote
     */
    private $vippsQuote;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Json
     */
    private $serializer;
    /**
     * @var ShipmentEstimationInterface
     */
    private $shipmentEstimation;

    /**
     * @var AddressInterfaceFactory
     */
    private $addressFactory;
    /**
     * @var CountryCodeLocator
     */
    private $countryCodeLocator;
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * Logistics constructor.
     *
     * @param ResultFactory $resultFactory
     * @param RequestInterface $request
     * @param Json $serializer
     * @param ShipmentEstimationInterface $shipmentEstimation
     * @param AddressInterfaceFactory $addressFactory
     * @param CartRepositoryInterface $cartRepository
     * @param QuoteRepositoryInterface $quoteRepository
     * @param CountryCodeLocator $countryCodeLocator
     * @param LoggerInterface $logger
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ResultFactory $resultFactory,
        RequestInterface $request,
        Json $serializer,
        ShipmentEstimationInterface $shipmentEstimation,
        AddressInterfaceFactory $addressFactory,
        CartRepositoryInterface $cartRepository,
        QuoteRepositoryInterface $quoteRepository,
        CountryCodeLocator $countryCodeLocator,
        LoggerInterface $logger
    ) {
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->quoteRepository = $quoteRepository;
        $this->serializer = $serializer;
        $this->shipmentEstimation = $shipmentEstimation;
        $this->addressFactory = $addressFactory;
        $this->cartRepository = $cartRepository;
        $this->countryCodeLocator = $countryCodeLocator;
        $this->logger = $logger;
    }

    /**
     * @return ResponseInterface|ResultInterface|Layout
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $this->authorize();

            $requestData = $this->serializer->unserialize('{"streetAddress":"Sandakerveien 6314","postalCode":"0483","region":"OSLO","country":"NO"}');

            $quote = $this->getQuote();
            $shippingMethods = $this->getShippingMethods($requestData, $quote);

            $responseData = $this->prepareResponseData($shippingMethods, $quote);
            $result->setHttpResponseCode(Response::STATUS_CODE_200);
            $result->setData($responseData);
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage());
            $result->setHttpResponseCode(Response::STATUS_CODE_500);
            $result->setData([
                'status' => Response::STATUS_CODE_500,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $result->setHttpResponseCode(Response::STATUS_CODE_500);
            $result->setData([
                'status' => Response::STATUS_CODE_500,
                'message' => __('An error occurred during Shipping Details processing.')
            ]);
        }

        return $result;
    }

    private function authorize()
    {
        if (!$this->request->getParam('reference')) {
            throw new LocalizedException(__('Invalid request parameters'));
        }

        $vippsQuote = $this->getVippsQuote();
        if ($vippsQuote->getStatus() !== Quote::STATUS_NEW) {
            throw new LocalizedException(__('Invalid request'));
        }
    }

    /**
     * @param bool $forceReload
     *
     * @return QuoteInterface
     * @throws NoSuchEntityException
     */
    private function getVippsQuote($forceReload = false): QuoteInterface
    {
        if (null === $this->vippsQuote || $forceReload) {
            $this->vippsQuote = $this->quoteRepository
                ->loadByOrderId($this->request->getParam('reference'));
        }

        return $this->vippsQuote;
    }

    private function createAddress($requestData)
    {
        $address = $this->addressFactory->create();
        $address->addData([
            'postcode' => $requestData['postalCode'] ?? null,
            'street' => $requestData['streetAddress'] ?? null,
            'address_type' => 'shipping',
            'city' => $requestData['region'] ?? null,
            'country_id' => $this->countryCodeLocator->getCountryCode($requestData['country'] ?? null)
        ]);

        return $address;
    }

    private function getQuote()
    {
        return $this->cartRepository->get($this->getVippsQuote()->getQuoteId());
    }

    /**
     * @param array $requestData
     *
     * @return ShippingMethodInterface[]
     * @throws NoSuchEntityException
     */
    private function getShippingMethods($requestData, $quote)
    {
        $quote->setIsActive(true);

        return $this->shipmentEstimation
            ->estimateByExtendedAddress($quote->getId(), $this->createAddress($requestData));
    }

    /**
     * @param array $shippingMethods
     * @param CartInterface $quote
     *
     * @return array
     */
    private function prepareResponseData(array $shippingMethods, CartInterface $quote): array
    {
        $responseData = [];
        foreach ($shippingMethods as $key => $shippingMethod) {
            $methodFullCode = $shippingMethod->getCarrierCode() . '_' . $shippingMethod->getMethodCode();

            $responseData[] = [
                'brand' => $this->getBrand($shippingMethod->getCarrierCode()),
                'amount' => [
                    'currency' => $quote->getStoreCurrencyCode(),
                    'value' => $shippingMethod->getAmount() * 100
                ],
                'id' => $methodFullCode,
                'priority' => $key,
                'isDefault' => false,
                'title' => $shippingMethod->getCarrierTitle(),
                'description' => $shippingMethod->getCarrierTitle() . ' ' . $shippingMethod->getMethodTitle()
            ];
        }

        return $responseData;
    }

    /**
     * Map brands to carrier code
     *
     * @param string $methodCode
     *
     * @return string
     */
    public function getBrand(string $methodCode): string
    {
        if (array_key_exists($methodCode, self::MAP_CARRIER_CODES)) {
            return self::MAP_CARRIER_CODES[$methodCode];
        }

        return 'OTHER';
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
