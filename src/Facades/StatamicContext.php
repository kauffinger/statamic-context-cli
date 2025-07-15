<?php

declare(strict_types=1);

namespace StatamicContext\StatamicContext\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \StatamicContext\StatamicContext\StatamicContext
 */
class StatamicContext extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \StatamicContext\StatamicContext\StatamicContext::class;
    }
}
