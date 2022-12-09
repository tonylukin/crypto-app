<?php

declare(strict_types=1);

namespace App\Model\Admin;

class DateIntervalModel
{
    public ?string $dateStart = null;
    public ?string $dateEnd = null;
    public ?string $daysAgo = '7';
}
