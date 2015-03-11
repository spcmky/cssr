(function(){

    $('.selectpicker').selectpicker();

    $('#periodSelector').on('change',function(){
        var uri = new URI(location.href).removeSearch("period").addSearch("period",$(this).val());
        location.href = uri.href();
    });

    $('#periodRangeSelector').on('submit',function(){

        var periodStart = moment($('#periodSelectorStart').val(),'YYYY-MM-DD');
        var periodEnd = moment($('#periodSelectorEnd').val(),'YYYY-MM-DD');

        $(this).find('div.alert').remove();

        if ( periodStart.isAfter(periodEnd) ) {

            var message = '<div class="alert alert-danger">';
            message += '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>';
            message += '<strong>Error!</strong> Start week cannot be after End week.';
            message += '</div>';

            $(this).prepend(message);
        } else {
            var uri = new URI(location.href);
            uri.removeSearch("periodStart").addSearch("periodStart",periodStart.format('YYYY-MM-DD'));
            uri.removeSearch("periodEnd").addSearch("periodEnd",periodEnd.format('YYYY-MM-DD'));
            location.href = uri.href();
        }

        return false;
    });

    $('#checkAllStudents').on('change',function(){
        $("input[name='students']").prop('checked', this.checked);
    });

    $('#caseloadButtonGenerate').on('click',function(){
        var checked = $("input[name='students']:checked");
        var count = checked.length;
        if ( !count ) {
            return;
        }

        var students = [];
        for ( var i = 0; i < count; i++ ) {
            students.push($(checked[i]).val());
        }

        var uri = new URI($(this).attr('data-url'));
        uri.removeSearch("students").addSearch("students",students.join());
        location.href = uri.href();
    });

    var reportTable = $('#friday-report').DataTable({
        "bPaginate": false,
        "bLengthChange": false,
        "bFilter": false,
        "bSort": true,
        "bInfo": false,
        "bAutoWidth": false,
        "bProcessing": true,
        "columnDefs": [{
            "searchable": false,
            "orderable": false,
            "targets": 0
        }],
        "order": [[ 1, 'asc' ]]
    });

    if ( typeof reportTable != "undefined" ) {
        reportTable.on('order.dt search.dt', function () {
            reportTable.column(0, {search:'applied', order:'applied'}).nodes().each( function (cell, i) {
                cell.innerHTML = i+1;
            });
        }).draw();
    }

    var sortableReportTable = $('table.sortable-report').DataTable({
        "bPaginate": false,
        "bLengthChange": false,
        "bFilter": false,
        "bSort": true,
        "bInfo": false,
        "bAutoWidth": false,
        "bProcessing": true,
        "columnDefs": [{
            "searchable": false,
            "orderable": false,
            "targets": 0
        }],
        "order": [[ 1, 'asc' ]]
    });

    if ( typeof sortableReportTable != "undefined" ) {
        sortableReportTable.on('order.dt search.dt', function () {
            sortableReportTable.column(0, {search:'applied', order:'applied'}).nodes().each( function (cell, i) {
                cell.innerHTML = i+1;
            });
        }).draw();
    }

    $('table.student-list').dataTable({
        //"bJQueryUI": true,
        "bPaginate": false,
        "bLengthChange": false,
        "bFilter": true,
        "bSort": true,
        "bInfo": false,
        "bAutoWidth": false,
        "bProcessing": true,
        "sDom":"flrtip"
    });

    $('table.staff-list').dataTable({
        //"bJQueryUI": true,
        "bPaginate": false,
        "bLengthChange": false,
        "bFilter": true,
        "bSort": true,
        "bInfo": false,
        "bAutoWidth": false,
        "bProcessing": true,
        "sDom":"flrtip"
    });

    $('#admin-list').dataTable({
        "bJQueryUI": true,
        "bPaginate": false,
        "bLengthChange": false,
        "bFilter": false,
        "bSort": true,
        "bInfo": false,
        "bAutoWidth": false,
        "bProcessing": true

    });

})();