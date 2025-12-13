<form class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    @csrf
    @method('PUT')
    <div class="modal-content">
        <div class="modal-header header-bg text-white">
            <h1 class="modal-title fs-5" id="crudModalLabel">Edit User</h1>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ $user->name ?? '' }}" placeholder="Enter full name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" id="email" class="form-control" value="{{ $user->email ?? '' }}" placeholder="Enter email address" required>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="phone" class="form-label fw-semibold">Phone</label>
                    <input type="tel" name="phone" id="phone" class="form-control" value="{{ $user->phone ?? '' }}" placeholder="Enter phone number">
                </div>
                <div class="col-md-6 mb-3">
                    <label for="signup_date" class="form-label fw-semibold">Signup Date</label>
                    <input type="date" name="signup_date" id="signup_date" class="form-control" value="{{ $user->signup_date ?? '' }}">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <input type="password" name="password" id="password" class="form-control" placeholder="Leave blank to keep current password">
                    <div class="form-text">Enter new password only if you want to change it</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="password_confirmation" class="form-label fw-semibold">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Confirm new password">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="role_id" class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                    <select name="role_id" id="role_id" class="form-select" required>
                        <option value="">Select Role</option>
                        @if(isset($roles))
                            @foreach($roles as $id => $name)
                                <option value="{{ $id }}" {{ (isset($user->role_id) && $user->role_id == $id) ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="lang_id" class="form-label fw-semibold">Language</label>
                    <select name="lang_id" id="lang_id" class="form-select">
                        <option value="">Select Language</option>
                        @if(isset($languages))
                            @foreach($languages as $id => $name)
                                <option value="{{ $id }}" {{ (isset($user->lang_id) && $user->lang_id == $id) ? 'selected' : '' }}>{{ $name }}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="image" class="form-label fw-semibold">Profile Image</label>
                <input type="file" name="image" id="image" class="form-control image-preview-input" 
                       accept="image/*" data-preview-container="#imagePreviewContainer">
                <div id="imagePreviewContainer" class="mt-2 image-preview-container">
                    @if(isset($user->image) && !empty($user->image))
                        <div class="current-image-preview">
                            <img src="{{ asset('storage/users/' . $user->image) }}" alt="Current Image" class="img-thumbnail">
                            <div class="form-text mt-1">Current image</div>
                        </div>
                    @endif
                </div>
                <div class="form-text">Allowed formats: jpeg, png, jpg, gif (Max size: 2MB)</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary form_submit_btn"
                data-url="{{ route('admin.users.update', $user->id) }}">Update User</button>
        </div>
    </div>
</form>