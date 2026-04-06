/**
 * Admin product create/edit modal (loaded via AJAX into #crudModal).
 * Inline <script> in injected HTML does not run; handlers must live here.
 */
function syncAdminProductHajraFree() {
    const $m = $('#crudModal');
    const $free = $m.find('#is_free_hajra');
    if (!$free.length) {
        return;
    }

    const typeEl = $m.find('#type').get(0);
    const catalogIsHajra = !typeEl || typeEl.value === 'hajra';
    const on = catalogIsHajra && $free.is(':checked');

    const $paidHolder = $m.find('#hajraPaidPricingHolder');
    const $donationNote = $m.find('#hajraDonationFreeNote');
    const $donationControls = $m.find('#hajraDonationControls');
    const $price = $m.find('#price');
    const $discEn = $m.find('#discount_enabled');
    const $platDon = $m.find('#platform_donation');
    const $discFields = $m.find('#discountFields');
    const $donFields = $m.find('#donationFields');

    if ($paidHolder.length) {
        $paidHolder.toggle(!on);
    }
    if ($donationNote.length) {
        $donationNote.toggleClass('d-none', !on);
    }
    if ($donationControls.length) {
        $donationControls.toggle(!on);
    }

    if ($price.length) {
        $price.prop('required', !on);
        $price.prop('readOnly', on);
        $price.toggleClass('bg-light', on);
        if (on) {
            $price.val('0');
        }
    }

    if ($discEn.length) {
        $discEn.prop('disabled', on);
        if (on) {
            $discEn.prop('checked', false);
        }
    }

    if ($discFields.length) {
        const showDisc = !on && $discEn.length && $discEn.is(':checked');
        $discFields.toggle(showDisc);
    }

    if ($platDon.length) {
        $platDon.prop('disabled', on);
        if (on) {
            $platDon.prop('checked', false);
        }
    }

    if ($donFields.length) {
        const showDon = !on && $platDon.length && $platDon.is(':checked');
        $donFields.toggle(showDon);
    }
}

const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true
});

function initDataTable(selector, columns, ajaxUrl, customOptions = {}) {
    // Destroy any existing DataTable instance
    if ($.fn.DataTable.isDataTable(selector)) {
        $(selector).DataTable().destroy();
    }
    
    const defaults = {
        processing: true,
        serverSide: true,
        responsive: true,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        pageLength: 10,
        ajax: {
            url: ajaxUrl,
            type: 'GET',
        },
        columns: columns,
        order: [],
    };
    const options = $.extend(true, {}, defaults, customOptions);
    return $(selector).DataTable(options);
}

$(document).ready(function () {
    $('#crudModal').on('shown.bs.modal', function () {
        syncAdminProductHajraFree();
    });

    $(document).on('change', '#crudModal #condition', function () {
        const $m = $('#crudModal');
        const $washed = $m.find('#washedSection');
        if (this.value === 'used') {
            $washed.show();
        } else {
            $washed.hide();
            $m.find('#washed_no').prop('checked', true);
        }
    });

    $(document).on('change', '#crudModal #discount_enabled', function () {
        const $m = $('#crudModal');
        $m.find('#discountFields').toggle($(this).is(':checked'));
        syncAdminProductHajraFree();
    });

    $(document).on('change', '#crudModal #platform_donation', function () {
        const $m = $('#crudModal');
        $m.find('#donationFields').toggle($(this).is(':checked'));
        syncAdminProductHajraFree();
    });

    $(document).on('click', '#crudModal .donation-preset', function (e) {
        e.preventDefault();
        const $m = $('#crudModal');
        const value = $(this).data('value');
        $m.find('#donation_percentage').val(value);
        $m.find('.donation-preset').removeClass('active');
        $(this).addClass('active');
    });

    $(document).on('change', '#crudModal #type', function () {
        const $m = $('#crudModal');
        const isHajra = this.value === 'hajra';
        $m.find('#hajraFreeRow').toggle(isHajra);
        if (!isHajra) {
            $m.find('#is_free_hajra').prop('checked', false);
        }
        syncAdminProductHajraFree();
    });

    $(document).on('change', '#crudModal #is_free_hajra', function () {
        syncAdminProductHajraFree();
    });

    // Image preview functionality
    $(document).on('change', '.image-preview-input', function (e) {
        const input = this;
        const previewContainer = $(input).data('preview-container');
        
        if (!previewContainer) {
            console.error('Missing data-preview-container attribute');
            return;
        }
        
        const $container = $(previewContainer);
        
        // If a new image is selected, reset the remove_image flag
        $('#remove_image').val('0');
        
        // Remove any removal message
        $('#imageRemovalMessage').remove();
        
        // Show the current image preview if it was hidden
        $('.current-image-preview').show();
        
        // Clear previous previews
        $container.find('.preview-wrapper').remove();
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function (e) {
                const img = $('<img>', {
                    src: e.target.result,
                    class: 'preview-image img-thumbnail',
                    alt: 'Preview'
                });
                
                const removeBtn = $('<button>', {
                    type: 'button',
                    class: 'btn btn-sm btn-danger preview-remove-btn',
                    html: '<i class="fas fa-times"></i>',
                    click: function () {
                        $container.find('.preview-wrapper').remove();
                        $(input).val('');
                    }
                });
                
                const wrapper = $('<div>', {
                    class: 'preview-wrapper position-relative d-inline-block'
                }).append(img).append(removeBtn);
                
                $container.append(wrapper);
            };
            
            reader.readAsDataURL(input.files[0]);
        }
    });
    
    // Handle remove image button click
    $(document).on('click', '#removeCurrentImage', function() {
        const removeInput = $('#remove_image');
        const imageInput = $('#image');
        const currentImagePreview = $('.current-image-preview');
        
        // Set the hidden input value to 1 (remove image)
        removeInput.val('1');
        
        // Clear file input
        imageInput.val('');
        
        // Hide the current image preview
        currentImagePreview.hide();
        
        // Show removal message
        currentImagePreview.after(
            $('<div>', {
                id: 'imageRemovalMessage',
                class: 'alert alert-warning mt-2',
                text: 'Current image will be removed upon update. Click the undo button to restore.'
            }).append(
                $('<button>', {
                    type: 'button',
                    class: 'btn btn-sm btn-outline-secondary ms-2',
                    text: 'Undo',
                    click: function() {
                        // Restore the image preview
                        currentImagePreview.show();
                        // Remove the removal message
                        $('#imageRemovalMessage').remove();
                        // Reset the hidden input value
                        removeInput.val('0');
                    }
                })
            )
        );
    });
    
    $(document).on('click', '.open_modal_btn', function (e) {
        e.preventDefault();
        const url = $(this).data('url');
        const parent = $(this).data('modal-parent');   // ex: #roleModal
        if (!url || !parent) {
            console.error('Missing data-url or data-modal-parent');
            return;
        }
        $.ajax({
            url: url,
            type: 'GET',
            success: function (response) {
                if (response.success) {
                    $(parent).html(response.html);
                    $(parent).modal('show');
                    syncAdminProductHajraFree();
                } else {
                    console.error(response.message || 'Unknown error');
                    alert('Failed to load modal content');
                    return;
                }
            },
            error: function (xhr) {
                console.error(xhr.responseText);
                alert('Failed to load modal content');
            }
        });
    });

    $(document).on('click', '.form_submit_btn', function (e) {
        e.preventDefault();

        const url = $(this).data('url');

        // Get the nearest form
        let form = $(this).closest('form');

        if (!form.length) {
            console.error('No form found for .form_submit_btn');
            return;
        }

        // Build FormData (supports files)
        let formData = new FormData(form[0]);

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,

            beforeSend: function () {
                form.find('small.error-text').text('');
            },

            success: function (res) {
                if (res.success) {

                    // close modal if needed
                    if (res.close_modal !== false) {
                        $('#crudModal').modal('hide');
                    }

                    // reload datatable if applicable
                    if (typeof $('#datatable').DataTable === 'function') {
                        $('#datatable').DataTable().ajax.reload(null, false);
                    }

                    // show success toast
                    Toast.fire({
                        icon: 'success',
                        title: res.message || 'Operation successful'
                    });
                } else {
                    console.log(res.message || 'Unknown error');
                }
            },

            error: function (err) {
                if (err.status === 422) {

                    // Remove old errors
                    form.find('small.validation-error').remove();

                    let errors = err.responseJSON.errors;

                    $.each(errors, function (key, messages) {

                        let input = form.find('[name="' + key + '"]');

                        // Append message after the input
                        input.after(
                            '<small class="text-danger validation-error d-block mt-1">' + messages[0] + '</small>'
                        );
                    });

                    Toast.fire({ icon: 'error', title: err.responseJSON.message });

                } else {
                    console.error(err.responseText);
                    Toast.fire({ icon: 'error', title: 'Request failed' });
                }
            }
        });
    });

    $(document).on('click', '.crud_delete_btn', function (e) {
        e.preventDefault();

        const url = $(this).data('url');

        if (!url) {
            Toast.fire({ icon: 'error', title: 'Delete URL missing' });
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {

            if (result.isConfirmed) {

                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },

                    success: function (res) {
                        if (res.success) {
                            Toast.fire({ icon: 'success', title: res.message || 'Deleted successfully' });

                            if (typeof $('#datatable').DataTable === 'function') {
                                $('#datatable').DataTable().ajax.reload(null, false);
                            }
                        } else {
                            Toast.fire({ icon: 'error', title: res.message || 'Delete failed' });
                        }
                    },

                    error: function (err) {
                        console.error(err.responseText);
                        Toast.fire({ icon: 'error', title: 'Delete request failed' });
                    }
                });
            }
        });
    });

    // Handle action buttons (approve, reject, etc.)
    $(document).on('click', '.crud_action_btn', function (e) {
        e.preventDefault();

        const url = $(this).data('url');
        const action = $(this).data('action');

        if (!url) {
            Toast.fire({ icon: 'error', title: 'Action URL missing' });
            return;
        }

        const actionText = action === 'approve' ? 'approve' : 'reject';
        const confirmText = action === 'approve' ? 'approve this item' : 'reject this item';

        Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to ${confirmText}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: action === 'approve' ? '#28a745' : '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: `Yes, ${actionText} it!`,
            cancelButtonText: 'Cancel'
        }).then((result) => {

            if (result.isConfirmed) {

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },

                    success: function (res) {
                        if (res.success) {
                            Toast.fire({ 
                                icon: 'success', 
                                title: res.message || `${actionText.charAt(0).toUpperCase() + actionText.slice(1)}d successfully` 
                            });

                            if (typeof $('#datatable').DataTable === 'function') {
                                $('#datatable').DataTable().ajax.reload(null, false);
                            }
                        } else {
                            Toast.fire({ icon: 'error', title: res.message || `${actionText.charAt(0).toUpperCase() + actionText.slice(1)} failed` });
                        }
                    },

                    error: function (err) {
                        console.error(err.responseText);
                        Toast.fire({ icon: 'error', title: `${actionText.charAt(0).toUpperCase() + actionText.slice(1)} request failed` });
                    }
                });
            }
        });
    });
})