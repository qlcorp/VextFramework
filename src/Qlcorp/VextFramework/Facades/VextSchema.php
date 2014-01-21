<?php namespace Qlcorp\VextFramework\Facades;

use \Illuminate\Support\Facades\Facade;

class VextSchema extends Facade {
    protected static function getFacadeAccessor() {
        return 'vextSchema';
    }
} 