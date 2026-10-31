<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\TravelInquiry;
use App\Services\AccountNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InquiryController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['status' => ['nullable', 'in:pending,in_progress,responded,cancelled']]);

        return Inertia::render('Inquiries', [
            'inquiries' => TravelInquiry::with(['service:id,title,destination', 'customer:id,name,email'])
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->latest()->paginate(15)->withQueryString(),
            'managed' => true,
            'filters' => $filters,
        ]);
    }

    public function update(Request $request, TravelInquiry $inquiry): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:in_progress,responded,cancelled'],
            'response' => ['nullable', 'required_if:status,responded', 'string', 'min:10', 'max:10000'],
        ]);
        DB::transaction(function () use ($request, $data, $inquiry): void {
            $locked = TravelInquiry::lockForUpdate()->findOrFail($inquiry->id);
            abort_unless(in_array($locked->status, ['pending', 'in_progress'], true), 409, 'La solicitud ya fue cerrada.');
            $locked->update([...$data, 'handled_by' => $request->user()->id]);
            AuditLog::record('inquiry.updated', $locked, ['status' => $locked->status]);
            app(AccountNotifier::class)->inquiry($locked);
        });

        return back()->with('success', 'Solicitud actualizada. La respuesta está disponible en el panel del cliente.');
    }
}
