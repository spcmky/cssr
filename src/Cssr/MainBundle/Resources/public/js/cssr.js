(function(){


    $('#periodSelector').on('change',function(){
        var uri = new URI(location.href).removeSearch("period").addSearch("period",$(this).val());
        location.href = uri.href();
    });

})();