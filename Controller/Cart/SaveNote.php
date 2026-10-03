<?php
declare(strict_types=1);

namespace Panth\AdvancedCart\Controller\Cart;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\CartRepositoryInterface;
use Panth\AdvancedCart\Helper\Data as AdvancedCartHelper;
use Psr\Log\LoggerInterface;

class SaveNote implements HttpPostActionInterface
{
    private RequestInterface $request;
    private JsonFactory $jsonFactory;
    private CheckoutSession $checkoutSession;
    private CartRepositoryInterface $cartRepository;
    private AdvancedCartHelper $helper;
    private LoggerInterface $logger;
    private FormKeyValidator $formKeyValidator;

    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonFactory,
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $cartRepository,
        AdvancedCartHelper $helper,
        LoggerInterface $logger,
        FormKeyValidator $formKeyValidator
    ) {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->cartRepository = $cartRepository;
        $this->helper = $helper;
        $this->logger = $logger;
        $this->formKeyValidator = $formKeyValidator;
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        if (!$this->helper->isFeatureEnabled('order_notes')) {
            return $result->setData(['success' => false, 'message' => 'Feature disabled']);
        }

        if (!$this->formKeyValidator->validate($this->request)) {
            return $result->setData(['success' => false, 'message' => 'Invalid form key']);
        }

        try {
            $note = $this->request->getParam('note', '');
            $maxLength = $this->helper->getOrderNotesMaxLength();

            $note = strip_tags((string)$note);
            if (mb_strlen($note, 'UTF-8') > $maxLength) {
                $note = mb_substr($note, 0, $maxLength, 'UTF-8');
            }

            $quote = $this->checkoutSession->getQuote();
            if (!$quote->getId() || !$quote->getIsActive()) {
                return $result->setData(['success' => false, 'message' => 'Could not save note']);
            }
            $quote->setData('panth_order_note', $note);
            $this->cartRepository->save($quote);

            return $result->setData(['success' => true]);
        } catch (\Exception $e) {
            $this->logger->error('Panth AdvancedCart SaveNote error: ' . $e->getMessage());
            return $result->setData(['success' => false, 'message' => 'Could not save note']);
        }
    }
}
