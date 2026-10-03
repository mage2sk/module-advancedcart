<?php
declare(strict_types=1);

namespace Panth\AdvancedCart\Test\Unit\Block\Adminhtml\Order;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Panth\AdvancedCart\Block\Adminhtml\Order\OrderNote;
use PHPUnit\Framework\TestCase;

class OrderNoteTest extends TestCase
{
    /**
     * @var ObjectManagerInterface|null
     */
    private ?ObjectManagerInterface $previousObjectManager = null;

    protected function setUp(): void
    {
        try {
            $this->previousObjectManager = ObjectManager::getInstance();
        } catch (\RuntimeException $e) {
            $this->previousObjectManager = null;
        }
        ObjectManager::setInstance($this->createStub(ObjectManagerInterface::class));
    }

    protected function tearDown(): void
    {
        if ($this->previousObjectManager !== null) {
            ObjectManager::setInstance($this->previousObjectManager);
            return;
        }
        (new \ReflectionProperty(ObjectManager::class, '_instance'))->setValue(null, null);
    }

    private function block(?DataObject $order): OrderNote
    {
        $registry = $this->createStub(Registry::class);
        $registry->method('registry')->willReturnCallback(
            static fn(string $key) => $key === 'current_order' ? $order : null
        );

        return new OrderNote($this->createStub(Context::class), $registry);
    }

    public function testReturnsCurrentOrderAndItsNote(): void
    {
        $order = new DataObject(['panth_order_note' => 'Deliver after 5pm']);
        $block = $this->block($order);

        $this->assertSame($order, $block->getOrder());
        $this->assertSame('Deliver after 5pm', $block->getOrderNote());
        $this->assertTrue($block->hasOrderNote());
    }

    public function testNoOrderMeansNoNote(): void
    {
        $block = $this->block(null);

        $this->assertNull($block->getOrder());
        $this->assertSame('', $block->getOrderNote());
        $this->assertFalse($block->hasOrderNote());
    }

    public function testOrderWithoutNote(): void
    {
        $this->assertFalse($this->block(new DataObject())->hasOrderNote());
    }
}
