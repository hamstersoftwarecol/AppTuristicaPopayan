<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePrivilegedTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        foreach (Role::cases() as $role) {
            if ($role->requiresTwoFactor() && $user?->hasRole($role->value)
                && (! $user->two_factor_secret || ! $user->two_factor_confirmed_at)) {
                return redirect()->route('security.edit');
            }
        }

        return $next($request);
    }
}
