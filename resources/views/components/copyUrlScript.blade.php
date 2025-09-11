<script>
    $("body").on('click', '.copy_url', function(e) {
        e.preventDefault();
        let msg = $(this).data('message') ?? 'Copied';
        let url = $(this).data('href') ?? "";

        if (url == '') {
            url = $(this).closest('.copy-container').find('.code_url').val();
        }
        try {
            if (url) {
                navigator.clipboard.writeText(url).then(function() {
                    dispMessage(false, msg);
                }, function() {
                    dispMessage(true, 'Error while Copy');
                }).catch(p => {
                    dispMessage(true, 'Request denied');
                });
            } else {
                dispMessage(true, "No data found to copy");
            }
        } catch (error) {
            alert('Unable to copy');
        }
    });
</script>
