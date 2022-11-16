<?php

declare(strict_types=1);

namespace Lits\Config;

use Lits\Config;
use Safe\DateTimeImmutable;

final class DepoConfig extends Config
{
    public ?DateTimeImmutable $start = null;
}
