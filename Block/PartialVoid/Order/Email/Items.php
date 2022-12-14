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

namespace Vipps\Checkout\Block\PartialVoid\Order\Email;

use Magento\Sales\Block\Order\Email\Items as OrderItems;
use Magento\Sales\Model\Order;

/**
 * Class Items
 * @package Vipps\Checkout\Block\PartialVoid\Order\Email
 */
class Items extends OrderItems
{
    /**
     * @var Order
     */
    private $order;

    /**
     * For this block we only need items that was canceled, using native template is preferred
     * so other items was removed from this order instance (just for template)
     *
     * @return Order
     */
    public function getOrder(): Order
    {
        if (!$this->order) {
            $this->order = clone $this->getData('order');
            $items = $this->order->getItems();
            foreach ($items as $key => $item) {
                if (!$item->getQtyCanceled()) {
                    unset($items[$key]);
                }
            }
            $this->order->setItems($items);
        }

        return $this->order;
    }
}
