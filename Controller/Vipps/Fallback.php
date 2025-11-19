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

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Session\SessionManagerInterface as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;
use Vipps\Checkout\Api\QuoteRepositoryInterface;
use Vipps\Checkout\Model\OrderLocator;
use Vipps\Checkout\Model\Quote;
use Vipps\Checkout\Api\Data\QuoteInterface;
use Vipps\Checkout\Gateway\Data\Session;
use Vipps\Checkout\Model\SessionProcessor;
use Magento\Framework\Message\ManagerInterface;
use Magento\Payment\Gateway\ConfigInterface;
use Vipps\Checkout\Model\SessionManager;

class Fallback implements ActionInterface
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;
    /**
     * @var QuoteRepositoryInterface
     */
    private $quoteRepository;
    /**
     * @var SessionProcessor
     */
    private $sessionProcessor;
    /**
     * @var OrderLocator
     */
    private $orderLocator;
    /**
     * @var OrderInterface
     */
    private $order;
    /**
     * @var ManagerInterface
     */
    private $messageManager;
    /**
     * @var ConfigInterface
     */
    private $config;
    /**
     * @var QuoteInterface
     */
    private $vippsQuote;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var ResultFactory
     */
    private $resultFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var SessionManager
     */
    private $sessionManager;

    /**
     * Fallback constructor.
     *
     * @param CheckoutSession $checkoutSession
     * @param CartRepositoryInterface $cartRepository
     * @param QuoteRepositoryInterface $quoteRepository
     * @param SessionProcessor $sessionProcessor
     * @param OrderLocator $orderLocator
     * @param ManagerInterface $messageManager
     * @param ConfigInterface $config
     * @param RequestInterface $request
     * @param ResultFactory $resultFactory
     * @param LoggerInterface $logger
     * @param SessionManager $sessionManager
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $cartRepository,
        QuoteRepositoryInterface $quoteRepository,
        SessionProcessor $sessionProcessor,
        OrderLocator $orderLocator,
        ManagerInterface $messageManager,
        ConfigInterface $config,
        RequestInterface $request,
        ResultFactory $resultFactory,
        LoggerInterface $logger,
        SessionManager $sessionManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
        $this->quoteRepository = $quoteRepository;
        $this->sessionProcessor = $sessionProcessor;
        $this->orderLocator = $orderLocator;
        $this->messageManager = $messageManager;
        $this->config = $config;
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->logger = $logger;
        $this->sessionManager = $sessionManager;
    }

    public function execute()
    {
        $session = null;

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $this->authorize();

            $vippsQuote = $this->getVippsQuote();
            if ($vippsQuote->getStatus() === Quote::STATUS_CANCELED) {
                $this->messageManager->addWarningMessage(__('Your order was cancelled in Vipps.'));
            } elseif ($vippsQuote->getStatus() === Quote::STATUS_NEW) {
                $session = $this->sessionProcessor->process($vippsQuote);
                $this->defineMessage($session);
            } else {
                $session = $this->sessionManager->getSession($vippsQuote->getCheckoutSessionId());
                $this->defineMessage($session);
            }
        } catch (LocalizedException $e) {
            $this->logger->critical($this->wrapExceptionMessage($e));
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->logger->critical($this->wrapExceptionMessage($e));
            $this->messageManager->addErrorMessage(
                __('A server error stopped your transaction from being processed.'
                    . ' Please contact to store administrator.')
            );
        } finally {
            $resultRedirect = $this->prepareResponse($resultRedirect, $session);
            $this->logger->debug($this->request->getRequestString());
        }

        return $resultRedirect;
    }

    private function authorize()
    {
        if (!$this->request->getParam('reference')) {
            throw new LocalizedException(__('Invalid request parameters'));
        }

        $vippsQuote = $this->getVippsQuote();
        if ($vippsQuote->getStatus() === Quote::STATUS_CANCELED) {
            return;
        }
        if ($vippsQuote->getStatus() === Quote::STATUS_RESERVED) {
            return;
        }
        if ($vippsQuote->getStatus() !== Quote::STATUS_NEW) {
            throw new LocalizedException(__('Invalid request'));
        }
    }

    /**
     * @param bool $forceReload
     *
     * @return QuoteInterface
     * @throws NoSuchEntityException
     */
    private function getVippsQuote($forceReload = false): QuoteInterface
    {
        if (null === $this->vippsQuote || $forceReload) {
            $this->vippsQuote = $this->quoteRepository
                ->loadByOrderId($this->request->getParam('reference'));
        }

        return $this->vippsQuote;
    }

    /**
     * @param Redirect $resultRedirect
     * @param Session $session
     *
     * @return Redirect
     * @throws \Exception
     */
    private function prepareResponse(Redirect $resultRedirect, Session $session = null)
    {
        $cartPersistent = $this->config->getValue('cancellation_cart_persistence');

        if ($session && $session->getPaymentDetails()->isTerminated() && $cartPersistent) {
            $this->restoreQuote($this->getVippsQuote(true));
            $resultRedirect->setPath('checkout/cart', ['_secure' => true]);
        } else {
            $this->storeLastOrder();
            if ($session && $session->getPaymentDetails()->isAuthorised()) {
                $resultRedirect->setPath('checkout/onepage/success', ['_secure' => true]);
            } else {
                $resultRedirect->setPath('checkout/onepage/failure', ['_secure' => true]);
            }
        }

        return $resultRedirect;
    }

    /**
     * Method store order info to checkout session
     */
    private function storeLastOrder()
    {
        $order = $this->getOrder();
        if (!$order) {
            return;
        }

        $this->checkoutSession->clearStorage();
        $this->checkoutSession->setLastQuoteId($order->getQuoteId());
        $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
        $this->checkoutSession->setLastOrderId($order->getEntityId());
        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->checkoutSession->setLastOrderStatus($order->getStatus());
    }

    /**
     * @param QuoteInterface $vippsQuote
     *
     * @throws NoSuchEntityException
     */
    private function restoreQuote(QuoteInterface $vippsQuote)
    {
        $quote = $this->cartRepository->get($vippsQuote->getQuoteId());

        /** @var Quote $quote */
        $quote->setIsActive(true);
        $quote->setReservedOrderId(null);

        $this->cartRepository->save($quote);
        $this->checkoutSession->replaceQuote($quote);
    }

    private function defineMessage(Session $session): void
    {
        if ($session->getPaymentDetails()->isTerminated()) {
            $this->messageManager->addWarningMessage(__('Your order was cancelled in Vipps.'));
        } elseif ($session->getPaymentDetails()->isAuthorised()) {
            $this->messageManager->addSuccessMessage(__('Your order was successfully placed.'));
        } else {
            $this->messageManager->addWarningMessage(
                __('We have not received a confirmation that order was reserved. It will be checked later again.')
            );
        }
    }

    /**
     * @param false $forceReload
     *
     * @return OrderInterface|null
     * @throws NoSuchEntityException
     */
    private function getOrder($forceReload = false): ?OrderInterface
    {
        if (null === $this->order || $forceReload) {
            $vippsQuote = $this->getVippsQuote($forceReload);
            $this->order = $this->orderLocator->get($vippsQuote->getReservedOrderId());
        }

        return $this->order;
    }

    private function wrapExceptionMessage($e): string
    {
        $message =
            \sprintf('QuoteID: %s. Exception message: %s.', $this->checkoutSession->getQuoteId(), $e->getMessage());

        if ($this->config->getValue('debug')) {
            $message .= \sprintf('Stack Trace %s', $e->getTraceAsString());
        }

        return $message;
    }
}
