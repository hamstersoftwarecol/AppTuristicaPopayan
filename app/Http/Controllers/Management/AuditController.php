<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $filters = $request->validate(['action' => ['nullable', 'string', 'max:100']]);

        return Inertia::render('management/Audit', [
            'logs' => AuditLog::with('actor:id,name')->when($filters['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
                ->latest('id')->paginate(30)->withQueryString(),
            'actions' => AuditLog::distinct()->orderBy('action')->pluck('action'),
            'filters' => $filters,
        ]);
    }
}
