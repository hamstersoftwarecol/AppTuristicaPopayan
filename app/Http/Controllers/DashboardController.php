<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Booking;
use App\Models\Provider;
use App\Models\TourismService;
use App\Models\TravelInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): RedirectResponse
    {
        // The highest assigned role determines the initial workspace.
        foreach (array_reverse(Role::cases()) as $role) {
            if ($request->user()?->hasRole($role->value)) {
                return to_route('dashboard.role', ['role' => $role->value]);
            }
        }

        abort(403, 'Tu cuenta aún no tiene un rol asignado.');
    }

    public function show(Request $request, string $role): Response
    {
        abort_unless($request->user()?->hasRole($role), 403);

        return Inertia::render('Dashboard', [
            'role' => $role,
            'permissions' => $request->user()->getAllPermissions()->pluck('name'),
            'stats' => [
                'my_bookings' => Booking::where('user_id', $request->user()->id)->where('status', 'confirmed')->count(),
                'my_inquiries' => TravelInquiry::where('user_id', $request->user()->id)->count(),
                'my_favorites' => DB::table('favorites')->where('user_id', $request->user()->id)->count(),
                'providers' => $request->user()->can('providers.manage') ? Provider::count() : null,
                'pending_inquiries' => $request->user()->can('inquiries.manage') ? TravelInquiry::whereIn('status', ['pending', 'in_progress'])->count() : null,
                'inventory' => $request->user()->can('services.manage') ? TourismService::count() : ($request->user()->can('inventory.manage-own') ? TourismService::whereHas('provider', fn ($query) => $query->where('owner_id', $request->user()->id))->count() : null),
            ],
        ]);
    }
}
