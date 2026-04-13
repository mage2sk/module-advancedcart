<?php
declare(strict_types=1);

namespace Panth\AdvancedCart\Block\Adminhtml\Order;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;

class OrderNote extends Template
{
    private Registry $registry;

    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        return $this->registry->registry('current_order');
    }

    public function getOrderNote(): string
    {
        $order = $this->getOrder();
        return $order ? (string)$order->getData('panth_order_note') : '';
    }

    public function hasOrderNote(): bool
    {
        return $this->getOrderNote() !== '';
    }
}
