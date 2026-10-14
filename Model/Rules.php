<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\InstantNavigation\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * The store's speculation rules (https://developer.chrome.com/docs/web-platform/prerender-pages):
 * same-site links, minus the configured paths, links with a query string
 * and anything marked data-no-instant / target=_blank / download / nofollow.
 */
class Rules
{
    public const XML_PATH = 'zero_instant_navigation/general/';
    public const ROUTE = 'zero/instant/rules';
    private const MODES = ['prerender', 'prefetch'];
    private const EAGERNESS = ['moderate', 'conservative'];

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH . 'enabled', ScopeInterface::SCOPE_STORE);
    }

    /**
     * Where the rules are served: the Speculation-Rules header points here.
     */
    public function url(): string
    {
        return rtrim($this->storeManager->getStore()->getBaseUrl(), '/') . '/' . self::ROUTE . '/';
    }

    public function toArray(): array
    {
        $mode = $this->option('mode', self::MODES);
        $eagerness = $this->option('eagerness', self::EAGERNESS);
        $base = rtrim((string) parse_url($this->storeManager->getStore()->getBaseUrl(), PHP_URL_PATH), '/');

        $where = [['href_matches' => $base . '/*']];
        $excluded = array_map(static fn (string $path) => $base . $path, $this->excludedPaths());
        if ($excluded) {
            $where[] = ['not' => ['href_matches' => $excluded]];
        }
        $where[] = ['not' => ['selector_matches' => 'a[href*="?"], [data-no-instant], [data-no-instant] a, a[target="_blank"], a[download], a[rel~="nofollow"]']];

        return [$mode => [['source' => 'document', 'where' => ['and' => $where], 'eagerness' => $eagerness]]];
    }

    /**
     * Configured paths, one per line; only plain path characters and * (a bad
     * pattern would void the whole rule set in the browser).
     *
     * @return string[]
     */
    public function excludedPaths(): array
    {
        $paths = [];
        foreach (preg_split('/\R/', (string) $this->scopeConfig->getValue(self::XML_PATH . 'exclude', ScopeInterface::SCOPE_STORE)) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || !preg_match('#^/[A-Za-z0-9_\-./*]*$#', $line)) {
                continue;
            }
            $paths[] = $line;
        }

        return array_values(array_unique($paths));
    }

    private function option(string $field, array $allowed): string
    {
        $value = (string) $this->scopeConfig->getValue(self::XML_PATH . $field, ScopeInterface::SCOPE_STORE);

        return in_array($value, $allowed, true) ? $value : $allowed[0];
    }
}
