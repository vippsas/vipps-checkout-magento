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
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\HTTP\Adapter\Curl as MagentoCurl;
use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Vipps\Checkout\Gateway\Config\Config;
use Vipps\Checkout\Gateway\Http\Client\ClientInterface;
use Vipps\Checkout\Model\ModuleMetadataInterface;
use Vipps\Checkout\Model\QuoteRepository as VippsQuoteRepository;
use Vipps\Checkout\Model\SessionManager;
use Vipps\Checkout\Model\UrlResolver;

class Index extends \Magento\Checkout\Controller\Index\Index
{

    /**
     * @var CheckoutSession
     */
    protected CheckoutSession $checkoutSession;

    /**
     * @var VippsQuoteRepository
     */
    protected VippsQuoteRepository $vippsQuoteRepository;

    /**
     * @var SessionManager
     */
    protected SessionManager $sessionManager;

    /**
     * @var \Magento\Framework\HTTP\Adapter\CurlFactory
     */
    protected CurlFactory $adapterFactory;

    /**
     * @var UrlResolver
     */
    private UrlResolver $urlResolver;

    /**
     * @var Json
     */
    private Json $serializer;

    /**
     * @var ModuleMetadataInterface
     */
    private ModuleMetadataInterface $moduleMetadata;

    /**
     * @var Config
     */
    private Config $config;

    /**
     * Index constructor.
     *
     * @param CheckoutSession $checkoutSession
     * @param VippsQuoteRepository $vippsQuoteRepository
     * @param SessionManager $sessionManager
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $accountManagement
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\Translate\InlineInterface $translateInline
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\View\LayoutFactory $layoutFactory
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Framework\HTTP\Adapter\CurlFactory $adapterFactory
     * @param UrlResolver $urlResolver
     * @param Json $serializer
     * @param ModuleMetadataInterface $moduleMetadata
     * @param Config $config
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        VippsQuoteRepository $vippsQuoteRepository,
        SessionManager $sessionManager,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $accountManagement,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\View\LayoutFactory $layoutFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        CurlFactory $adapterFactory,
        UrlResolver $urlResolver,
        Json $serializer,
        ModuleMetadataInterface $moduleMetadata,
        Config $config,
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $customerRepository,
            $accountManagement,
            $coreRegistry,
            $translateInline,
            $formKeyValidator,
            $scopeConfig,
            $layoutFactory,
            $quoteRepository,
            $resultPageFactory,
            $resultLayoutFactory,
            $resultRawFactory,
            $resultJsonFactory
        );
        $this->checkoutSession = $checkoutSession;
        $this->vippsQuoteRepository = $vippsQuoteRepository;
        $this->sessionManager = $sessionManager;
        $this->adapterFactory = $adapterFactory;
        $this->urlResolver = $urlResolver;
        $this->serializer = $serializer;
        $this->moduleMetadata = $moduleMetadata;
        $this->config = $config;
    }

    public function execute()
    {
        $resultPage = parent::execute();
        if ($resultPage instanceof Redirect) {
            return $resultPage;
        }

        $currentVippsData = null;
        if ($this->checkoutSession->getQuoteId()) {
            try {
                $currentVippsData = $this->vippsQuoteRepository->loadNewByQuote($this->checkoutSession->getQuoteId());
                $vippsSession = $this->getSession($currentVippsData->getCheckoutSessionId());
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // No Vipps data found for the current quote
            }
        }

        if ($currentVippsData &&
            $currentVippsData->getCheckoutToken() &&
            $currentVippsData->getStatus() !== 'canceled' &&
            $this->getRequest()->getParam('token') === null) {
            $session = $this->sessionManager->getSession(
                $currentVippsData->getCheckoutSessionId()
            );
            if (!$session->isSessionExpired() && !$session->isPaymentTerminated()) {
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setPath(
                    'checkout/vipps',
                    ['_query' => ['token' => $currentVippsData->getCheckoutToken()]]
                );

                return $resultRedirect;
            }
        }

        $request = $this->getRequest();

        $sessionTerminated = false;
        if ($currentVippsData &&
            $currentVippsData->getCheckoutSessionId() &&
            $currentVippsData->getStatus() !== 'canceled') {
            $session = $this->sessionManager->getSession(
                $currentVippsData->getCheckoutSessionId()
            );

            if ($session->isSessionExpired() || $session->isPaymentTerminated()) {
                $sessionTerminated = true;
            }
        }

        if (isset($vippsSession['sessionState']) && ($vippsSession['sessionState'] === 'PaymentTerminated' ||
            $vippsSession['sessionState'] === 'SessionExpired')) {
            $sessionTerminated = true;
        }

        if ($request->getParam('token') === null || $sessionTerminated) {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('checkout/vipps/session');

            return $resultRedirect;
        }

        // Fallback to cart page if invalid quote was made, i.e. with invalid quantity
        if ($request->getParam('token') === '') {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('checkout/cart');

            return $resultRedirect;
        }

        $resultPage->getConfig()->getTitle()->set(__('Checkout'));

        return $resultPage;
    }

    private function getSession($sessionId)
    {

        $headers = [
            'Content-Type' => 'application/json',
            'Idempotency-Key' => uniqid('req-id-', true),
            'X-Source-Address' => '',
            'X-TimeStamp' => '',
            'Merchant-Serial-Number' => $this->config->getValue('merchant_serial_number'),
            'Client_Id' => $this->config->getValue('client_id'),
            'Client_Secret' => $this->config->getValue('client_secret'),
            'Ocp-Apim-Subscription-Key' => $this->config->getValue('subscription_key1'),
        ];

        $headers = $this->moduleMetadata->addOptionalHeaders($headers);

        $result = [];
        foreach ($headers as $key => $value) {
            $result[] = sprintf('%s: %s', $key, $value);
        }
        $headers = $result;

        $transfer = [
            'method' => Request::METHOD_GET,
            'uri' => $this->urlResolver->getUrl('/checkout/v3/session/')  . $sessionId,
            'body' => [
                'reference' => $sessionId,
            ]
        ];

        try {
            $adapter = null;
            /** @var MagentoCurl $adapter */
            $adapter = $this->adapterFactory->create();
            $options = [
                CURLOPT_TIMEOUT => 30,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POSTFIELDS => $this->serializer->serialize($transfer['body'])
            ];
            $adapter->setOptions($options);

            $adapter->write(
                $transfer['method'],
                $transfer['uri'],
                '1.1',
                $headers,
                $this->serializer->serialize($transfer['body'])
            );

            $response = $adapter->read();

            return $this->serializer->unserialize(Response::fromString($response)->getContent());
        } finally {
            $adapter ? $adapter->close() : null;
        }
    }
}
