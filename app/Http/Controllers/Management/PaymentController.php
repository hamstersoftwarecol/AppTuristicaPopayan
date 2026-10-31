<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\PaymentAttempt;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['status' => ['nullable', 'in:CREATED,PENDING,APPROVED,DECLINED,ERROR,VOIDED']]);

        return Inertia::render('management/Payments', [
            'payments' => PaymentAttempt::with('booking:id,reference,service_title,status')
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))->latest()->paginate(20)->withQueryString(),
            'filters' => $filters,
        ]);
    }
}
