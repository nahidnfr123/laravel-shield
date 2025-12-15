<?php

namespace NahidFerdous\Shield\Facades;

use Illuminate\Support\Facades\Facade;

class Shield extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'shield';
    }
}
