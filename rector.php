<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
->withPaths([
    __DIR__.'/src',
    __DIR__.'/config',
])
->withTypeCoverageLevel(36)
->withDeadCodeLevel(40)
->withPreparedSets(
    codeQuality: true,
    instanceOf: true,
    earlyReturn: true,
    strictBooleans: true,
)
->withImportNames();
