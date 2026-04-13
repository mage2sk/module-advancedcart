<?php
declare(strict_types=1);

namespace Panth\AdvancedCart\Block\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Panth\AdvancedCart\Helper\Data;

class OrderNote extends Template
{
    private Data $helper;
    private CheckoutSession $checkoutSession;

    public function __construct(
        Context $context,
        Data $helper,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
    }

    public function isEnabled(): bool
    {
        return $this->helper->isFeatureEnabled('order_notes');
    }

    public function getMaxLength(): int
    {
        return (int) ($this->helper->getConfig('order_notes/max_length') ?: 500);
    }

    public function getPlaceholder(): string
    {
        return (string) ($this->helper->getConfig('order_notes/placeholder') ?: 'Add a note to your order...');
    }

    public function getLabel(): string
    {
        return (string) ($this->helper->getConfig('order_notes/label') ?: 'Order Note');
    }

    public function getSaveUrl(): string
    {
        return $this->getUrl('advancedcart/cart/savenote');
    }

    /**
     * Returns current quote's saved order note (pre-populate the textarea)
     */
    public function getCurrentNote(): string
    {
        try {
            $note = $this->checkoutSession->getQuote()->getData('panth_order_note');
            return (string) ($note ?? '');
        } catch (\Exception $e) {
            return '';
        }
    }
}
