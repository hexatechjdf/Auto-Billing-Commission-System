<table class="table" id="{{ $id }}">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Default</th>
            <th>Copy</th>
        </tr>
    </thead>
</table>

@push('script')
<script>
    $(document).ready(function() {
        let table = $('#{{ $id }}').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: '{{ $route }}',
            columns: [
                { data: 'id' },
                { data: 'name' },
                { data: 'radio', orderable: false, searchable: false },
                { data: 'copy', orderable: false, searchable: false }
            ]
        });

        // Reinitialize DataTable when its tab is shown
        // $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        //     if ($(e.target).attr('href') === '#{{ str_replace('-table', '', $id) }}') {
        //         table.columns.adjust().draw();
        //     }
        // });
    });
</script>
@endpush
