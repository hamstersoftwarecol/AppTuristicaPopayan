<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\ServiceRequest;
use App\Models\AuditLog;
use App\Models\Provider;
use App\Models\TourismService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(Request $request): Response
    {
        $admin = $request->user()?->can('services.manage');
        abort_unless($admin || $request->user()?->can('inventory.manage-own'), 403);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:draft,review,published,archived']]);
        $providers = Provider::query()->when(! $admin, fn ($query) => $query->where('owner_id', $request->user()->id));

        return Inertia::render('management/Services', [
            'services' => TourismService::with('provider:id,name')
                ->whereIn('provider_id', (clone $providers)->select('id'))
                ->when($filters['q'] ?? null, fn ($query, $q) => $query->where('title', 'like', '%'.$q.'%'))
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->latest()->paginate(15)->withQueryString(),
            'providers' => $providers->orderBy('name')->get(['id', 'name', 'status']),
            'categories' => array_diff_key(config('tourism.modules'), ['blog' => true]),
            'canPublish' => $admin,
            'filters' => $filters,
        ]);
    }

    public function store(ServiceRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $service = TourismService::create($request->validated());
            AuditLog::record('service.created', $service, ['status' => $service->status]);
        });

        return back()->with('success', 'Servicio creado.');
    }

    public function update(ServiceRequest $request, TourismService $service): RedirectResponse
    {
        Gate::authorize('update', $service);
        DB::transaction(function () use ($request, $service): void {
            $service = TourismService::lockForUpdate()->findOrFail($service->id);
            if ($service->departures()->whereHas('bookings')->exists()
                && ($service->provider_id !== $request->integer('provider_id') || $service->category !== $request->input('category'))) {
                throw ValidationException::withMessages(['provider_id' => 'Un servicio con reservas no puede cambiar de proveedor o categoría.']);
            }
            $before = $service->status;
            $service->update($request->validated());
            AuditLog::record('service.updated', $service, ['from' => $before, 'to' => $service->status]);
        });

        return back()->with('success', 'Servicio actualizado.');
    }
}
