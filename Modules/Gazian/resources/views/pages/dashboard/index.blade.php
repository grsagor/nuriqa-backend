@extends('gazian::layout.app')

@section('title', 'Dashboard — Gazian Water Admin')

@section('content')
<div class="page-shell">
    <div class="page-top">
        <div>
            <h1 class="page-heading">Gazian Water Dashboard</h1>
            <p class="page-subtitle">Newsletter subscribers and trade enquiries</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="surface p-4 h-100">
                <p class="text-muted mb-1">Newsletter subscribers</p>
                <h2 class="mb-3">{{ number_format($newsletterCount) }}</h2>
                <a href="{{ route('gazian.admin.newsletter-subscribers.index') }}" class="btn btn-sm btn-outline-primary">View list</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="surface p-4 h-100">
                <p class="text-muted mb-1">Trade enquiries</p>
                <h2 class="mb-3">{{ number_format($enquiryCount) }}</h2>
                <a href="{{ route('gazian.admin.trade-enquiries.index') }}" class="btn btn-sm btn-outline-primary">View list</a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="surface p-4 h-100">
                <p class="text-muted mb-1">Unread enquiries</p>
                <h2 class="mb-3">{{ number_format($unreadEnquiryCount) }}</h2>
                <a href="{{ route('gazian.admin.trade-enquiries.index') }}" class="btn btn-sm btn-outline-warning">Review</a>
            </div>
        </div>
    </div>
</div>
@endsection
