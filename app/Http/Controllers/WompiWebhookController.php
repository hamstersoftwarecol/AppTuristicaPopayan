<?php

namespace App\Http\Controllers;

use App\Services\Payments\WompiSandbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WompiWebhookController extends Controller
{
    public function __invoke(Request $request, WompiSandbox $wompi): JsonResponse
    {
        abort_unless($request->isJson() && strlen($request->getContent()) <= 65536, 400);
        $wompi->handleEvent($request->json()->all());

        return response()->json(['received' => true]);
    }
}
