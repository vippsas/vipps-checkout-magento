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

namespace Vipps\Checkout\Cron;

use Magento\Framework\App\Config\ScopeCodeResolver;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Vipps\Checkout\Api\Data\QuoteStatusInterface;
use Vipps\Checkout\Model\Order\Cancellation\Config;
use Vipps\Checkout\Model\Quote as VippsQuote;
use Vipps\Checkout\Model\Quote\AttemptManagement;
use Vipps\Checkout\Model\QuoteRepository as VippsQuoteRepository;
use Vipps\Checkout\Model\ResourceModel\Quote\Collection as VippsQuoteCollection;
use Vipps\Checkout\Model\ResourceModel\Quote\CollectionFactory as VippsQuoteCollectionFactory;
use Vipps\Checkout\Model\SessionProcessor;

/**
 * Class FetchOrderStatus
 * @package Vipps\Checkout\Cron
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class FetchOrderFromVipps
{
    /**
     * Order collection page size
     */
    const COLLECTION_PAGE_SIZE = 250;

    /**
     * @var SessionProcessor
     */
    private $sessionProcessor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeCodeResolver
     */
    private $scopeCodeResolver;

    /**
     * @var Config
     */
    private $cancellationConfig;

    /**
     * @var AttemptManagement
     */
    private $attemptManagement;

    /**
     * @var VippsQuoteCollectionFactory
     */
    private $vippsQuoteCollectionFactory;

    /**
     * @var VippsQuoteRepository
     */
    private $vippsQuoteRepository;

    /**
     * FetchOrderFromVipps constructor.
     *
     * @param VippsQuoteCollectionFactory $vippsQuoteCollectionFactory
     * @param VippsQuoteRepository $vippsQuoteRepository
     * @param SessionProcessor $sessionProcessor
     * @param LoggerInterface $logger
     * @param StoreManagerInterface $storeManager
     * @param ScopeCodeResolver $scopeCodeResolver
     * @param Config $cancellationConfig
     * @param AttemptManagement $attemptManagement
     */
    public function __construct(
        VippsQuoteCollectionFactory $vippsQuoteCollectionFactory,
        VippsQuoteRepository $vippsQuoteRepository,
        SessionProcessor $sessionProcessor,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager,
        ScopeCodeResolver $scopeCodeResolver,
        Config $cancellationConfig,
        AttemptManagement $attemptManagement
    ) {
        $this->sessionProcessor = $sessionProcessor;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
        $this->scopeCodeResolver = $scopeCodeResolver;
        $this->cancellationConfig = $cancellationConfig;
        $this->attemptManagement = $attemptManagement;
        $this->vippsQuoteCollectionFactory = $vippsQuoteCollectionFactory;
        $this->vippsQuoteRepository = $vippsQuoteRepository;
    }

    /**
     * Create orders from Vipps that are not created in Magento yet
     *
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        try {
            $currentStore = $this->storeManager->getStore()->getId();
            $currentPage = 1;
            do {
                $vippsQuoteCollection = $this->createCollection($currentPage);
                $this->logger->debug('Fetched payment details');
                /** @var VippsQuote $vippsQuote */
                foreach ($vippsQuoteCollection as $vippsQuote) {
                    $this->processQuote($vippsQuote);
                    usleep(1000000); //delay for 1 second
                }
                $currentPage++;
            } while ($currentPage <= $vippsQuoteCollection->getLastPageNumber());
        } finally {
            $this->storeManager->setCurrentStore($currentStore);
        }
    }

    /**
     * @param VippsQuote $vippsQuote
     *
     * @throws CouldNotSaveException
     */
    private function processQuote(VippsQuote $vippsQuote)
    {
        try {
            $this->prepareEnv($vippsQuote);

            $vippsQuote->incrementAttempt();
            $this->vippsQuoteRepository->save($vippsQuote);

            $this->sessionProcessor->process($vippsQuote);
        } catch (\Throwable $t) {
            $this->logger->critical($t->getMessage(), ['vipps_quote_id' => $vippsQuote->getId()]);

            $attempt = $this->attemptManagement->createAttempt($vippsQuote);
            $attempt->setMessage($t->getMessage());
            $this->attemptManagement->save($attempt);
        }
    }

    /**
     * Prepare environment.
     *
     * @param VippsQuote $quote
     */
    private function prepareEnv(VippsQuote $quote)
    {
        // set quote store as current store
        $this->scopeCodeResolver->clean();

        $this->storeManager->setCurrentStore($quote->getStoreId());
    }

    /**
     * @param $currentPage
     *
     * @return VippsQuoteCollection
     */
    private function createCollection($currentPage)
    {
        /** @var VippsQuoteCollection $collection */
        $collection = $this->vippsQuoteCollectionFactory->create();

        $collection
            ->setPageSize(self::COLLECTION_PAGE_SIZE)
            ->setCurPage($currentPage)
            ->addFieldToFilter(
                'attempts',
                [
                    ['lt' => $this->cancellationConfig->getAttemptsMaxCount()],
                    ['null' => 1]
                ]
            )
            ->addFieldToFilter(
                QuoteStatusInterface::FIELD_STATUS,
                ['in' => [QuoteStatusInterface::STATUS_NEW, QuoteStatusInterface::STATUS_PENDING]]
            )
            ->addFieldToFilter(
                QuoteStatusInterface::FIELD_CHECKOUT_TOKEN,
                ['notnull' => true]
            );

        return $collection;
    }
}
