<?php

declare(strict_types=1);

namespace Lits\Config;

use Lits\Config;

final class CsvConfig extends Config
{
    public ?int $header = 0;
    public string $delimiter = '~';
    public int $biblios = 1;

    /** @var string[] */
    public array $columns = [
        'location',
        'status',
        'message',
        'barcodes',
        'record',
        'created',
        'updated',
        'revision',
        'biblios',
    ];
}
