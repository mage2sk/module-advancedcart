<?php
declare(strict_types=1);

namespace Panth\AdvancedCart\Test\Unit\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Panth\AdvancedCart\Helper\Data;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    /**
     * @param array $values config path => value
     * @param array $flags config path => bool
     * @return Data
     */
    private function helper(array $values = [], array $flags = []): Data
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            static fn(string $path) => $values[$path] ?? null
        );
        $scopeConfig->method('isSetFlag')->willReturnCallback(
            static fn(string $path) => (bool)($flags[$path] ?? false)
        );
        $urlBuilder = $this->createStub(UrlInterface::class);
        $urlBuilder->method('getUrl')->willReturnCallback(
            static fn(string $route, array $params = []) => 'https://store.test/' . ($params['_direct'] ?? $route)
        );
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);
        $context->method('getUrlBuilder')->willReturn($urlBuilder);

        return new Data($context);
    }

    public function testIsEnabledReadsGeneralFlag(): void
    {
        $this->assertTrue($this->helper([], ['panth_advancedcart/general/enabled' => true])->isEnabled());
        $this->assertFalse($this->helper()->isEnabled());
    }

    public function testIsEnabledUsesStoreScope(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with('panth_advancedcart/general/enabled', ScopeInterface::SCOPE_STORE)
            ->willReturn(true);
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);

        $this->assertTrue((new Data($context))->isEnabled());
    }

    public function testGetConfigPrefixesPathAndPassesStoreId(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('getValue')
            ->with('panth_advancedcart/some/path', ScopeInterface::SCOPE_STORE, 3)
            ->willReturn('value');
        $context = $this->createStub(Context::class);
        $context->method('getScopeConfig')->willReturn($scopeConfig);

        $this->assertSame('value', (new Data($context))->getConfig('some/path', 3));
    }

    public function testFeatureIsDisabledWhenModuleIsDisabled(): void
    {
        $helper = $this->helper([], ['panth_advancedcart/order_notes/enabled' => true]);

        $this->assertFalse($helper->isFeatureEnabled('order_notes'));
    }

    public function testFeatureFollowsItsOwnFlagWhenModuleIsEnabled(): void
    {
        $helper = $this->helper([], [
            'panth_advancedcart/general/enabled' => true,
            'panth_advancedcart/order_notes/enabled' => true,
        ]);

        $this->assertTrue($helper->isFeatureEnabled('order_notes'));
        $this->assertFalse($helper->isFeatureEnabled('trust_badges'));
    }

    public static function thresholdProvider(): array
    {
        return [
            'not configured' => [null, 50.0],
            'empty string' => ['', 50.0],
            'not numeric' => ['abc', 50.0],
            'zero' => ['0', 0.0],
            'decimal' => ['75.5', 75.5],
        ];
    }

    #[DataProvider('thresholdProvider')]
    public function testFreeShippingThreshold(?string $value, float $expected): void
    {
        $helper = $this->helper(['panth_advancedcart/free_shipping_bar/threshold' => $value]);

        $this->assertSame($expected, $helper->getFreeShippingThreshold());
    }

    public static function deliveryDaysProvider(): array
    {
        return [
            'not configured' => [null, null, 3, 7],
            'not numeric' => ['x', 'y', 3, 7],
            'negative clamps to zero' => ['-2', '-1', 0, 0],
            'configured' => ['1', '4', 1, 4],
            'decimal truncated' => ['2.9', '5.2', 2, 5],
        ];
    }

    #[DataProvider('deliveryDaysProvider')]
    public function testDeliveryDays(?string $min, ?string $max, int $expectedMin, int $expectedMax): void
    {
        $helper = $this->helper([
            'panth_advancedcart/estimated_delivery/min_days' => $min,
            'panth_advancedcart/estimated_delivery/max_days' => $max,
        ]);

        $this->assertSame($expectedMin, $helper->getDeliveryMinDays());
        $this->assertSame($expectedMax, $helper->getDeliveryMaxDays());
    }

    public function testTrustBadgesDefaultList(): void
    {
        $this->assertSame(
            ['secure_checkout', 'money_back', 'free_returns'],
            $this->helper()->getTrustBadges()
        );
    }

    public function testTrustBadgesAreTrimmed(): void
    {
        $helper = $this->helper(['panth_advancedcart/trust_badges/badges' => ' fast_shipping , support_24_7 ']);

        $this->assertSame(['fast_shipping', 'support_24_7'], $helper->getTrustBadges());
    }

    public function testOrderNotesMaxLengthDefaultsAndCasts(): void
    {
        $this->assertSame(500, $this->helper()->getOrderNotesMaxLength());
        $this->assertSame(
            500,
            $this->helper(['panth_advancedcart/order_notes/max_length' => ''])->getOrderNotesMaxLength()
        );
        $this->assertSame(
            120,
            $this->helper(['panth_advancedcart/order_notes/max_length' => '120'])->getOrderNotesMaxLength()
        );
    }

    public function testTextDefaultsWhenNothingIsConfigured(): void
    {
        $helper = $this->helper();

        $this->assertStringContainsString('{{remaining}}', $helper->getFreeShippingProgressMessage());
        $this->assertStringContainsString('FREE shipping', $helper->getFreeShippingAchievedMessage());
        $this->assertSame('Continue Shopping', $helper->getContinueShoppingLabel());
        $this->assertSame('https://store.test/', $helper->getContinueShoppingUrl());
        $this->assertSame('Estimated Delivery', $helper->getDeliveryLabel());
        $this->assertSame('Add special instructions for your order...', $helper->getOrderNotesPlaceholder());
        $this->assertSame('Your cart is empty', $helper->getEmptyCartHeading());
        $this->assertStringStartsWith('Looks like you haven', $helper->getEmptyCartMessage());
        $this->assertSame('Start Shopping', $helper->getEmptyCartButtonLabel());
    }

    public function testConfiguredTextOverridesDefaults(): void
    {
        $p = 'panth_advancedcart/';
        $helper = $this->helper([
            $p . 'free_shipping_bar/message_progress' => 'P {{remaining}}',
            $p . 'free_shipping_bar/message_achieved' => 'Done',
            $p . 'continue_shopping/label' => 'Back',
            $p . 'continue_shopping/url' => '/sale',
            $p . 'estimated_delivery/label' => 'Arrives',
            $p . 'order_notes/placeholder' => 'Notes',
            $p . 'empty_cart/heading' => 'Empty',
            $p . 'empty_cart/message' => 'Nothing here',
            $p . 'empty_cart/button_label' => 'Shop',
        ]);

        $this->assertSame('P {{remaining}}', $helper->getFreeShippingProgressMessage());
        $this->assertSame('Done', $helper->getFreeShippingAchievedMessage());
        $this->assertSame('Back', $helper->getContinueShoppingLabel());
        $this->assertSame('https://store.test/sale', $helper->getContinueShoppingUrl());
        $this->assertSame('Arrives', $helper->getDeliveryLabel());
        $this->assertSame('Notes', $helper->getOrderNotesPlaceholder());
        $this->assertSame('Empty', $helper->getEmptyCartHeading());
        $this->assertSame('Nothing here', $helper->getEmptyCartMessage());
        $this->assertSame('Shop', $helper->getEmptyCartButtonLabel());
    }

    public static function continueShoppingUrlProvider(): array
    {
        return [
            'empty goes to home page' => [null, 'https://store.test/'],
            'root slash' => ['/', 'https://store.test/'],
            'path with leading slash' => ['/sale.html', 'https://store.test/sale.html'],
            'path without leading slash stays on store root' => ['sale.html', 'https://store.test/sale.html'],
            'surrounding spaces are trimmed' => ['  women/tops.html ', 'https://store.test/women/tops.html'],
            'absolute https url is kept' => ['https://example.com/shop', 'https://example.com/shop'],
            'absolute url scheme is case insensitive' => ['HTTP://example.com/shop', 'HTTP://example.com/shop'],
            'protocol relative url is kept' => ['//cdn.example.com/x', '//cdn.example.com/x'],
        ];
    }

    #[DataProvider('continueShoppingUrlProvider')]
    public function testContinueShoppingUrlResolvesAgainstStoreBaseUrl(?string $value, string $expected): void
    {
        $helper = $this->helper(['panth_advancedcart/continue_shopping/url' => $value]);

        $this->assertSame($expected, $helper->getContinueShoppingUrl());
    }
}
