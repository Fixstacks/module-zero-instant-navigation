<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\InstantNavigation\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Zero\InstantNavigation\Model\Rules;

class RulesTest extends TestCase
{
    private function rules(array $config, string $baseUrl = 'https://shop.example/'): Rules
    {
        $scope = $this->createStub(ScopeConfigInterface::class);
        $scope->method('getValue')->willReturnCallback(fn ($path) => $config[str_replace(Rules::XML_PATH, '', $path)] ?? null);
        $scope->method('isSetFlag')->willReturnCallback(fn ($path) => !empty($config[str_replace(Rules::XML_PATH, '', $path)]));
        $store = $this->createStub(Store::class);
        $store->method('getBaseUrl')->willReturn($baseUrl);
        $manager = $this->createStub(StoreManagerInterface::class);
        $manager->method('getStore')->willReturn($store);

        return new Rules($scope, $manager);
    }

    public function testSameSiteLinksMinusExclusions(): void
    {
        $rules = $this->rules(['mode' => 'prerender', 'eagerness' => 'moderate', 'exclude' => "/checkout/*\n/customer/*\n"])->toArray();
        $rule = $rules['prerender'][0];

        $this->assertSame('document', $rule['source']);
        $this->assertSame('moderate', $rule['eagerness']);
        $this->assertSame(['href_matches' => '/*'], $rule['where']['and'][0]);
        $this->assertSame(['not' => ['href_matches' => ['/checkout/*', '/customer/*']]], $rule['where']['and'][1]);
        $this->assertStringContainsString('a[href*="?"]', $rule['where']['and'][2]['not']['selector_matches']);
        $this->assertStringContainsString('[data-no-instant]', $rule['where']['and'][2]['not']['selector_matches']);
    }

    public function testStoreCodeInUrlPrefixesEveryPattern(): void
    {
        $rule = $this->rules(['mode' => 'prefetch', 'eagerness' => 'conservative', 'exclude' => '/checkout/*'], 'https://shop.example/de/')->toArray()['prefetch'][0];

        $this->assertSame('conservative', $rule['eagerness']);
        $this->assertSame(['href_matches' => '/de/*'], $rule['where']['and'][0]);
        $this->assertSame(['not' => ['href_matches' => ['/de/checkout/*']]], $rule['where']['and'][1]);
    }

    public function testBadPatternsAndValuesAreIgnored(): void
    {
        $rules = $this->rules(['mode' => 'everything', 'eagerness' => 'immediate', 'exclude' => "checkout\n/ok/*\n/bad path\n/x?y\n/ok/*"]);

        $this->assertSame(['/ok/*'], $rules->excludedPaths());
        $this->assertArrayHasKey('prerender', $rules->toArray());
        $this->assertSame('moderate', $rules->toArray()['prerender'][0]['eagerness']);
    }

    public function testRulesUrl(): void
    {
        $this->assertSame('https://shop.example/zero/instant/rules/', $this->rules([])->url());
    }
}
