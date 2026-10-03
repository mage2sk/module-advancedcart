<?php
declare(strict_types=1);

namespace Panth\AdvancedCart\Test\Unit\Block\Cart;

use Magento\Framework\View\Element\Template\Context;
use Panth\AdvancedCart\Block\Cart\CartBlock;
use Panth\Core\Helper\Theme as ThemeHelper;
use PHPUnit\Framework\TestCase;

class CartBlockTest extends TestCase
{
    private function block(bool $hyva, ?string $template): CartBlock
    {
        $theme = $this->createStub(ThemeHelper::class);
        $theme->method('isHyva')->willReturn($hyva);

        $block = new CartBlock($this->createStub(Context::class), $theme);
        if ($template !== null) {
            $block->setTemplate($template);
        }
        return $block;
    }

    public function testHyvaKeepsOriginalTemplate(): void
    {
        $this->assertSame(
            'Panth_AdvancedCart::cart/trust-badges.phtml',
            $this->block(true, 'Panth_AdvancedCart::cart/trust-badges.phtml')->getTemplate()
        );
    }

    public function testLumaSwitchesToLumaFolder(): void
    {
        $this->assertSame(
            'Panth_AdvancedCart::cart/luma/trust-badges.phtml',
            $this->block(false, 'Panth_AdvancedCart::cart/trust-badges.phtml')->getTemplate()
        );
    }

    public function testLumaLeavesTemplatesOutsideCartFolderUntouched(): void
    {
        $this->assertSame(
            'Panth_AdvancedCart::checkout/note.phtml',
            $this->block(false, 'Panth_AdvancedCart::checkout/note.phtml')->getTemplate()
        );
    }

    public function testEmptyTemplateIsReturnedAsIs(): void
    {
        $this->assertSame('', $this->block(false, '')->getTemplate());
        $this->assertNull($this->block(false, null)->getTemplate());
    }
}
