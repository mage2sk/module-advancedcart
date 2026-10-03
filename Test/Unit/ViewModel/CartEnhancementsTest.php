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
use PHPUnit\Framework\TestCase;

class CartEnhancementsTest extends TestCase
{
    private const RATE = 0.5;

    private function createViewModel(DataObject $quote, float $threshold = 50.0): CartEnhancements
    {
        $helper = $this->createStub(AdvancedCartHelper::class);
        $helper->method('getFreeShippingThreshold')->willReturn($threshold);
        $helper->method('getFreeShippingProgressMessage')->willReturn('Only {{remaining}} left');

        $session = $this->createStub(CheckoutSession::class);
        $session->method('getQuote')->willReturn($quote);

        $priceCurrency = $this->createStub(PriceCurrencyInterface::class);
        $priceCurrency->method('convert')->willReturnCallback(
            static function ($amount) {
                return $amount * self::RATE;
            }
        );
        $priceCurrency->method('format')->willReturnCallback(
            static function ($amount) {
                return 'EUR' . number_format((float)$amount, 2);
            }
        );

        return new CartEnhancements(
            $helper,
            $session,
            $priceCurrency,
            $this->createStub(TimezoneInterface::class),
            $this->createStub(ThemeHelper::class)
        );
    }

    private function createQuote(float $baseSubtotal, array $items = [], float $baseDiscount = 0.0): DataObject
    {
        return new DataObject([
            'subtotal' => $baseSubtotal * self::RATE,
            'base_subtotal' => $baseSubtotal,
            'store' => new DataObject(['id' => 1]),
            'shipping_address' => new DataObject(['base_discount_amount' => $baseDiscount]),
            'all_visible_items' => $items,
        ]);
    }

    public function testFreeShippingComparesBaseSubtotalWithBaseThreshold(): void
    {
        $viewModel = $this->createViewModel($this->createQuote(40.0));

        $this->assertFalse($viewModel->hasFreeShipping());
        $this->assertEqualsWithDelta(80.0, $viewModel->getFreeShippingPercentage(), 0.001);
        $this->assertEqualsWithDelta(5.0, $viewModel->getFreeShippingRemaining(), 0.001);
        $this->assertEqualsWithDelta(25.0, $viewModel->getFreeShippingThresholdAmount(), 0.001);
        $this->assertSame('Only EUR5.00 left', $viewModel->getFreeShippingMessage());
    }

    public function testFreeShippingReachedInBaseCurrency(): void
    {
        $viewModel = $this->createViewModel($this->createQuote(60.0));

        $this->assertTrue($viewModel->hasFreeShipping());
        $this->assertEqualsWithDelta(0.0, $viewModel->getFreeShippingRemaining(), 0.001);
    }

    public function testSavingsAreConvertedToStoreCurrency(): void
    {
        $item = new DataObject([
            'product' => new DataObject(['price' => 30.0]),
            'base_price' => 20.0,
            'price' => 20.0,
            'qty' => 2,
            'base_discount_amount' => 0.0,
        ]);
        $viewModel = $this->createViewModel($this->createQuote(40.0, [$item]));

        $this->assertEqualsWithDelta(20.0, $viewModel->getBaseCartSavings(), 0.001);
        $this->assertEqualsWithDelta(10.0, $viewModel->getCartSavings(), 0.001);
        $this->assertTrue($viewModel->hasCartSavings());
    }

    public function testDiscountSavingsUseBaseAmounts(): void
    {
        $viewModel = $this->createViewModel($this->createQuote(40.0, [], -12.0));

        $this->assertEqualsWithDelta(12.0, $viewModel->getBaseCartSavings(), 0.001);
        $this->assertEqualsWithDelta(6.0, $viewModel->getCartSavings(), 0.001);
    }
}
