<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\TourismService;
use App\Models\TravelInquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class InquiryController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Inquiries', [
            'inquiries' => TravelInquiry::where('user_id', $request->user()->id)->with('service:id,title,destination')->latest()->paginate(15),
            'managed' => false,
            'filters' => [],
        ]);
    }

    public function store(Request $request, TourismService $service): RedirectResponse
    {
        abort_unless(TourismService::published()->whereKey($service->id)->exists(), 404);
        $data = $request->validate([
            'travel_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'travelers' => ['required', 'integer', 'min:1', 'max:100'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);
        DB::transaction(function () use ($request, $service, $data): void {
            $inquiry = TravelInquiry::create([...$data, 'user_id' => $request->user()->id, 'tourism_service_id' => $service->id]);
            AuditLog::record('inquiry.created', $inquiry);
        });

        return to_route('inquiries.index')->with('success', 'Solicitud enviada. El equipo confirmará las condiciones y disponibilidad.');
    }

    public function cancel(Request $request, TravelInquiry $inquiry): RedirectResponse
    {
        abort_unless($inquiry->user_id === $request->user()->id, 403);
        DB::transaction(function () use ($inquiry): void {
            $locked = TravelInquiry::lockForUpdate()->findOrFail($inquiry->id);
            abort_unless(in_array($locked->status, ['pending', 'in_progress'], true), 409, 'La solicitud ya fue cerrada.');
            $locked->update(['status' => 'cancelled']);
            AuditLog::record('inquiry.cancelled', $locked);
        });

        return back()->with('success', 'Solicitud cancelada.');
    }
}
