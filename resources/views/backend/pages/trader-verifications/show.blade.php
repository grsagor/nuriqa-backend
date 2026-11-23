@php
    $badge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'draft' => 'secondary'][$v->status] ?? 'secondary';
    $address = fn ($p) => collect([$v->{$p.'_address_line1'}, $v->{$p.'_address_line2'}, $v->{$p.'_city'}, $v->{$p.'_postcode'}, $v->{$p.'_country'}])->filter()->implode(', ') ?: '—';
    $row = fn ($label, $value) => '<div class="col-md-6 mb-3"><strong>'.e($label).'</strong><p class="mb-0">'.($value !== null && $value !== '' ? e($value) : '—').'</p></div>';
    $yes = fn ($flag) => $flag ? 'Yes' : 'No';
@endphp
<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
    <div class="modal-content">
        <div class="modal-header header-bg text-white">
            <h5 class="modal-title">Trader verification #{{ $v->id }} <span class="badge bg-{{ $badge }} ms-2">{{ ucfirst($v->status) }}</span></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">

            <h6 class="text-uppercase text-muted">Account</h6>
            <div class="row">
                {!! $row('Name', $v->user?->name) !!}
                {!! $row('Email', $v->user?->email) !!}
                {!! $row('Role', $v->user?->role?->name) !!}
                {!! $row('Submitted', $v->submitted_at?->format('d M Y H:i')) !!}
            </div>
            <hr>

            <h6 class="text-uppercase text-muted">Business</h6>
            <div class="row">
                {!! $row('Legal name', $v->legal_name) !!}
                {!! $row('Trading name', $v->trading_name) !!}
                {!! $row('Business type', ucfirst(str_replace('_', ' ', (string) $v->business_type))) !!}
                {!! $row('Companies House number', $v->companies_house_number) !!}
                {!! $row('Registered address', $address('registered')) !!}
                {!! $row('Trading address', $v->trading_address_same_as_registered ? 'Same as registered address' : $address('trading')) !!}
                {!! $row('Returns address', $v->returns_address_same_as_trading ? 'Same as trading address' : $address('returns')) !!}
                {!! $row('VAT', $v->vat_registered ? 'Registered: '.($v->vat_number ?: '—') : 'Not VAT registered') !!}
            </div>
            <hr>

            <h6 class="text-uppercase text-muted">Owner / director and contact</h6>
            <div class="row">
                {!! $row('Owner / director', $v->owner_full_name) !!}
                {!! $row('Their role', ucfirst((string) $v->owner_role)) !!}
                {!! $row('Contact name', $v->contact_name) !!}
                {!! $row('Contact email', $v->contact_email) !!}
                {!! $row('Contact phone', $v->contact_phone) !!}
            </div>
            <hr>

            <h6 class="text-uppercase text-muted">Bank account</h6>
            <div class="row">
                {!! $row('Account holder', $v->bank_account_holder) !!}
                {!! $row('Sort code', $v->bank_sort_code ? substr($v->bank_sort_code, 0, 2).'-'.substr($v->bank_sort_code, 2, 2).'-'.substr($v->bank_sort_code, 4, 2) : null) !!}
                {!! $row('Account number', $v->bank_account_number) !!}
            </div>
            <p class="small text-muted">Check the account holder matches the legal or trading name and the bank proof below.</p>
            <hr>

            <h6 class="text-uppercase text-muted">Online presence and products</h6>
            <div class="row">
                <div class="col-md-6 mb-3"><strong>Website</strong>
                    <p class="mb-0">@if($v->website_url)<a href="{{ $v->website_url }}" target="_blank" rel="noopener noreferrer nofollow">{{ $v->website_url }}</a>@else — @endif</p></div>
                <div class="col-md-6 mb-3"><strong>Social media</strong>
                    @forelse(($v->social_links ?? []) as $link)<p class="mb-0"><a href="{{ $link }}" target="_blank" rel="noopener noreferrer nofollow">{{ $link }}</a></p>@empty<p class="mb-0">—</p>@endforelse</div>
                {!! $row('Product categories', implode(', ', $categories)) !!}
            </div>
            <hr>

            <h6 class="text-uppercase text-muted">Declarations</h6>
            <div class="row">
                {!! $row('Products are genuine', $yes($v->confirm_products_genuine)) !!}
                {!! $row('Products are safe and legally compliant', $yes($v->confirm_legal_compliance)) !!}
                {!! $row('Agreed to Origin seller terms', $yes($v->accepted_seller_terms).($v->terms_version ? ' (version '.$v->terms_version.')' : '')) !!}
                {!! $row('Accepted on', $v->terms_accepted_at ? $v->terms_accepted_at->format('d M Y H:i').' from '.$v->terms_accepted_ip : null) !!}
            </div>
            <hr>

            <h6 class="text-uppercase text-muted">Documents</h6>
            @forelse($v->documents->groupBy('type') as $type => $docs)
                <p class="mb-1"><strong>{{ $documentTypeLabels[$type] ?? $type }}</strong></p>
                <ul>
                    @foreach($docs as $doc)
                        <li><a href="{{ route('admin.trader-verifications.document', $doc->id) }}" target="_blank" rel="noopener">{{ $doc->original_name }}</a>
                            <span class="small text-muted">({{ number_format($doc->size / 1024) }} KB, {{ $doc->created_at->format('d M Y') }})</span></li>
                    @endforeach
                </ul>
            @empty
                <p class="text-muted">No documents uploaded.</p>
            @endforelse
            <p class="small text-muted">Every document you open is recorded in the history below.</p>

            @if($v->status === 'rejected' && $v->rejection_reason)
                <div class="alert alert-danger"><strong>Rejected:</strong> {{ $v->rejection_reason }}</div>
            @endif

            @if($v->status === 'pending')
                <hr>
                <div class="row g-4">
                    <div class="col-md-6">
                        <form id="traderApproveForm">
                            @csrf
                            <h6>Approve</h6>
                            <div class="mb-2">
                                <label class="form-label" for="approve_notes">Internal notes (optional, never shown to the trader)</label>
                                <textarea name="internal_notes" id="approve_notes" class="form-control" rows="3">{{ $v->internal_notes }}</textarea>
                            </div>
                            <button type="button" class="btn btn-success form_submit_btn" data-url="{{ route('admin.trader-verifications.approve', $v->id) }}">
                                <i class="fas fa-check"></i> Approve trader
                            </button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form id="traderRejectForm">
                            @csrf
                            <h6>Reject</h6>
                            <div class="mb-2">
                                <label class="form-label" for="reject_reason">Reason (shown to the trader, so they know what to fix)</label>
                                <textarea name="rejection_reason" id="reject_reason" class="form-control" rows="3" minlength="10" required></textarea>
                                <small class="error-text text-danger"></small>
                            </div>
                            <button type="button" class="btn btn-danger form_submit_btn" data-url="{{ route('admin.trader-verifications.reject', $v->id) }}">
                                <i class="fas fa-times"></i> Reject application
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            @if($history->isNotEmpty())
                <hr>
                <h6 class="text-uppercase text-muted">History</h6>
                <ul class="small mb-0">
                    @foreach($history as $log)
                        <li>{{ $log->created_at->format('d M Y H:i') }}: {{ str_replace(['trader_verification.', '_'], ['', ' '], $log->action) }}
                            by {{ $log->actor?->name ?? 'the trader' }}@if($log->message) <span class="text-muted">({{ \Illuminate\Support\Str::limit($log->message, 80) }})</span>@endif</li>
                    @endforeach
                </ul>
            @endif
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>
