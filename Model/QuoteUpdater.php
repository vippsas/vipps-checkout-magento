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

namespace Vipps\Checkout\Model;

use Magento\Framework\Locale\Bundle\RegionBundle;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Vipps\Checkout\Gateway\Data\Session;
use Magento\Framework\Locale\ResolverInterface;

/**
 * Class QuoteUpdater
 * @package Vipps\Checkout\Model\Helper
 */
class QuoteUpdater
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;
    /**
     * @var CountryCodeLocator
     */
    private $countryCodeLocator;

    /**
     * QuoteUpdater constructor.
     *
     * @param CartRepositoryInterface $cartRepository
     * @param CountryCodeLocator $countryCodeLocator
     */
    public function __construct(
        CartRepositoryInterface $cartRepository,
        CountryCodeLocator $countryCodeLocator
    ) {
        $this->cartRepository = $cartRepository;
        $this->countryCodeLocator = $countryCodeLocator;
    }

    /**
     * @param CartInterface $quote
     * @param Session $session
     *
     * @return bool|CartInterface|Quote
     */
    public function execute(CartInterface $quote, Session $session)
    {
        /** @var Quote $quote */
        $this->updateQuoteAddresses($quote, $session);

        /**
         * Unset shipping assignment to prevent from saving / applying outdated data
         * @see \Magento\Quote\Model\QuoteRepository\SaveHandler::processShippingAssignment
         */
        if ($quote->getExtensionAttributes()) {
            $quote->getExtensionAttributes()->setShippingAssignments(null);
        }
        $this->cartRepository->save($quote);
        return $quote;
    }

    /**
     * @param Quote $quote
     * @param Session $session
     */
    private function updateQuoteAddresses(Quote $quote, Session $session)
    {
        $this->updateBillingAddress($quote, $session);
        if (!$quote->getIsVirtual()) {
            $this->updateShippingAddress($quote, $session);
        }
    }

    /**
     * @param Quote $quote
     * @param Session $session
     */
    private function updateShippingAddress(Quote $quote, Session $session)
    {
        $shippingDetails = $session->getShippingDetails();

        $shippingAddress = $quote->getShippingAddress();

        $shippingAddress->setFirstname($shippingDetails->getFirstName());
        $shippingAddress->setLastname($shippingDetails->getLastName());
        $shippingAddress->setEmail($shippingDetails->getEmail());
        $shippingAddress->setTelephone($shippingDetails->getPhoneNumber());

        $shippingAddress->setStreetFull($shippingDetails->getStreetAddress());
        $shippingAddress->setPostcode($shippingDetails->getPostalCode());
        $shippingAddress->setCity($shippingDetails->getRegion());
        $shippingAddress->setCountryId($this->countryCodeLocator->getCountryCode($shippingDetails->getCountry()));

        $shippingAddress->setShippingMethod($shippingDetails->getShippingMethodId());
        $shippingAddress->setShippingAmount(0);

        //We do not save user address from vipps in Magento
        $shippingAddress->setSaveInAddressBook(false);
    }

    /**
     * @param Quote $quote
     * @param Session $session
     */
    private function updateBillingAddress(Quote $quote, Session $session)
    {
        $billingDetails = $session->getBillingDetails();

        $billingAddress = $quote->getBillingAddress();

        $billingAddress->setFirstname($billingDetails->getFirstName());
        $billingAddress->setLastname($billingDetails->getLastName());
        $billingAddress->setEmail($billingDetails->getEmail());
        $billingAddress->setTelephone($billingDetails->getPhoneNumber());

        $billingAddress->setStreetFull($billingDetails->getStreetAddress());
        $billingAddress->setPostcode($billingDetails->getPostalCode());
        $billingAddress->setCity($billingDetails->getRegion());

        $billingAddress->setCountryId($this->countryCodeLocator->getCountryCode($billingDetails->getCountry()));

        //We do not save user address from vipps in Magento
        $billingAddress->setSaveInAddressBook(false);
    }
}
