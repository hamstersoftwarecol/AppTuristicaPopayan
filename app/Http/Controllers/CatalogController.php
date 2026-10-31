<?php

namespace App\Http\Controllers;

use App\Models\Departure;
use App\Models\TourismService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', Rule::in(array_diff(array_keys(config('tourism.modules')), ['blog']))],
            'destination' => ['nullable', 'string', 'max:100'],
            'max_price' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'sort' => ['nullable', Rule::in(['recent', 'price_asc', 'price_desc'])],
            'favorites' => ['nullable', 'boolean'],
        ]);
        $filters['favorites'] = $request->boolean('favorites');
        $query = TourismService::published()->with('provider:id,name');
        $query->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($query) => $query->where('title', 'like', '%'.$q.'%')->orWhere('description', 'like', '%'.$q.'%')))
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['destination'] ?? null, fn ($query, $destination) => $query->where('destination', 'like', '%'.$destination.'%'));
        if (isset($filters['max_price'])) {
            $query->where('reference_price', '<=', $filters['max_price']);
        }
        if ($request->boolean('favorites')) {
            abort_unless($request->user() !== null, 403);
            $query->whereIn('id', DB::table('favorites')->where('user_id', $request->user()->id)->select('tourism_service_id'));
        }
        $sort = $filters['sort'] ?? 'recent';
        if ($sort === 'recent') {
            $query->latest('id');
        } else {
            $query->orderByRaw('CASE WHEN reference_price IS NULL THEN 1 ELSE 0 END')->orderBy('reference_price', $sort === 'price_asc' ? 'asc' : 'desc')->orderBy('id');
        }

        return Inertia::render('catalog/Index', [
            'services' => $query->paginate(12)->withQueryString(),
            'categories' => array_diff_key(config('tourism.modules'), ['blog' => true]),
            'filters' => $filters,
            'favoriteIds' => $request->user() ? DB::table('favorites')->where('user_id', $request->user()->id)->pluck('tourism_service_id') : [],
        ]);
    }

    public function show(Request $request, TourismService $service): Response
    {
        abort_unless(TourismService::published()->whereKey($service->id)->exists(), 404);

        return Inertia::render('catalog/Show', [
            'service' => $service->load('provider:id,name'),
            'departures' => in_array($service->category, ['actividades', 'paquetes'], true) ? Departure::where('tourism_service_id', $service->id)->where('is_open', true)->where('service_date', '>', today())
                ->with('service.provider:id,name')
                ->withSum(['bookings as occupied' => fn ($query) => $query->where(fn ($query) => $query->where('status', 'confirmed')->orWhere(fn ($query) => $query->where('status', 'pending')->where('expires_at', '>', now())))], 'travelers')
                ->orderBy('service_date')->limit(90)->get()->map(fn (Departure $departure) => [
                    'id' => $departure->id,
                    'service_date' => $departure->service_date->toDateString(),
                    'available' => max(0, $departure->capacity - (int) $departure->getAttribute('occupied')),
                    'unit_price' => $departure->unit_price,
                    'terms' => $departure->terms,
                    'quote_token' => $departure->quoteToken(),
                ]) : [],
            'isFavorite' => $request->user() && DB::table('favorites')->where('user_id', $request->user()->id)->where('tourism_service_id', $service->id)->exists(),
        ]);
    }
}
