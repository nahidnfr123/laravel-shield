<?php

namespace NahidFerdous\Shield\Tests\Unit;

use NahidFerdous\Shield\Http\Middleware\ShieldLog;
use NahidFerdous\Shield\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ShieldLogTest extends TestCase
{
    public function test_it_silences_when_debug_disabled(): void
    {
        config(['app.debug' => false]);

        Log::spy();

        $request = Request::create('/api/test', 'GET');
        $response = new Response('', 200);

        (new ShieldLog)->terminate($request, $response);

        Log::shouldNotHaveReceived('info');
        Log::shouldNotHaveReceived('debug');
    }

    public function test_it_logs_when_debug_enabled(): void
    {
        config(['app.debug' => true]);

        Log::spy();

        $request = Request::create('/api/test', 'GET');
        $response = new Response('', 200);

        (new ShieldLog)->terminate($request, $response);

        Log::shouldHaveReceived('info')->twice();
        Log::shouldHaveReceived('debug')->times(4);
    }
}
