<script>
    function copy(url) {

        try {
            navigator.clipboard.writeText(url).then(x => {
                dispMessage(false, 'Copied', timeout = 10000)
            })
        } catch (error) {

        }
    }
    // Initialize the DataTable with the 'Designation' column using the render feature
    let table = $('#lookupDatatable').DataTable({
        columns: [{
                data: 'name'
            },

            {
                data: 'id'
            },

            {
                data: 'email'
            },
            {
                data: 'id'
            }, { // Designation column (merged position and office)
                data: 'action',
                render: function(data, type, row, meta) {
                    return row.already_exist ? '-' :
                        `<span class="btn btn-warning btn-sm row-${meta.row}"   onclick="event.preventDefault(); alertMsg('${row.id}','${meta.row}')"><i class="bi bi-award"></i> Set Locations</span>`;
                }
            }
        ]
    });
</script>