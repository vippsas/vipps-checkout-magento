<?php
/**
 * Copyright 2022 Vipps
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software
 * and associated documentation files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING
 * BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NON
 * INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM,
 * DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Vipps\Checkout\Api\Delivery;

interface MethodMappingInterface
{
    /**
     * Bring Posten delivery code mapping, you can add here your own delivery code mapping
     */
    public const CARRIER_CODE = [
        'bring' => 'POSTEN',
    ];

    /**
     * Delivery method name mapping
     */
    public const METHOD_CODE = [
        '5800' => 'PICKUP_POINT',
        '5600' => 'HOME_DELIVERY',
        '5000' => 'HOME_DELIVERY',
        '4850' => 'HOME_DELIVERY',
        'PAKKE_I_POSTKASSEN' => 'MAILBOX',
        'PAKKE_I_POSTKASSEN_SPORBAR' => 'MAILBOX',
        'MAIL' => 'MAILBOX',
        'EXPRESS_NORDIC_SAME_DAY' => 'MAILBOX',
        'EXPRESS_INTERNATIONAL_0900' => 'MAILBOX',
        'EXPRESS_INTERNATIONAL_1200' => 'MAILBOX',
        'EXPRESS_INTERNATIONAL' => 'MAILBOX',
        'EXPRESS_ECONOMY' => 'MAILBOX',
        '5100' => 'MAILBOX',
        'BUSINESS_PARCEL' => 'MAILBOX',
        'BUSINESS_PARCEL_BULK' => 'PICKUP_POINT',
        'COURIER_VIP' => 'HOME_DELIVERY',
        'COURIER_1H' => 'HOME_DELIVERY',
        'COURIER_2H' => 'HOME_DELIVERY',
        'COURIER_4H' => 'HOME_DELIVERY',
        'COURIER_6H' => 'HOME_DELIVERY',
        'OIL_EXPRESS' => 'MAILBOX'
    ];
}
