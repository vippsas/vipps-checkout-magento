<?php

declare(strict_types=1);

namespace Vipps\Checkout\Plugin;

use Magento\Checkout\Block\Checkout\LayoutProcessor;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\ScopeInterface;
class CheckoutLayout
{
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected RequestInterface $request
    ) {
    }

    public function afterProcess(
        LayoutProcessor $subject,
        array $jsLayout
    ) {

        // Check if current page is Vipps Checkout
        $moduleName      = $this->request->getModuleName();
        $controllerName  = $this->request->getControllerName();

        if ($moduleName !== 'checkout' || $controllerName !== 'vipps') {
            return $jsLayout;
        }

        // Check discount display configuration
        $discount = $this->scopeConfig->isSetFlag(
            'payment/vipps_v2/display_discount',
            ScopeInterface::SCOPE_STORE
        );

        if ($discount) {
            return $jsLayout;
        }

        // Remove discount component from checkout layout if disabled
        unset($jsLayout['components']['checkout']['children']['sidebar']['children']['summary']['children']['discount']);
        return $jsLayout;
    }
}
