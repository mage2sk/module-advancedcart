<?php
declare(strict_types=1);

namespace Panth\AdvancedCart\Block\Cart;

use Magento\Framework\View\Element\Template;
use Panth\Core\Helper\Theme as ThemeHelper;

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
            $template = str_replace('::cart/', '::cart/luma/', $template);
        }

        return $template;
    }
}
