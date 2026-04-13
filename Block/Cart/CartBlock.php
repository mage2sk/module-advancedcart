<?php
declare(strict_types=1);

namespace Panth\AdvancedCart\Block\Cart;

use Magento\Framework\View\Element\Template;
use Panth\Core\Helper\Theme as ThemeHelper;

/**
 * Generic cart block that automatically switches templates between Hyva and Luma.
 *
 * Hyva templates: Panth_AdvancedCart::cart/xxx.phtml
 * Luma templates: Panth_AdvancedCart::cart/luma/xxx.phtml
 *
 * Set the Hyva template path in layout XML. This block auto-prefixes with luma/ for Luma theme.
 */
class CartBlock extends Template
{
    private ThemeHelper $themeHelper;

    public function __construct(
        Template\Context $context,
        ThemeHelper $themeHelper,
        array $data = []
    ) {
        $this->themeHelper = $themeHelper;
        parent::__construct($context, $data);
    }

    public function getTemplate()
    {
        $template = parent::getTemplate();

        if (!$this->themeHelper->isHyva() && $template) {
            // Convert Panth_AdvancedCart::cart/xxx.phtml to Panth_AdvancedCart::cart/luma/xxx.phtml
            $template = str_replace('::cart/', '::cart/luma/', $template);
        }

        return $template;
    }
}
