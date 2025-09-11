$(document).ready(function () {
    handleAjaxForm = function(formId, beforeSubmitCallback = null) {
        const $form = $(`#${formId}`);
        const $submitBtn = $form.find('button[type="submit"]');
        var $btnOldText = $submitBtn.text();

        $form.on('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            // Allow custom data manipulation before submission (e.g., radio buttons)
            if (beforeSubmitCallback && typeof beforeSubmitCallback === 'function') {
                beforeSubmitCallback(formData, $form);
            }

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function () {
                    $submitBtn.prop('disabled', true).text('Saving...');
                },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message);
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) { // Validation errors
                        const errors = xhr.responseJSON.errors;
                        $.each(errors, function(field, messages) {
                            $.each(messages, function(index, message) {
                                toastr.error(message);
                            });
                        });
                    } else { // Other errors
                        toastr.error(xhr.responseJSON.message || 'An unexpected error occurred.');
                    }
                },
                complete: function () {
                    $submitBtn.prop('disabled', false).text($btnOldText);
                }
            });
        });
    };
});
