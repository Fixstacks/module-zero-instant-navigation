<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\InstantNavigation\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Eagerness implements OptionSourceInterface
{
    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => 'moderate', 'label' => __('Moderate: when pointing at a link')],
            ['value' => 'conservative', 'label' => __('Conservative: when the click starts')],
        ];
    }
}
