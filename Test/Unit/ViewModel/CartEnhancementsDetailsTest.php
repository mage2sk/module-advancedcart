<?php
declare(strict_types=1);

namespace Panth\AdvancedCart\Test\Unit\ViewModel;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\DataObject;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Panth\AdvancedCart\Helper\Data as AdvancedCartHelper;
use Panth\AdvancedCart\ViewModel\CartEnhancements;
use Panth\Core\Helper\Theme as ThemeHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CartEnhancementsDetailsTest extends TestCase
{
    /**
     * @param DataObject $quote
     * @param array $helperValues method => return value
     * @param \DateTime|null $now
     * @param bool $hyva
     * @return CartEnhancements
     */
    private function viewModel(
        DataObject $quote,
        array $helperValues = [],
        ?\DateTime $now = null,
        bool $hyva = false
    ): CartEnhancements {
        $helper = $this->createStub(AdvancedCartHelper::class);
        foreach ($helperValues as $method => $value) {
            $helper->method($method)->willReturn($value);
        }

        $session = $this->createStub(CheckoutSession::class);
        $session->method('getQuote')->willReturn($quote);

        $priceCurrency = $this->createStub(PriceCurrencyInterface::class);
        $priceCurrency->method('convert')->willReturnCallback(static fn($amount) => $amount);
        $priceCurrency->method('format')->willReturnCallback(
            static fn($amount) => '$' . number_format((float)$amount, 2)
        );

        $timezone = $this->createStub(TimezoneInterface::class);
        $timezone->method('date')->willReturn($now ?? new \DateTime('2026-10-02 10:00:00'));

        $theme = $this->createStub(ThemeHelper::class);
        $theme->method('isHyva')->willReturn($hyva);

        return new CartEnhancements($helper, $session, $priceCurrency, $timezone, $theme);
    }

    private function quote(array $data = []): DataObject
    {
        return new DataObject($data + [
            'subtotal' => 0.0,
            'base_subtotal' => 0.0,
            'store' => new DataObject(),
            'shipping_address' => new DataObject(['base_discount_amount' => 0.0]),
            'all_visible_items' => [],
        ]);
    }

    private function item(float $regular, ?float $basePrice, float $price, float $qty, float $discount = 0.0): DataObject
    {
        return new DataObject([
            'product' => new DataObject(['price' => $regular]),
            'base_price' => $basePrice,
            'price' => $price,
            'qty' => $qty,
            'base_discount_amount' => $discount,
        ]);
    }

    public function testDelegatesFlagsToHelperAndTheme(): void
    {
        $viewModel = $this->viewModel($this->quote(), ['isEnabled' => true], null, true);

        $this->assertTrue($viewModel->isHyva());
        $this->assertTrue($viewModel->isEnabled());
        $this->assertFalse($this->viewModel($this->quote())->isHyva());
    }

    public function testQtyButtonsAskForQtyButtonsFeature(): void
    {
        $helper = $this->createMock(AdvancedCartHelper::class);
        $helper->expects($this->exactly(2))
            ->method('isFeatureEnabled')
            ->willReturnCallback(static fn(string $feature) => $feature === 'qty_buttons');
        $viewModel = new CartEnhancements(
            $helper,
            $this->createStub(CheckoutSession::class),
            $this->createStub(PriceCurrencyInterface::class),
            $this->createStub(TimezoneInterface::class),
            $this->createStub(ThemeHelper::class)
        );

        $this->assertTrue($viewModel->isQtyButtonsEnabled());
        $this->assertFalse($viewModel->isFeatureEnabled('trust_badges'));
    }

    public function testCartSubtotalIsCastToFloat(): void
    {
        $viewModel = $this->viewModel($this->quote(['subtotal' => '12.50']));

        $this->assertSame(12.5, $viewModel->getCartSubtotal());
    }

    public function testBaseSubtotalFallsBackToSubtotalWhenMissing(): void
    {
        $viewModel = $this->viewModel($this->quote(['subtotal' => 30.0, 'base_subtotal' => null]));

        $this->assertSame(30.0, $viewModel->getBaseCartSubtotal());
    }

    public function testZeroThresholdMeansFreeShippingAlways(): void
    {
        $viewModel = $this->viewModel($this->quote(), [
            'getFreeShippingThreshold' => 0.0,
            'getFreeShippingAchievedMessage' => 'Free!',
        ]);

        $this->assertSame(100.0, (float)$viewModel->getFreeShippingPercentage());
        $this->assertTrue($viewModel->hasFreeShipping());
        $this->assertSame('Free!', $viewModel->getFreeShippingMessage());
        $this->assertSame(0.0, $viewModel->getFreeShippingRemaining());
    }

    public function testPercentageIsCappedAtHundred(): void
    {
        $viewModel = $this->viewModel($this->quote(['base_subtotal' => 250.0]), ['getFreeShippingThreshold' => 100.0]);

        $this->assertSame(100.0, (float)$viewModel->getFreeShippingPercentage());
    }

    public function testThresholdReachedExactlyCountsAsFreeShipping(): void
    {
        $viewModel = $this->viewModel($this->quote(['base_subtotal' => 100.0]), [
            'getFreeShippingThreshold' => 100.0,
            'getFreeShippingAchievedMessage' => 'Free!',
        ]);

        $this->assertTrue($viewModel->hasFreeShipping());
        $this->assertSame('Free!', $viewModel->getFreeShippingMessage());
    }

    public function testProgressMessageReplacesEveryPlaceholder(): void
    {
        $viewModel = $this->viewModel($this->quote(['base_subtotal' => 25.0]), [
            'getFreeShippingThreshold' => 100.0,
            'getFreeShippingProgressMessage' => '{{remaining}} to go ({{remaining}})',
        ]);

        $this->assertSame('$75.00 to go ($75.00)', $viewModel->getFreeShippingMessage());
    }

    public function testFormatPriceUsesPriceCurrency(): void
    {
        $this->assertSame('$1,234.50', $this->viewModel($this->quote())->formatPrice(1234.5));
    }

    public function testItemDiscountsAreSummedWhenAddressHasNone(): void
    {
        $quote = $this->quote(['all_visible_items' => [
            $this->item(10.0, 10.0, 10.0, 1, -3.0),
            $this->item(10.0, 10.0, 10.0, 1, 2.0),
        ]]);

        $this->assertSame(5.0, $this->viewModel($quote)->getBaseCartSavings());
    }

    public function testAddressDiscountWinsOverItemDiscounts(): void
    {
        $quote = $this->quote([
            'shipping_address' => new DataObject(['base_discount_amount' => -4.0]),
            'all_visible_items' => [$this->item(10.0, 10.0, 10.0, 1, -9.0)],
        ]);

        $this->assertSame(4.0, $this->viewModel($quote)->getBaseCartSavings());
    }

    public function testSavingsTakeTheLargerOfDiscountAndSpecialPrice(): void
    {
        $quote = $this->quote([
            'shipping_address' => new DataObject(['base_discount_amount' => -3.0]),
            'all_visible_items' => [$this->item(20.0, 15.0, 15.0, 2)],
        ]);

        $this->assertSame(10.0, $this->viewModel($quote)->getBaseCartSavings());
    }

    public function testSpecialPriceFallsBackToPriceWhenBasePriceMissing(): void
    {
        $quote = $this->quote(['all_visible_items' => [$this->item(20.0, null, 18.0, 3)]]);

        $this->assertSame(6.0, $this->viewModel($quote)->getBaseCartSavings());
    }

    public function testFreeItemsAndRegularPricedItemsGiveNoSavings(): void
    {
        $quote = $this->quote(['all_visible_items' => [
            $this->item(20.0, 0.0, 0.0, 1),
            $this->item(20.0, 20.0, 20.0, 1),
            $this->item(20.0, 25.0, 25.0, 1),
        ]]);
        $viewModel = $this->viewModel($quote);

        $this->assertSame(0.0, $viewModel->getBaseCartSavings());
        $this->assertFalse($viewModel->hasCartSavings());
        $this->assertSame('$0.00', $viewModel->getFormattedCartSavings());
    }

    public function testFormattedSavings(): void
    {
        $quote = $this->quote(['all_visible_items' => [$this->item(12.0, 10.0, 10.0, 1)]]);

        $this->assertSame('$2.00', $this->viewModel($quote)->getFormattedCartSavings());
    }

    public function testTrustBadgesKeepConfiguredOrderAndDropUnknown(): void
    {
        $viewModel = $this->viewModel($this->quote(), [
            'getTrustBadges' => ['fast_shipping', 'unknown', 'secure_checkout'],
        ]);

        $badges = $viewModel->getTrustBadges();

        $this->assertSame(['fast_shipping', 'secure_checkout'], array_keys($badges));
        $this->assertSame(['label' => 'Fast Shipping', 'icon' => 'truck'], $badges['fast_shipping']);
        $this->assertSame('lock', $badges['secure_checkout']['icon']);
    }

    public function testTrustBadgesEmptyWhenNoneConfigured(): void
    {
        $this->assertSame([], $this->viewModel($this->quote(), ['getTrustBadges' => []])->getTrustBadges());
    }

    public static function deliveryProvider(): array
    {
        return [
            'friday skips weekend' => ['2026-10-02', 3, 7, 'Oct 7', 'Oct 13'],
            'saturday start' => ['2026-10-03', 1, 2, 'Oct 5', 'Oct 6'],
            'zero days is today' => ['2026-10-02', 0, 0, 'Oct 2', 'Oct 2'],
            'month rollover' => ['2026-10-29', 2, 5, 'Nov 2', 'Nov 5'],
            'min above max is normalised' => ['2026-10-02', 7, 3, 'Oct 7', 'Oct 13'],
        ];
    }

    #[DataProvider('deliveryProvider')]
    public function testEstimatedDeliveryCountsBusinessDays(
        string $today,
        int $min,
        int $max,
        string $expectedMin,
        string $expectedMax
    ): void {
        $now = new \DateTime($today . ' 09:00:00');
        $viewModel = $this->viewModel($this->quote(), [
            'getDeliveryMinDays' => $min,
            'getDeliveryMaxDays' => $max,
            'getDeliveryLabel' => 'Arrives',
        ], $now);

        $this->assertSame(
            ['min_date' => $expectedMin, 'max_date' => $expectedMax, 'label' => 'Arrives'],
            $viewModel->getEstimatedDeliveryRange()
        );
        $this->assertSame($today, $now->format('Y-m-d'));
    }

    public function testExistingOrderNoteIsStringEvenWhenMissing(): void
    {
        $this->assertSame('', $this->viewModel($this->quote())->getExistingOrderNote());
        $this->assertSame(
            'Gift',
            $this->viewModel($this->quote(['panth_order_note' => 'Gift']))->getExistingOrderNote()
        );
    }

    public function testTextGettersDelegateToHelper(): void
    {
        $viewModel = $this->viewModel($this->quote(), [
            'getFreeShippingThreshold' => 80.0,
            'getContinueShoppingLabel' => 'Back',
            'getContinueShoppingUrl' => '/sale',
            'getOrderNotesPlaceholder' => 'Notes',
            'getOrderNotesMaxLength' => 250,
            'getEmptyCartHeading' => 'Empty',
            'getEmptyCartMessage' => 'Nothing',
            'getEmptyCartButtonLabel' => 'Shop',
        ]);

        $this->assertSame(80.0, $viewModel->getFreeShippingThreshold());
        $this->assertSame('Back', $viewModel->getContinueShoppingLabel());
        $this->assertSame('/sale', $viewModel->getContinueShoppingUrl());
        $this->assertSame('Notes', $viewModel->getOrderNotesPlaceholder());
        $this->assertSame(250, $viewModel->getOrderNotesMaxLength());
        $this->assertSame('Empty', $viewModel->getEmptyCartHeading());
        $this->assertSame('Nothing', $viewModel->getEmptyCartMessage());
        $this->assertSame('Shop', $viewModel->getEmptyCartButtonLabel());
    }
}
