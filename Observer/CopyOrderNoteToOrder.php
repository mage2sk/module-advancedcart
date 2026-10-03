<?php
declare(strict_types=1);

namespace Panth\AdvancedCart\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CopyOrderNoteToOrder implements ObserverInterface
{
    public function execute(Observer $observer): void
    {
        $quote = $observer->getEvent()->getQuote();
        $order = $observer->getEvent()->getOrder();
        if (!$quote || !$order) {
            return;
        }

        $note = (string) $quote->getData('panth_order_note');
        if (trim($note) === '') {
            return;
        }

        $order->setData('panth_order_note', $note);
    }
}
