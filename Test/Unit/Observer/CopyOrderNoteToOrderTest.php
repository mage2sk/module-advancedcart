<?php
declare(strict_types=1);

namespace Panth\AdvancedCart\Test\Unit\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Panth\AdvancedCart\Observer\CopyOrderNoteToOrder;
use PHPUnit\Framework\TestCase;

class CopyOrderNoteToOrderTest extends TestCase
{
    private function observer(?DataObject $quote, ?DataObject $order): Observer
    {
        return new Observer(['event' => new Event(['quote' => $quote, 'order' => $order])]);
    }

    public function testCopiesNoteFromQuoteToOrder(): void
    {
        $quote = new DataObject(['panth_order_note' => 'Leave at the door']);
        $order = new DataObject();

        (new CopyOrderNoteToOrder())->execute($this->observer($quote, $order));

        $this->assertSame('Leave at the door', $order->getData('panth_order_note'));
    }

    public function testKeepsSurroundingWhitespaceOfNonEmptyNote(): void
    {
        $quote = new DataObject(['panth_order_note' => '  ring twice ']);
        $order = new DataObject();

        (new CopyOrderNoteToOrder())->execute($this->observer($quote, $order));

        $this->assertSame('  ring twice ', $order->getData('panth_order_note'));
    }

    public function testSkipsBlankNote(): void
    {
        $quote = new DataObject(['panth_order_note' => "  \n "]);
        $order = new DataObject(['panth_order_note' => 'existing']);

        (new CopyOrderNoteToOrder())->execute($this->observer($quote, $order));

        $this->assertSame('existing', $order->getData('panth_order_note'));
    }

    public function testSkipsMissingNote(): void
    {
        $order = new DataObject();

        (new CopyOrderNoteToOrder())->execute($this->observer(new DataObject(), $order));

        $this->assertFalse($order->hasData('panth_order_note'));
    }

    public function testDoesNothingWithoutOrder(): void
    {
        $quote = new DataObject(['panth_order_note' => 'note']);

        (new CopyOrderNoteToOrder())->execute($this->observer($quote, null));

        $this->assertSame(['panth_order_note' => 'note'], $quote->getData());
    }

    public function testDoesNothingWithoutQuote(): void
    {
        $order = new DataObject();

        (new CopyOrderNoteToOrder())->execute($this->observer(null, $order));

        $this->assertSame([], $order->getData());
    }
}
