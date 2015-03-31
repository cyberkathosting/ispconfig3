$('document').ready(function(){
    $('#use_muc_host').on('change', function(e){
        if($(this).is(':checked')){
            $('#toggle-use-muc').addClass('in');
            $('#use_pastebin').trigger('change');
            $('#use_http_archive').trigger('change');
        }else{
            $('#toggle-use-muc').removeClass('in');
        }
    });
    $('#use_pastebin').on('change', function(e){
        if($(this).is(':checked')){
            $('#toggle-use-pastebin').addClass('in');
        }else{
            $('#toggle-use-pastebin').removeClass('in');
        }
    });
    $('#use_http_archive').on('change', function(e){
        if($(this).is(':checked')){
            $('#toggle-use-archive').addClass('in');
        }else{
            $('#toggle-use-archive').removeClass('in');
        }
    });
    $('#use_muc_host').trigger('change');
})