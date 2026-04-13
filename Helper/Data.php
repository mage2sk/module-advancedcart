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

    // Free Shipping Bar
    public function getFreeShippingThreshold(): float
    {
        return (float)($this->getConfig('free_shipping_bar/threshold') ?: 50);
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

    // Trust Badges
    public function getTrustBadges(): array
    {
        $badges = $this->getConfig('trust_badges/badges') ?: 'secure_checkout,money_back,free_returns';
        return array_map('trim', explode(',', $badges));
    }

    // Continue Shopping
    public function getContinueShoppingLabel(): string
    {
        return $this->getConfig('continue_shopping/label') ?: 'Continue Shopping';
    }

    public function getContinueShoppingUrl(): string
    {
        return $this->getConfig('continue_shopping/url') ?: '/';
    }

    // Estimated Delivery
    public function getDeliveryMinDays(): int
    {
        return (int)($this->getConfig('estimated_delivery/min_days') ?: 3);
    }

    public function getDeliveryMaxDays(): int
    {
        return (int)($this->getConfig('estimated_delivery/max_days') ?: 7);
    }

    public function getDeliveryLabel(): string
    {
        return $this->getConfig('estimated_delivery/label') ?: 'Estimated Delivery';
    }

    // Order Notes
    public function getOrderNotesPlaceholder(): string
    {
        return $this->getConfig('order_notes/placeholder')
            ?: 'Add special instructions for your order...';
    }

    public function getOrderNotesMaxLength(): int
    {
        return (int)($this->getConfig('order_notes/max_length') ?: 500);
    }

    // Empty Cart
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
