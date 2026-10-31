<?php

namespace App\Http\Controllers;

use App\Models\TourismService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    public function store(Request $request, TourismService $service): RedirectResponse
    {
        abort_unless(TourismService::published()->whereKey($service->id)->exists(), 404);
        DB::table('favorites')->insertOrIgnore(['user_id' => $request->user()->id, 'tourism_service_id' => $service->id]);

        return back()->with('success', 'Servicio guardado en favoritos.');
    }

    public function destroy(Request $request, TourismService $service): RedirectResponse
    {
        DB::table('favorites')->where('user_id', $request->user()->id)->where('tourism_service_id', $service->id)->delete();

        return back()->with('success', 'Servicio retirado de favoritos.');
    }
}
