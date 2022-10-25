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
namespace Vipps\Checkout\Controller\Vipps;

use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Http\Context;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;
use Vipps\Checkout\Model\CountryCodeLocator;

class UpdateCustomerInformation implements ActionInterface, CsrfAwareActionInterface
{
    private ResultFactory $resultFactory;
    private RequestInterface $request;
    private Json $serializer;
    private LoggerInterface $logger;
    private MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId;
    private CartRepositoryInterface $cartRepository;
    private CountryCodeLocator $countryCodeLocator;
    private Context $context;

    public function __construct(
        ResultFactory $resultFactory,
        RequestInterface $request,
        Json $serializer,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        CartRepositoryInterface $cartRepository,
        CountryCodeLocator $countryCodeLocator,
        LoggerInterface $logger,
        Context $context
    ) {
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->serializer = $serializer;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->cartRepository = $cartRepository;
        $this->countryCodeLocator = $countryCodeLocator;
        $this->logger = $logger;
        $this->context = $context;
    }

    public function execute()
    {
        $data = $this->request->getPost();

        $cartId = $this->context->getValue('customer_logged_in')
            ? $data['cartId']
            : $this->maskedQuoteIdToQuoteId->execute($data['cartId']);
        $quote = $this->cartRepository->get($cartId);

        $this->updateBillingAddress($quote, $data);
        $this->updateShippingAddress($quote, $data);

        $this->cartRepository->save($quote);

        /** @var ResultJson $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData([]);

        return $resultJson;
    }

    /**
     * @param CartInterface $quote
     * @param $data
     */
    private function updateBillingAddress(CartInterface $quote, $data)
    {
        $billingAddress = $quote->getBillingAddress();
        if ($billingAddress) {
            $billingAddress->setLastname($data->get('lastName'));
            $billingAddress->setFirstname($data->get('firstName'));
            $billingAddress->setEmail($data->get('email'));
            $billingAddress->setTelephone($data->get('phone'));
            $billingAddress->setCity($data->get('city'));
            $billingAddress->setCountryId($this->countryCodeLocator->getCountryCode($data->get('country')));
            $billingAddress->setStreet($data->get('address'));
            $billingAddress->setPostcode($data->get('zip'));
            $billingAddress->setSameAsBilling(false);
            $billingAddress->setSaveInAddressBook(false);
            $billingAddress->setCustomerAddressId(null);
        }
    }

    /**
     * @param CartInterface|Quote $quote
     * @param $data
     */
    private function updateShippingAddress(CartInterface $quote, $data)
    {
        $shippingAddress = $quote->getShippingAddress();
        if ($shippingAddress) {
            $shippingAddress->setLastname($data->get('lastName'));
            $shippingAddress->setFirstname($data->get('firstName'));
            $shippingAddress->setEmail($data->get('email'));
            $shippingAddress->setTelephone($data->get('phone'));
            $shippingAddress->setCity($data->get('city'));
            $shippingAddress->setCountryId($this->countryCodeLocator->getCountryCode($data->get('country')));
            $shippingAddress->setStreet($data->get('address'));
            $shippingAddress->setPostcode($data->get('zip'));
            $shippingAddress->setSameAsBilling(true);
            $shippingAddress->setSaveInAddressBook(false);
            $shippingAddress->setCustomerAddressId(null);
        }
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
