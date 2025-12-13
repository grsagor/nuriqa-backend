<form class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    @csrf
    @method('PUT')
    <div class="modal-content">
        <div class="modal-header header-bg text-white">
            <h1 class="modal-title fs-5" id="crudModalLabel">Edit Role</h1>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">Role Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="{{ $role->name ?? '' }}" placeholder="Enter role name" required>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary form_submit_btn"
                data-url="{{ route('admin.roles.update', $role->id) }}">Update Role</button>
        </div>
    </div>
</form>
