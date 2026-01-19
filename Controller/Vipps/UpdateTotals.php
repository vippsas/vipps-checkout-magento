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

use Laminas\Http\Request;
use Laminas\Http\Response;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Http\Context;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json as ResultJson;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\HTTP\Adapter\Curl as MagentoCurl;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;
use Vipps\Checkout\Model\Exception\AcquireLockException;
use Vipps\Checkout\Model\LockManager;
use Vipps\Checkout\Model\QuoteRepository as VippsQuoteRepository;
use Vipps\Checkout\Model\SessionManager;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use Vipps\Checkout\Gateway\Http\TransferFactory;
use Vipps\Checkout\Gateway\Http\TransferInterface;
use Vipps\Checkout\Gateway\Http\Client\CheckoutCurl;

class UpdateTotals implements ActionInterface, CsrfAwareActionInterface
{
    private ResultFactory $resultFactory;
    private RequestInterface $request;
    private Json $serializer;
    private MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId;
    private CartRepositoryInterface $cartRepository;
    private LoggerInterface $logger;
    private Context $context;
    private VippsQuoteRepository $vippsQuoteRepository;

    private SessionManager $sessionManager;

    private LockManager $lockManager;

    private ShippingMethodManagementInterface $shippingMethodManagement;

    private TransferFactory $transferFactory;

    private CheckoutCurl $checkoutCurl;

    public function __construct(
        ResultFactory $resultFactory,
        RequestInterface $request,
        Json $serializer,
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        CartRepositoryInterface $cartRepository,
        LoggerInterface $logger,
        Context $context,
        VippsQuoteRepository $vippsQuoteRepository,
        SessionManager $sessionManager,
        LockManager $lockManager,
        ShippingMethodManagementInterface $shippingMethodManagement,
        TransferFactory $transferFactory,
        CheckoutCurl $checkoutCurl,

    ) {
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->serializer = $serializer;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->cartRepository = $cartRepository;
        $this->logger = $logger;
        $this->context = $context;
        $this->vippsQuoteRepository = $vippsQuoteRepository;
        $this->sessionManager = $sessionManager;
        $this->lockManager = $lockManager;
        $this->shippingMethodManagement = $shippingMethodManagement;
        $this->transferFactory = $transferFactory;
        $this->checkoutCurl = $checkoutCurl;
    }

    public function execute()
    {
        $data = $this->request->getPost();

        $cartId = $this->context->getValue('customer_logged_in')
            ? $data['cartId']
            : $this->maskedQuoteIdToQuoteId->execute($data['cartId']);
        $quote = $this->cartRepository->get($cartId);

        $currentTotal = (int) $quote->getGrandTotal() * 100;
        $vippsTotal = (int) ($data['fractionalDenomination'] ?? null);
        $forceUpdate = isset($data['forceUpdate']) ? $data['forceUpdate'] : false;

        if ($forceUpdate || $currentTotal !== $vippsTotal) {
            if ($data['shippingId']) {
                $this->updateVippsSession($quote, $data['shippingId']);
            } else {
                $this->updateVippsSession($quote);
            }
        }

        if ($data['shippingId']) {
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setShippingMethod($data['shippingId']);

            $amount = $data['shippingPrice'] / 100;
            $shippingAddress->setShippingAmount($amount);

            $quote->getShippingAddress()->setCollectShippingRates(true);
            $quote->collectTotals();

            $this->cartRepository->save($quote);
        }


        /** @var ResultJson $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData([]);

        return $resultJson;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    private function updateVippsSession($quote, $shippingId = false)
    {
        $shippingMethods = $this->shippingMethodManagement->getList($quote->getId());
        $shippingMethodGroupedArray = [];
        foreach ($shippingMethods as $shippingMethod) {
            $carrierCode = $shippingMethod->getCarrierCode();
            $shippingMethodGroupedArray[$carrierCode][] = $shippingMethod;
        }

        if (!$shippingId) {
            $shippingId = '';
        }

        if ($shippingMethodGroupedArray) {
            $shippingMethodBody = [];
            $counter = 0;

            foreach ($shippingMethodGroupedArray as $shippingMethodGroup) {
                foreach ($shippingMethodGroup as $shippingMethod) {
                    $shippingMethodBody[$counter] = [
                        'id' => $shippingMethod->getCarrierCode() . '_' . $shippingMethod->getMethodCode(),
                        'amount' => [
                            'currency' => $quote->getStoreCurrencyCode(),
                            'value' => $shippingMethod->getAmount() * 100
                        ],
                        'isDefault' => ($shippingMethod->getCarrierCode() . '_' . $shippingMethod->getMethodCode()) == $shippingId,
                        'priority' => $counter,
                        'title' => $shippingMethod->getCarrierTitle() ?: '',
                        'description' => $shippingMethod->getMethodTitle() ?: '',
                        'brand' => (str_contains($shippingMethod->getCarrierCode(), 'bring')) ? 'POSTEN' : 'OTHER',
                    ];

                    if ($shippingMethodBody[$counter]['brand'] === 'POSTEN') {
                        if (method_exists(
                                $shippingMethod->getExtensionAttributes(),
                                'getLogoUrl'
                            ) && $shippingMethod->getExtensionAttributes()->getLogoUrl() && str_contains(
                                $shippingMethod->getExtensionAttributes()->getLogoUrl(),
                                'Bring'
                            )) {
                            $shippingMethodBody[$counter]['brand'] = 'BRING';
                        }
                    }
                    $counter++;
                }
            }
        }

        $transferRequest = [
            'reference' => $quote->getReservedOrderId(),
            'transaction' => [
                'amount' => [
                    'currency' => $quote->getStoreCurrencyCode(),
                    'value' => ($quote->getGrandTotal() - $quote->getShippingAddress()->getShippingInclTax() ?? 0) * 100
                ],
                'paymentDescription' => 'Order Id: ' . $quote->getReservedOrderId()
            ]
        ];

        if ($shippingMethods) {
            $transferRequest['logisticOptions'] = $shippingMethodBody;
        }

        /** @var TransferInterface $transfer */
        $transfer = $this->transferFactory->create($transferRequest);

        try {
            $lockName = $this->acquireLock($quote->getReservedOrderId());
            return $this->checkoutCurl->placeRequest($transfer);
        } finally {
            if (isset($lockName)) {
                $this->releaseLock($lockName);
            }
        }
    }

    /**
     * @param $reservedOrderId
     *
     * @return string
     * @throws AlreadyExistsException
     * @throws InputException
     * @throws AcquireLockException
     * @throws \Exception
     */
    private function acquireLock($reservedOrderId)
    {
        $lockName = 'vipps_place_order_' . $reservedOrderId;
        $retries = 0;
        $canLock = $this->lockManager->lock($lockName, 10);

        while (!$canLock && ($retries < 10)) {
            usleep(200000);
            //wait for 0.2 seconds
            $retries++;
            $canLock = $this->lockManager->lock($lockName, 10);
        }

        if (!$canLock) {
            throw new AcquireLockException(
                __('Can not acquire lock for order "%1"', $reservedOrderId)
            );
        }

        return $lockName;
    }

    /**
     * @param $lockName
     *
     * @return bool
     * @throws InputException
     */
    private function releaseLock($lockName)
    {
        return $this->lockManager->unlock($lockName);
    }
}
