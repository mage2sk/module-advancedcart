<?php
declare(strict_types=1);

namespace Panth\AdvancedCart\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Panth\AdvancedCart\Helper\Data as AdvancedCartHelper;
use Panth\Core\Helper\Theme as ThemeHelper;

class CartEnhancements implements ArgumentInterface
{
    private AdvancedCartHelper $helper;
    private CheckoutSession $checkoutSession;
    private PriceCurrencyInterface $priceCurrency;
    private TimezoneInterface $timezone;
    private ThemeHelper $themeHelper;

    public function __construct(
        AdvancedCartHelper $helper,
        CheckoutSession $checkoutSession,
        PriceCurrencyInterface $priceCurrency,
        TimezoneInterface $timezone,
        ThemeHelper $themeHelper
    ) {
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->priceCurrency = $priceCurrency;
        $this->timezone = $timezone;
        $this->themeHelper = $themeHelper;
    }

    public function isHyva(): bool
    {
        return $this->themeHelper->isHyva();
    }

    public function isEnabled(): bool
    {
        return $this->helper->isEnabled();
    }

    public function isFeatureEnabled(string $feature): bool
    {
        return $this->helper->isFeatureEnabled($feature);
    }

    public function getFreeShippingThreshold(): float
    {
        return $this->helper->getFreeShippingThreshold();
    }

    public function getCartSubtotal(): float
    {
        $quote = $this->checkoutSession->getQuote();
        return (float)$quote->getSubtotal();
    }

    public function getBaseCartSubtotal(): float
    {
        $quote = $this->checkoutSession->getQuote();
        $baseSubtotal = $quote->getBaseSubtotal();
        return $baseSubtotal !== null ? (float)$baseSubtotal : (float)$quote->getSubtotal();
    }

    public function getFreeShippingThresholdAmount(): float
    {
        return $this->convertToStoreCurrency($this->getFreeShippingThreshold());
    }

    public function getFreeShippingRemaining(): float
    {
        $remaining = $this->getFreeShippingThreshold() - $this->getBaseCartSubtotal();
        return $this->convertToStoreCurrency(max(0, $remaining));
    }

    public function getFreeShippingPercentage(): float
    {
        $threshold = $this->getFreeShippingThreshold();
        if ($threshold <= 0) {
            return 100;
        }
        return min(100, ($this->getBaseCartSubtotal() / $threshold) * 100);
    }

    public function hasFreeShipping(): bool
    {
        return $this->getBaseCartSubtotal() >= $this->getFreeShippingThreshold();
    }

    public function getFreeShippingMessage(): string
    {
        if ($this->hasFreeShipping()) {
            return $this->helper->getFreeShippingAchievedMessage();
        }

        $message = $this->helper->getFreeShippingProgressMessage();
        $remaining = $this->formatPrice($this->getFreeShippingRemaining());
        return str_replace('{{remaining}}', $remaining, $message);
    }

    public function formatPrice(float $amount): string
    {
        return $this->priceCurrency->format($amount, false);
    }

    public function getCartSavings(): float
    {
        return $this->convertToStoreCurrency($this->getBaseCartSavings());
    }

    public function getBaseCartSavings(): float
    {
        $quote = $this->checkoutSession->getQuote();

        $discount = abs((float)$quote->getShippingAddress()->getBaseDiscountAmount());

        if ($discount <= 0) {
            foreach ($quote->getAllVisibleItems() as $item) {
                $discount += abs((float)$item->getBaseDiscountAmount());
            }
        }

        $specialPriceSavings = 0;
        foreach ($quote->getAllVisibleItems() as $item) {
            $product = $item->getProduct();
            $regularPrice = (float)$product->getPrice();
            $basePrice = $item->getBasePrice();
            $finalPrice = $basePrice !== null ? (float)$basePrice : (float)$item->getPrice();

            if ($regularPrice > $finalPrice && $finalPrice > 0) {
                $specialPriceSavings += ($regularPrice - $finalPrice) * $item->getQty();
            }
        }

        return max($discount, $specialPriceSavings);
    }

    public function hasCartSavings(): bool
    {
        return $this->getCartSavings() > 0;
    }

    public function getFormattedCartSavings(): string
    {
        return $this->formatPrice($this->getCartSavings());
    }

    public function getTrustBadges(): array
    {
        $badges = $this->helper->getTrustBadges();
        $badgeData = [];

        $allBadges = [
            'secure_checkout' => [
                'label' => 'Secure Checkout',
                'icon' => 'lock',
            ],
            'money_back' => [
                'label' => 'Money Back Guarantee',
                'icon' => 'shield-check',
            ],
            'free_returns' => [
                'label' => 'Free Returns',
                'icon' => 'refresh-cw',
            ],
            'fast_shipping' => [
                'label' => 'Fast Shipping',
                'icon' => 'truck',
            ],
            'support_24_7' => [
                'label' => '24/7 Support',
                'icon' => 'headphones',
            ],
            'quality_guarantee' => [
                'label' => 'Quality Guarantee',
                'icon' => 'award',
            ],
        ];

        foreach ($badges as $badge) {
            if (isset($allBadges[$badge])) {
                $badgeData[$badge] = $allBadges[$badge];
            }
        }

        return $badgeData;
    }

    public function getContinueShoppingLabel(): string
    {
        return $this->helper->getContinueShoppingLabel();
    }

    public function getContinueShoppingUrl(): string
    {
        return $this->helper->getContinueShoppingUrl();
    }

    public function getEstimatedDeliveryRange(): array
    {
        $minDays = $this->helper->getDeliveryMinDays();
        $maxDays = $this->helper->getDeliveryMaxDays();
        if ($minDays > $maxDays) {
            [$minDays, $maxDays] = [$maxDays, $minDays];
        }

        $now = $this->timezone->date();

        $minDate = clone $now;
        $maxDate = clone $now;

        $daysAdded = 0;
        while ($daysAdded < $minDays) {
            $minDate->modify('+1 day');
            if ($minDate->format('N') < 6) {
                $daysAdded++;
            }
        }

        $daysAdded = 0;
        while ($daysAdded < $maxDays) {
            $maxDate->modify('+1 day');
            if ($maxDate->format('N') < 6) {
                $daysAdded++;
            }
        }

        return [
            'min_date' => $minDate->format('M j'),
            'max_date' => $maxDate->format('M j'),
            'label' => $this->helper->getDeliveryLabel(),
        ];
    }

    public function getOrderNotesPlaceholder(): string
    {
        return $this->helper->getOrderNotesPlaceholder();
    }

    public function getOrderNotesMaxLength(): int
    {
        return $this->helper->getOrderNotesMaxLength();
    }

    public function getExistingOrderNote(): string
    {
        $quote = $this->checkoutSession->getQuote();
        return (string)$quote->getData('panth_order_note');
    }

    public function getEmptyCartHeading(): string
    {
        return $this->helper->getEmptyCartHeading();
    }

    public function getEmptyCartMessage(): string
    {
        return $this->helper->getEmptyCartMessage();
    }

    public function getEmptyCartButtonLabel(): string
    {
        return $this->helper->getEmptyCartButtonLabel();
    }

    public function isQtyButtonsEnabled(): bool
    {
        return $this->helper->isFeatureEnabled('qty_buttons');
    }

    private function convertToStoreCurrency(float $amount): float
    {
        $store = $this->checkoutSession->getQuote()->getStore();
        return (float)$this->priceCurrency->convert($amount, $store);
    }
}
