<?php
declare(strict_types=1);

namespace Panth\AdvancedCart\Plugin;

use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Psr\Log\LoggerInterface;

class QuoteToOrderPlugin
{
    private OrderRepositoryInterface $orderRepository;
    private CartRepositoryInterface $cartRepository;
    private LoggerInterface $logger;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        CartRepositoryInterface $cartRepository,
        LoggerInterface $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->cartRepository = $cartRepository;
        $this->logger = $logger;
    }

    /**
     * Copy panth_order_note from quote to order after order is placed
     *
     * afterPlaceOrder receives: ($subject, $result, ...$args)
     * where $result is the order ID and $args[0] is the cart ID
     */
    public function afterPlaceOrder(
        QuoteManagement $subject,
        $orderId,
        $cartId
    ) {
        if (!$orderId) {
            return $orderId;
        }

        try {
            $quote = $this->cartRepository->get($cartId);
            $orderNote = $quote->getData('panth_order_note');

            if ($orderNote) {
                $order = $this->orderRepository->get($orderId);
                $order->setData('panth_order_note', $orderNote);
                $this->orderRepository->save($order);
            }
        } catch (\Exception $e) {
            $this->logger->error('Panth AdvancedCart: Failed to copy order note - ' . $e->getMessage());
        }

        return $orderId;
    }
}
