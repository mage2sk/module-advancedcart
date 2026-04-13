<?php
declare(strict_types=1);

namespace Panth\AdvancedCart\Block\Order;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Panth\AdvancedCart\Helper\Data;

class OrderNote extends Template
{
    private Registry $registry;
    private Data $helper;

    public function __construct(
        Context $context,
        Registry $registry,
        Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->helper = $helper;
    }

    public function isEnabled(): bool
    {
        return $this->helper->isFeatureEnabled('order_notes');
    }

    public function getOrderNote(): string
    {
        $order = $this->registry->registry('current_order');
        return $order ? (string) $order->getData('panth_order_note') : '';
    }

    public function hasOrderNote(): bool
    {
        return $this->isEnabled() && $this->getOrderNote() !== '';
    }
}
