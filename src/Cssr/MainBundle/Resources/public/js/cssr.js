(function(){


    $('#periodSelector').on('change',function(){
        var uri = new URI(location.href).removeSearch("period").addSearch("period",$(this).val());
        location.href = uri.href();
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