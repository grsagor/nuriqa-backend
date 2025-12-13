const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true
});

function initDataTable(selector, columns, ajaxUrl, customOptions = {}) {
    const defaults = {
        processing: true,
        serverSide: true,
        responsive: true,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        pageLength: 10,
        ajax: {
            url: ajaxUrl,
            type: 'GET'
        },
        columns: columns,
        destroy: true
    };
    const options = $.extend(true, {}, defaults, customOptions);
    return $(selector).DataTable(options);
}

$(document).ready(function () {
    // Image preview functionality
    $(document).on('change', '.image-preview-input', function (e) {
        const input = this;
        const previewContainer = $(input).data('preview-container');
        
        if (!previewContainer) {
            console.error('Missing data-preview-container attribute');
            return;
        }
        
        const $container = $(previewContainer);
        
        // Clear previous previews
        $container.empty();
        
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
                        $container.empty();
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
})