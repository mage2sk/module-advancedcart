<?php
declare(strict_types=1);

namespace Panth\AdvancedCart\Test\Unit\Block\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\DataObject;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;
use Panth\AdvancedCart\Block\Checkout\OrderNote;
use Panth\AdvancedCart\Helper\Data;
use PHPUnit\Framework\TestCase;

class OrderNoteTest extends TestCase
{
    private function block(array $config = [], ?CheckoutSession $session = null, bool $enabled = true): OrderNote
    {
        $helper = $this->createStub(Data::class);
        $helper->method('isFeatureEnabled')->willReturnCallback(
            static fn(string $feature) => $enabled && $feature === 'order_notes'
        );
        $helper->method('getConfig')->willReturnCallback(
            static fn(string $path) => $config[$path] ?? null
        );

        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            static fn($route = null) => 'https://shop.test/' . $route
        );
        $context = $this->createStub(Context::class);
        $context->method('getUrlBuilder')->willReturn($url);

        if ($session === null) {
            $session = $this->createStub(CheckoutSession::class);
            $session->method('getQuote')->willReturn(new DataObject());
        }

        return new OrderNote($context, $helper, $session);
    }

    public function testDefaultsWhenNothingIsConfigured(): void
    {
        $block = $this->block();

        $this->assertSame(500, $block->getMaxLength());
        $this->assertSame('Add a note to your order...', $block->getPlaceholder());
        $this->assertSame('Order Note', $block->getLabel());
    }

    public function testConfiguredValues(): void
    {
        $block = $this->block([
            'order_notes/max_length' => '200',
            'order_notes/placeholder' => 'Instructions',
            'order_notes/label' => 'Notes',
        ]);

        $this->assertSame(200, $block->getMaxLength());
        $this->assertSame('Instructions', $block->getPlaceholder());
        $this->assertSame('Notes', $block->getLabel());
    }

    public function testIsEnabledFollowsOrderNotesFeature(): void
    {
        $this->assertTrue($this->block()->isEnabled());
        $this->assertFalse($this->block([], null, false)->isEnabled());
    }

    public function testSaveUrlPointsToSaveNoteController(): void
    {
        $this->assertSame('https://shop.test/advancedcart/cart/savenote', $this->block()->getSaveUrl());
    }

    public function testCurrentNoteComesFromQuote(): void
    {
        $session = $this->createStub(CheckoutSession::class);
        $session->method('getQuote')->willReturn(new DataObject(['panth_order_note' => 'Fragile']));

        $this->assertSame('Fragile', $this->block([], $session)->getCurrentNote());
    }

    public function testCurrentNoteIsEmptyWhenQuoteHasNone(): void
    {
        $this->assertSame('', $this->block()->getCurrentNote());
    }

    public function testCurrentNoteIsEmptyWhenQuoteCannotBeLoaded(): void
    {
        $session = $this->createStub(CheckoutSession::class);
        $session->method('getQuote')->willThrowException(new \RuntimeException('no session'));

        $this->assertSame('', $this->block([], $session)->getCurrentNote());
    }
}
