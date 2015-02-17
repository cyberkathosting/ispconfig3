$('document').ready(function(){
    $('#auth_method').on('select2-selecting', function(e){
        val = e.choice ? e.choice.id : e.target.selectedIndex;
        if(val == 2){
            //Mailbox
            $('#toggle-auth-internal').removeClass('in');
            $('#toggle-registration-closed').removeClass('in');
        }else if(val != undefined){
            $('#toggle-auth-internal').addClass('in');
            $('#public_registration').trigger('change');
        }else{
            $('#toggle-auth-internal').removeClass('in');
            $('#toggle-registration-closed').removeClass('in');
        }
    });
    $('#public_registration').on('change', function(e){
        if($(this).is(':checked')){
            $('#toggle-registration-closed').removeClass('in');
        }else{
            $('#toggle-registration-closed').addClass('in');
        }
    });
    $('#public_registration').trigger('change');
    $('#auth_method').trigger('select2-selecting');
})