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

namespace Vipps\Checkout\Api;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Vipps\Checkout\Api\Data\QuoteInterface;

/**
 * Interface QuoteRepositoryInterface
 * @api
 */
interface QuoteRepositoryInterface
{
    /**
     * @param QuoteInterface $quote Monitoring quote.
     * @throws CouldNotSaveException
     * @return void
     */
    public function save(QuoteInterface $quote);

    /**
     * @param $quoteId
     *
     * @return QuoteInterface
     */
    public function get($quoteId): QuoteInterface;

    /**
     * @param $quoteId
     *
     * @return QuoteInterface
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function loadNewByQuote($quoteId): QuoteInterface;

    /**
     * @param string $orderId
     *
     * @return QuoteInterface
     * @throws NoSuchEntityException
     */
    public function loadByOrderId($orderId): QuoteInterface;
}
