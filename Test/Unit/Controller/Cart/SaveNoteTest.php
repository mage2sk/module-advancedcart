<?php
declare(strict_types=1);

namespace Panth\AdvancedCart\Test\Unit\Controller\Cart;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;
use Panth\AdvancedCart\Controller\Cart\SaveNote;
use Panth\AdvancedCart\Helper\Data as AdvancedCartHelper;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SaveNoteTest extends TestCase
{
    /**
     * @var array|null
     */
    private ?array $responseData = null;

    /**
     * @var array
     */
    private array $quoteData = [];

    private function jsonFactory(): JsonFactory
    {
        $json = $this->createStub(Json::class);
        $json->method('setData')->willReturnCallback(function ($data) use ($json) {
            $this->responseData = $data;
            return $json;
        });
        $factory = $this->createStub(JsonFactory::class);
        $factory->method('create')->willReturn($json);
        return $factory;
    }

    private function quote(?int $id = 5, bool $active = true): Quote
    {
        $quote = $this->createStub(Quote::class);
        $quote->method('getId')->willReturn($id);
        $quote->method('getIsActive')->willReturn($active);
        $quote->method('setData')->willReturnCallback(function ($key, $value = null) use ($quote) {
            $this->quoteData[$key] = $value;
            return $quote;
        });
        return $quote;
    }

    private function controller(
        array $params = [],
        bool $featureEnabled = true,
        bool $formKeyValid = true,
        ?Quote $quote = null,
        ?CartRepositoryInterface $repository = null,
        ?LoggerInterface $logger = null,
        int $maxLength = 500
    ): SaveNote {
        $request = $this->createStub(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(
            static fn($key, $default = null) => $params[$key] ?? $default
        );

        $helper = $this->createStub(AdvancedCartHelper::class);
        $helper->method('isFeatureEnabled')->willReturnCallback(
            static fn(string $feature) => $featureEnabled && $feature === 'order_notes'
        );
        $helper->method('getOrderNotesMaxLength')->willReturn($maxLength);

        $validator = $this->createStub(FormKeyValidator::class);
        $validator->method('validate')->willReturn($formKeyValid);

        $session = $this->createStub(CheckoutSession::class);
        $session->method('getQuote')->willReturn($quote ?? $this->quote());

        return new SaveNote(
            $request,
            $this->jsonFactory(),
            $session,
            $repository ?? $this->createStub(CartRepositoryInterface::class),
            $helper,
            $logger ?? $this->createStub(LoggerInterface::class),
            $validator
        );
    }

    public function testRejectsWhenFeatureIsDisabled(): void
    {
        $repository = $this->createMock(CartRepositoryInterface::class);
        $repository->expects($this->never())->method('save');

        $this->controller(['note' => 'x'], false, true, null, $repository)->execute();

        $this->assertSame(['success' => false, 'message' => 'Feature disabled'], $this->responseData);
    }

    public function testRejectsInvalidFormKey(): void
    {
        $repository = $this->createMock(CartRepositoryInterface::class);
        $repository->expects($this->never())->method('save');

        $this->controller(['note' => 'x'], true, false, null, $repository)->execute();

        $this->assertSame(['success' => false, 'message' => 'Invalid form key'], $this->responseData);
    }

    public function testSavesSanitisedNoteOnActiveQuote(): void
    {
        $quote = $this->quote();
        $repository = $this->createMock(CartRepositoryInterface::class);
        $repository->expects($this->once())->method('save')->with($quote);

        $this->controller(['note' => '<b>Gift</b> wrap <script>x</script>please'], true, true, $quote, $repository)
            ->execute();

        $this->assertSame(['success' => true], $this->responseData);
        $this->assertSame('Gift wrap xplease', $this->quoteData['panth_order_note']);
    }

    public function testTruncatesNoteToMaxLengthCountingMultibyteCharacters(): void
    {
        $note = str_repeat("\u{00E9}", 8);

        $this->controller(['note' => $note], true, true, null, null, null, 5)->execute();

        $this->assertSame(['success' => true], $this->responseData);
        $this->assertSame(str_repeat("\u{00E9}", 5), $this->quoteData['panth_order_note']);
    }

    public function testMissingNoteClearsTheStoredNote(): void
    {
        $this->controller([])->execute();

        $this->assertSame(['success' => true], $this->responseData);
        $this->assertSame('', $this->quoteData['panth_order_note']);
    }

    public function testRefusesQuoteWithoutId(): void
    {
        $repository = $this->createMock(CartRepositoryInterface::class);
        $repository->expects($this->never())->method('save');

        $this->controller(['note' => 'x'], true, true, $this->quote(null), $repository)->execute();

        $this->assertSame(['success' => false, 'message' => 'Could not save note'], $this->responseData);
        $this->assertArrayNotHasKey('panth_order_note', $this->quoteData);
    }

    public function testRefusesInactiveQuote(): void
    {
        $repository = $this->createMock(CartRepositoryInterface::class);
        $repository->expects($this->never())->method('save');

        $this->controller(['note' => 'x'], true, true, $this->quote(9, false), $repository)->execute();

        $this->assertSame(['success' => false, 'message' => 'Could not save note'], $this->responseData);
    }

    public function testRepositoryFailureIsLoggedAndReported(): void
    {
        $repository = $this->createStub(CartRepositoryInterface::class);
        $repository->method('save')->willThrowException(new \RuntimeException('db down'));
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('db down'));

        $this->controller(['note' => 'x'], true, true, null, $repository, $logger)->execute();

        $this->assertSame(['success' => false, 'message' => 'Could not save note'], $this->responseData);
    }
}
