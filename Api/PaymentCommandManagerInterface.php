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

use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Vipps\Checkout\Gateway\Exception\VippsException;

/**
 * Interface PaymentCommandManagerInterface
 * @package Vipps\Checkout\Api
 * @api
 */
interface PaymentCommandManagerInterface
{
    /**
     * @param string $orderId
     * @param array $arguments
     *
     * @return mixed
     */
    public function getPayment($orderId, $arguments = []);

    /**
     * @param string $orderId
     * @param array $arguments
     *
     * @return mixed
     */
    public function getPaymentEventLog($orderId, $arguments = []);

    /**
     * Send Receipt.
     *
     * @param array $arguments
     *
     * @return mixed
     * @throws VippsException
     */
    public function sendReceipt(OrderInterface $order, $arguments = []);

    /**
     * Method to execute cancel Command.
     *
     * @param InfoInterface $payment
     * @param array $arguments
     *
     * @return mixed
     */
    public function cancel(InfoInterface $payment, $arguments = []);
}
