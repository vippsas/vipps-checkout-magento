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
declare(strict_types=1);

namespace Vipps\Checkout\Controller\Vipps;

use Laminas\Http\Response;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\Layout;
use Psr\Log\LoggerInterface;
use Vipps\Checkout\Api\Data\QuoteInterface;
use Vipps\Checkout\Api\QuoteRepositoryInterface;
use Vipps\Checkout\Model\Quote;
use Vipps\Checkout\Model\SessionProcessor;

/**
 * Class Callback
 * @package Vipps\Checkout\Controller\Payment
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Callback implements ActionInterface, CsrfAwareActionInterface
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var QuoteRepositoryInterface
     */
    private $quoteRepository;
    /**
     * @var \Vipps\Checkout\Model\Quote
     */
    private $vippsQuote;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var SessionProcessor
     */
    private SessionProcessor $sessionProcessor;

    /**
     * Callback constructor.
     *
     * @param ResultFactory $resultFactory
     * @param RequestInterface $request
     * @param QuoteRepositoryInterface $quoteRepository
     * @param SessionProcessor $sessionProcessor
     * @param LoggerInterface $logger
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ResultFactory $resultFactory,
        RequestInterface $request,
        QuoteRepositoryInterface $quoteRepository,
        SessionProcessor $sessionProcessor,
        LoggerInterface $logger
    ) {
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->quoteRepository = $quoteRepository;
        $this->logger = $logger;
        $this->sessionProcessor = $sessionProcessor;
    }

    /**
     * @return ResponseInterface|ResultInterface|Layout
     */
    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        try {
            $this->authorize();

            $vippsQuote = $this->getVippsQuote();
            if ($vippsQuote->getStatus() === Quote::STATUS_NEW) {
                $this->sessionProcessor->process($vippsQuote);
            }

            $result->setHttpResponseCode(Response::STATUS_CODE_202);
            $result->setData([]);
        } catch (LocalizedException $e) {
            $this->logger->critical($e->getMessage());
            $result->setHttpResponseCode(Response::STATUS_CODE_500);
            $result->setData([
                'status' => Response::STATUS_CODE_500,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            $result->setHttpResponseCode(Response::STATUS_CODE_500);
            $result->setData([
                'status' => Response::STATUS_CODE_500,
                'message' => __('An error occurred during Shipping Details processing.')
            ]);
        }

        return $result;
    }

    private function authorize()
    {
        if (!$this->request->getParam('order')) {
            throw new LocalizedException(__('Invalid request parameters'));
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
                ->loadByOrderId($this->request->getParam('order'));
        }

        return $this->vippsQuote;
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
