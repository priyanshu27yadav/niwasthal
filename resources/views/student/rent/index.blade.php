@extends('layouts.student')

@section('title', 'My Rent & Dues — Niwasthal')
@section('page-title', 'Rent & Outstanding Dues')

@section('content')
@include('student.partials.rent-bills-table', [
    'records' => $rentRecords,
    'totalOutstanding' => $totalOutstanding,
    'razorpayConfigured' => $razorpayConfigured,
])

<div class="mt-3">{{ $rentRecords->links() }}</div>
@endsection
