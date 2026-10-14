<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\InstantNavigation\Controller\Instant;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Serialize\Serializer\Json;
use Zero\InstantNavigation\Model\Rules as RuleSet;

/**
 * /zero/instant/rules/: the speculation rules JSON the Speculation-Rules
 * header points to. Browsers only accept it with this MIME type. Public and
 * the same for every visitor, so caches may keep it for an hour.
 */
class Rules implements HttpGetActionInterface
{
    public function __construct(
        private readonly RawFactory $rawFactory,
        private readonly RuleSet $rules,
        private readonly Json $json
    ) {
    }

    /**
     * @return Raw
     * @throws NotFoundException
     */
    public function execute()
    {
        if (!$this->rules->isEnabled()) {
            throw new NotFoundException(__('Instant navigation is off.'));
        }
        $result = $this->rawFactory->create();
        $result->setHeader('Content-Type', 'application/speculationrules+json', true)
            ->setHeader('Cache-Control', 'public, max-age=3600', true)
            ->setContents($this->json->serialize($this->rules->toArray()));

        return $result;
    }
}
