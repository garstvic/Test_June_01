$(document).ready(function(){
    $.ajax({
        method: 'POST',
        url: '/ajax/get-news',
        success: function(data){
            // $('.starter-template').html(data);
        }
    });
});