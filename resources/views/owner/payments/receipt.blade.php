@extends('layouts.owner')

@section('title', 'Receipt ' . $payment->receipt_number)
@section('page-title', 'Payment Receipt')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <a href="{{ route('owner.payments.index') }}" class="btn btn-light btn-sm border"><i class="bi bi-arrow-left me-1"></i> Back</a>
    <button onclick="window.print()" class="btn btn-primary btn-sm"><i class="bi bi-printer me-1"></i> Print Receipt</button>
</div>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="lm-card p-5">
            <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
                <div>
                    <h4 class="fw-bold text-primary mb-1">{{ $payment->property?->name ?? 'Niwasthal PG' }}</h4>
                    <div class="small text-muted">{{ $payment->property?->address }}, {{ $payment->property?->city }}</div>
                </div>
                <div class="text-end">
                    <span class="badge bg-success fs-6 mb-1">PAID</span>
                    <div class="font-monospace small text-muted">{{ $payment->receipt_number }}</div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-6">
                    <div class="small text-muted">Received From</div>
                    <div class="fw-bold">{{ $payment->student_name }}</div>
                </div>
                <div class="col-6 text-end">
                    <div class="small text-muted">Payment Date</div>
                    <div class="fw-semibold">{{ $payment->paid_at?->format('d M Y, h:i A') }}</div>
                </div>
            </div>

            <table class="table table-bordered mb-4">
                <thead><tr><th>Description</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                    <tr>
                        <td>Rent / Fee Payment ({{ strtoupper($payment->method) }})</td>
                        <td class="text-end fw-bold">₹{{ number_format($payment->amount, 2) }}</td>
                    </tr>
                </tbody>
            </table>

            <div class="text-center small text-muted mt-4 pt-3 border-top">
                This is a computer-generated receipt from Niwasthal. Thank you!
            </div>
        </div>
    </div>
</div>
@endsection
