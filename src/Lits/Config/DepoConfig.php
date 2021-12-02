<?php

declare(strict_types=1);

namespace Lits\Config;

use DateTimeInterface as DateTime;
use Lits\Config;

final class DepoConfig extends Config
{
    public ?DateTime $start = null;
}
