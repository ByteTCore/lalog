<?php

namespace Lalog\Facades;

use Illuminate\Support\Facades\Facade;
use Lalog\QueryLogger;

/**
 * @method static void listen()
 *
 * @see QueryLogger
 */
class Lalog extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return QueryLogger::class;
    }
}
