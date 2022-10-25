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
namespace Vipps\Checkout\Block\Adminhtml\Order\View\Tab;

use Vipps\Checkout\Gateway\Data\PaymentEventLog;
use Vipps\Checkout\Model\Method\Vipps;
use Vipps\Checkout\Model\PaymentEventLogProvider;

/**
 * Payment Event Log order tab
 *
 * @api
 * @since 100.0.2
 */
class EventLog extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'Vipps_Checkout::order/view/tab/event_log.phtml';
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
    /**
     * @var \Magento\Sales\Helper\Admin
     */
    private $adminHelper;
    /**
     * @var PaymentEventLogProvider
     */
    private $eventLogProvider;

    /**
     * EventLog constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param PaymentEventLogProvider $eventLogProvider
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        PaymentEventLogProvider $eventLogProvider,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
        $this->adminHelper = $adminHelper;
        $this->eventLogProvider = $eventLogProvider;
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->_coreRegistry->registry('current_order');
    }

    /**
     * @inheritdoc
     */
    public function getTabLabel()
    {
        return __('Vipps Payment Event Log');
    }

    /**
     * @inheritdoc
     */
    public function getTabTitle()
    {
        return __('Payment Event Log');
    }

    /**
     * Get Tab Class
     *
     * @return string
     */
    public function getTabClass()
    {
        return 'ajax only';
    }

    /**
     * Get Class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->getTabClass();
    }

    /**
     * Get Tab Url
     *
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl('vipps/order/eventLog', ['_current' => true]);
    }

    /**
     * @inheritdoc
     */
    public function canShowTab()
    {
        return $this->getOrder()->getPayment()->getMethod() === Vipps::METHOD_CODE;
    }

    /**
     * @inheritdoc
     */
    public function isHidden()
    {
        return false;
    }

    public function getEventLog(): PaymentEventLog
    {
        return $this->eventLogProvider->get($this->getOrder()->getIncrementId());
    }
}
