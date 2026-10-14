<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\InstantNavigation\Plugin;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\Page;
use Zero\InstantNavigation\Model\Rules;

/**
 * Full HTML pages carry `Speculation-Rules: "<rules URL>"` (an HTTP header,
 * not an inline <script type="speculationrules">: the storefront's CSP
 * allows no inline scripts). Set before the page renders: the built-in page
 * cache stores the response in its own after-plugin on renderResult, so
 * cached copies carry the header too. HTMX fragments don't get it.
 */
class AddSpeculationRulesHeader
{
    public function __construct(
        private readonly Rules $rules,
        private readonly RequestInterface $request
    ) {
    }

    /**
     * @param Page $subject
     * @param ResponseInterface $response
     * @return null
     */
    public function beforeRenderResult(Page $subject, ResponseInterface $response)
    {
        if ($response instanceof HttpResponse
            && $this->rules->isEnabled()
            && !$this->request->getHeader('HX-Request')
        ) {
            $response->setHeader('Speculation-Rules', '"' . $this->rules->url() . '"', true);
        }

        return null;
    }
}
