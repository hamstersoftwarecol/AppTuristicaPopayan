<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Management\ProviderRequest;
use App\Models\AuditLog;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProviderController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'in:pending,active,inactive']]);

        return Inertia::render('management/Providers', [
            'providers' => Provider::with('owner:id,name,email')
                ->when($filters['q'] ?? null, fn ($query, $q) => $query->where('name', 'like', '%'.$q.'%'))
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->latest()->paginate(15)->withQueryString(),
            'owners' => User::role('proveedor')->orderBy('name')->get(['id', 'name', 'email']),
            'filters' => $filters,
        ]);
    }

    public function store(ProviderRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $provider = Provider::create($request->validated());
            AuditLog::record('provider.created', $provider, ['status' => $provider->status, 'owner_id' => $provider->owner_id]);
        });

        return back()->with('success', 'Proveedor creado.');
    }

    public function update(ProviderRequest $request, Provider $provider): RedirectResponse
    {
        DB::transaction(function () use ($request, $provider): void {
            $before = $provider->only(['status', 'owner_id']);
            $provider->update($request->validated());
            AuditLog::record('provider.updated', $provider, ['before' => $before, 'after' => $provider->only(['status', 'owner_id'])]);
        });

        return back()->with('success', 'Proveedor actualizado.');
    }
}
