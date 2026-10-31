<?php

namespace App\Http\Controllers\Management;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        return Inertia::render('management/Users', [
            'users' => User::select(['id', 'name', 'email', 'email_verified_at', 'created_at'])->with('roles:id,name')
                ->when($filters['q'] ?? null, fn ($query, $q) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$q.'%')->orWhere('email', 'like', '%'.$q.'%')))
                ->latest()->paginate(20)->withQueryString(),
            'roles' => array_column(Role::cases(), 'value'),
            'filters' => $filters,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->id === $user->id) {
            throw ValidationException::withMessages(['role' => 'No puedes cambiar tu propio rol desde este panel.']);
        }
        $data = $request->validate(['role' => ['required', Rule::enum(Role::class)]]);
        DB::transaction(function () use ($user, $data): void {
            $before = $user->getRoleNames()->all();
            $user->syncRoles([$data['role']]);
            AuditLog::record('user.role_changed', $user, ['before' => $before, 'after' => [$data['role']]]);
        });

        return back()->with('success', 'Rol actualizado. Los permisos se aplican desde la siguiente solicitud.');
    }
}
