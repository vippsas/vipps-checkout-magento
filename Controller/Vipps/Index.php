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

use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Vipps\Checkout\Model\QuoteRepository as VippsQuoteRepository;
use Vipps\Checkout\Model\SessionManager;
use Psr\Log\LoggerInterface;
use Magento\Framework\Exception\NoSuchEntityException;

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
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

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
        LoggerInterface $logger
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
        $this->logger = $logger;
    }

    public function execute()
    {
        $resultPage = parent::execute();
        if ($resultPage instanceof Redirect) {
            return $resultPage;
        }
        $request = $this->getRequest();

        if (!$this->checkoutSession->getQuoteId()) {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('checkout/cart');

            return $resultRedirect;
        }

        try {
            $vippsCheckoutQuote = $this->vippsQuoteRepository->loadNewByQuote($this->checkoutSession->getQuoteId());
        } catch (NoSuchEntityException) {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('checkout/vipps/session');

            return $resultRedirect;
        }

        try {
            $vippsSession = $this->sessionManager->getSession(
                $vippsCheckoutQuote->getCheckoutSessionId()
            );
        } catch (\Exception $e) {
            $this->logger->error(
                'Vipps checkout session fetch failed',
                ['exception' => $e]
            );
        }

        // if token is null and session not expired & not terminated, reuse token
        if (
            $vippsCheckoutQuote->getCheckoutToken() &&
            $vippsCheckoutQuote->getStatus() !== 'canceled' &&
            $request->getParam('token') === null &&
            !$vippsSession->isSessionExpired() &&
            !$vippsSession->isPaymentTerminated()
        ) {
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setPath(
                    'checkout/vipps',
                    ['_query' => ['token' => $vippsCheckoutQuote->getCheckoutToken()]]
                );

                return $resultRedirect;
        }

        // if token empty or session terminated, create new session
        if (
            $request->getParam('token') === null ||
            $vippsSession->isPaymentTerminated() ||
            $vippsSession->isSessionExpired()
        ) {
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
}