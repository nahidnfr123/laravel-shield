<?php

namespace HasinHayder\Tyro\Http\Controllers;

use Illuminate\Routing\Controller;

class TyroController extends Controller {
    public function tyro() {
        return response([
            'message' => 'Welcome to Tyro, the zero config API boilerplate with roles and abilities for Laravel Sanctum. Visit https://github.com/hasinhayder/tyro for documentation.',
        ]);
    }

    public function version() {
        return response([
            'version' => config('tyro.version'),
        ]);
    }
}
