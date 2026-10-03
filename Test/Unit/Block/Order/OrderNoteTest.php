<?php
declare(strict_types=1);

namespace Panth\AdvancedCart\Test\Unit\Block\Order;

use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Panth\AdvancedCart\Block\Order\OrderNote;
use Panth\AdvancedCart\Helper\Data;
use PHPUnit\Framework\TestCase;

class OrderNoteTest extends TestCase
{
    private function block(?DataObject $order, bool $enabled = true): OrderNote
    {
        $registry = $this->createStub(Registry::class);
        $registry->method('registry')->willReturnCallback(
            static fn(string $key) => $key === 'current_order' ? $order : null
        );
        $helper = $this->createStub(Data::class);
        $helper->method('isFeatureEnabled')->willReturnCallback(
            static fn(string $feature) => $enabled && $feature === 'order_notes'
        );

        return new OrderNote($this->createStub(Context::class), $registry, $helper);
    }

    public function testReturnsNoteOfCurrentOrder(): void
    {
        $block = $this->block(new DataObject(['panth_order_note' => 'Call first']));

        $this->assertTrue($block->isEnabled());
        $this->assertSame('Call first', $block->getOrderNote());
        $this->assertTrue($block->hasOrderNote());
    }

    public function testNoOrderMeansNoNote(): void
    {
        $block = $this->block(null);

        $this->assertSame('', $block->getOrderNote());
        $this->assertFalse($block->hasOrderNote());
    }

    public function testOrderWithoutNote(): void
    {
        $this->assertFalse($this->block(new DataObject())->hasOrderNote());
    }

    public function testNoteIsHiddenWhenFeatureIsDisabled(): void
    {
        $block = $this->block(new DataObject(['panth_order_note' => 'Call first']), false);

        $this->assertFalse($block->isEnabled());
        $this->assertSame('Call first', $block->getOrderNote());
        $this->assertFalse($block->hasOrderNote());
    }
}
