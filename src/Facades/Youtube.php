<?php 

namespace Madcoda\Youtube\Facades;

use Illuminate\Support\Facades\Facade;

class Youtube extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'Madcoda\Youtube\Youtube';
    }
}
