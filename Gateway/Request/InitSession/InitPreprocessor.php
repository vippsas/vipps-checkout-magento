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

use Vipps\Checkout\Model\QuoteFactory;
use Vipps\Checkout\Model\QuoteRepository;
use Vipps\Checkout\Api\Data\QuoteInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Vipps\Checkout\Gateway\Request\SubjectReader;
use Vipps\Checkout\Model\Method\Vipps;

/**
 * Class InitPreprocessor
 * @package Vipps\Checkout\Gateway\Request\InitSession
 */
class InitPreprocessor implements BuilderInterface
{
    /**
     * @var SubjectReader
     */
    private $subjectReader;

    /**
     * InitPreprocessor constructor.
     *
     * @param SubjectReader $subjectReader
     */
    public function __construct(
        SubjectReader $subjectReader,
        private QuoteFactory $quoteFactory,
        private QuoteRepository $quoteRepository,
    ) {
        $this->subjectReader = $subjectReader;
    }

    /**
     * Get merchant related data for Initiate payment request.
     *
     * @param array $buildSubject
     *
     * @return array
     * @throws \Exception
     */
    public function build(array $buildSubject)
    {
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $this->subjectReader->readPayment($buildSubject);
        $payment = $paymentDO->getPayment();

        if ($payment instanceof QuotePayment) {
            $payment->setMethod(Vipps::METHOD_CODE);

            $quote = $payment->getQuote();
            $quote->setReservedOrderId(null);
            $quote->reserveOrderId();

            $quote->getPayment()
                ->setAdditionalInformation(Vipps::METHOD_TYPE_KEY, Vipps::METHOD_TYPE_CHECKOUT);

            try {
                $vippsQuote = $this->quoteRepository->loadNewByQuote($quote->getId());

            } catch (NoSuchEntityException $e) {
                /** @var QuoteInterface $vippsQuote */
                $vippsQuote = $this->quoteFactory->create();
                $vippsQuote->setStoreId($quote->getStoreId());
                $vippsQuote->setQuoteId((int) $quote->getId());
                $vippsQuote->setStatus(QuoteInterface::STATUS_NEW);
            }
            if ($vippsQuote->getReservedOrderId() !== $quote->getReservedOrderId()) {
                $vippsQuote->setReservedOrderId($quote->getReservedOrderId());
                $this->quoteRepository->save($vippsQuote);
            }
        }

        return [];
    }
}
