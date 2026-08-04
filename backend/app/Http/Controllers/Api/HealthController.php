<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = 'ok';

        try {
            DB::select('select 1');
        } catch (\Throwable) {
            $database = 'failed';
        }

        return response()->json([
            'status' => $database === 'ok' ? 'ok' : 'degraded',
            'service' => config('app.name'),
            'database' => $database,
            'timestamp' => now()->toISOString(),
        ], $database === 'ok' ? 200 : 503);
    }
}
