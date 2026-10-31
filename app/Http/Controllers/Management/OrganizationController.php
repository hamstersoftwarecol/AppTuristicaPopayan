<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Provider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('management/Organization', [
            'providers' => Provider::where('owner_id', $request->user()->id)->orderBy('name')->get(['id', 'name', 'contact_email', 'phone', 'website', 'status']),
        ]);
    }

    public function update(Request $request, Provider $provider): RedirectResponse
    {
        abort_unless($provider->owner_id === $request->user()->id, 403);
        $data = $request->validate([
            'contact_email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'url:https', 'max:255'],
        ]);
        DB::transaction(function () use ($provider, $data): void {
            $provider->update($data);
            AuditLog::record('provider.contact_updated', $provider);
        });

        return back()->with('success', 'Datos de contacto actualizados.');
    }
}
