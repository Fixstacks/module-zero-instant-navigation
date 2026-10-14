<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 *
 * Zero_InstantNavigation: the browser loads (or renders) the next page while
 * the shopper is still pointing at its link (Speculation Rules API), so the
 * click feels instant. Off by default: Stores > Configuration > Zero >
 * Instant Navigation.
 */
use Magento\Framework\Component\ComponentRegistrar;

ComponentRegistrar::register(
    ComponentRegistrar::MODULE,
    'Zero_InstantNavigation',
    __DIR__
);
