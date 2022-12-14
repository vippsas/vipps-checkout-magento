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

namespace Vipps\Checkout\Model\Adminhtml\Config\Source\Cancellation;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Type
 */
class Type implements ArrayInterface
{
    /**
     * @var string
     */
    const AUTOMATIC = 'automatic';

    /**
     * @var string
     */
    const MANUAL = 'manual';

    /**
     * Order cancellation options.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::AUTOMATIC,
                'label' => __('Automatic'),
            ],
            [
                'value' => self::MANUAL,
                'label' => __('Manual'),
            ]
        ];
    }
}
