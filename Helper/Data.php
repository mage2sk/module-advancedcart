<?php
declare(strict_types=1);

namespace Panth\AdvancedCart\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    private const XML_PATH_PREFIX = 'panth_advancedcart/';

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PREFIX . 'general/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getConfig(string $path, $storeId = null): ?string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_PREFIX . $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function isFeatureEnabled(string $feature): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PREFIX . $feature . '/enabled',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getFreeShippingThreshold(): float
    {
        $value = $this->getConfig('free_shipping_bar/threshold');
        return is_numeric($value) ? (float)$value : 50.0;
    }

    public function getFreeShippingProgressMessage(): string
    {
        return $this->getConfig('free_shipping_bar/message_progress')
            ?: "You're only {{remaining}} away from free shipping!";
    }

    public function getFreeShippingAchievedMessage(): string
    {
        return $this->getConfig('free_shipping_bar/message_achieved')
            ?: 'Congratulations! You\'ve earned FREE shipping!';
    }

    public function getTrustBadges(): array
    {
        $badges = $this->getConfig('trust_badges/badges') ?: 'secure_checkout,money_back,free_returns';
        return array_map('trim', explode(',', $badges));
    }

    public function getContinueShoppingLabel(): string
    {
        return $this->getConfig('continue_shopping/label') ?: 'Continue Shopping';
    }

    public function getContinueShoppingUrl(): string
    {
        $url = trim((string)$this->getConfig('continue_shopping/url'));
        if (preg_match('#^(https?:)?//#i', $url)) {
            return $url;
        }
        return $this->_urlBuilder->getUrl('', ['_direct' => ltrim($url, '/')]);
    }

    public function getDeliveryMinDays(): int
    {
        $value = $this->getConfig('estimated_delivery/min_days');
        return is_numeric($value) ? max(0, (int)$value) : 3;
    }

    public function getDeliveryMaxDays(): int
    {
        $value = $this->getConfig('estimated_delivery/max_days');
        return is_numeric($value) ? max(0, (int)$value) : 7;
    }

    public function getDeliveryLabel(): string
    {
        return $this->getConfig('estimated_delivery/label') ?: 'Estimated Delivery';
    }

    public function getOrderNotesPlaceholder(): string
    {
        return $this->getConfig('order_notes/placeholder')
            ?: 'Add special instructions for your order...';
    }

    public function getOrderNotesMaxLength(): int
    {
        return (int)($this->getConfig('order_notes/max_length') ?: 500);
    }

    public function getEmptyCartHeading(): string
    {
        return $this->getConfig('empty_cart/heading') ?: 'Your cart is empty';
    }

    public function getEmptyCartMessage(): string
    {
        return $this->getConfig('empty_cart/message')
            ?: 'Looks like you haven\'t added anything to your cart yet. Browse our collection and find something you love!';
    }

    public function getEmptyCartButtonLabel(): string
    {
        return $this->getConfig('empty_cart/button_label') ?: 'Start Shopping';
    }
}
