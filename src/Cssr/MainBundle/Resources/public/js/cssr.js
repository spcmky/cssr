(function(){


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

    //$('a.score-comment-popover').popover();

    $('#friday-report').dataTable({
        "bPaginate": false,
        "bLengthChange": false,
        "bFilter": false,
        "bSort": true,
        "bInfo": false,
        "bAutoWidth": false
    });

})();