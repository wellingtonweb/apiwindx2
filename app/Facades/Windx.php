<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Windx extends Facade {

    protected static function getFacadeAccessor()
    {
        return 'windx-client'; // O nome do binding de serviço que você deseja usar
    }
}