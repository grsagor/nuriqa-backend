<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
    <div class="modal-content">
        <div class="modal-header header-bg text-white">
            <h5 class="modal-title">Join Us Application Details</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <strong>Type:</strong>
                    <p><span
                            class="badge bg-{{ $application->type === 'model' ? 'primary' : 'info' }}">{{ ucfirst($application->type) }}</span>
                    </p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Status:</strong>
                    <p>
                        <span
                            class="badge bg-{{ $application->status === 'accepted' ? 'success' : ($application->status === 'rejected' ? 'danger' : ($application->status === 'reviewed' ? 'info' : 'warning')) }}">
                            {{ ucfirst($application->status ?? 'pending') }}
                        </span>
                    </p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Full Name:</strong>
                    <p>{{ $application->full_name }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Email:</strong>
                    <p>{{ $application->email }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Phone:</strong>
                    <p>{{ $application->phone }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Age:</strong>
                    <p>{{ $application->age ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Gender:</strong>
                    <p>{{ $application->gender ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6 mb-3">
                    <strong>Nationality:</strong>
                    <p>{{ $application->nationality ?? 'N/A' }}</p>
                </div>
                <div class="col-md-12 mb-3">
                    <strong>Address:</strong>
                    <p>{{ $application->address }}{{ $application->apartment_suite_unit ? ', ' . $application->apartment_suite_unit : '' }},
                        {{ $application->city }}, {{ $application->postal_code }}</p>
                </div>
                @if($application->type === 'model')
                    @if($application->height)
                        <div class="col-md-6 mb-3">
                            <strong>Height:</strong>
                            <p>{{ $application->height }}</p>
                        </div>
                    @endif
                    @if($application->weight)
                        <div class="col-md-6 mb-3">
                            <strong>Weight:</strong>
                            <p>{{ $application->weight }}</p>
                        </div>
                    @endif
                    @if($application->comfort_preferences)
                        <div class="col-md-12 mb-3">
                            <strong>Comfort Preferences:</strong>
                            <p>{{ is_array($application->comfort_preferences) ? implode(', ', $application->comfort_preferences) : $application->comfort_preferences }}
                            </p>
                        </div>
                    @endif
                    @if($application->model_experiences)
                        <div class="col-md-12 mb-3">
                            <strong>Model Experiences:</strong>
                            <p>{{ is_array($application->model_experiences) ? implode(', ', $application->model_experiences) : $application->model_experiences }}
                            </p>
                        </div>
                    @endif
                    @if($application->model_motivation)
                        <div class="col-md-12 mb-3">
                            <strong>Model Motivation:</strong>
                            <p>{{ $application->model_motivation }}</p>
                        </div>
                    @endif
                @else
                    @if($application->areas_of_interest)
                        <div class="col-md-12 mb-3">
                            <strong>Areas of Interest:</strong>
                            <p>{{ is_array($application->areas_of_interest) ? implode(', ', $application->areas_of_interest) : $application->areas_of_interest }}
                            </p>
                        </div>
                    @endif
                    @if($application->volunteer_experiences)
                        <div class="col-md-12 mb-3">
                            <strong>Volunteer Experiences:</strong>
                            <p>{{ $application->volunteer_experiences }}</p>
                        </div>
                    @endif
                    @if($application->availability)
                        <div class="col-md-12 mb-3">
                            <strong>Availability:</strong>
                            <p>{{ is_array($application->availability) ? implode(', ', $application->availability) : $application->availability }}
                            </p>
                        </div>
                    @endif
                    @if($application->commitment_level)
                        <div class="col-md-12 mb-3">
                            <strong>Commitment Level:</strong>
                            <p>{{ is_array($application->commitment_level) ? implode(', ', $application->commitment_level) : $application->commitment_level }}
                            </p>
                        </div>
                    @endif
                    @if($application->volunteer_motivation)
                        <div class="col-md-12 mb-3">
                            <strong>Volunteer Motivation:</strong>
                            <p>{{ $application->volunteer_motivation }}</p>
                        </div>
                    @endif
                @endif
                <div class="col-md-12 mb-3">
                    <strong>Created At:</strong>
                    <p>{{ $application->created_at->format('d M Y H:i') }}</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>