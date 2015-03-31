$('document').ready(function(){
    // Not needed as long as maildomain hook is not implemented
    return;
    $('#management_method').on('select2-selecting', function(e){
        val = e.choice ? e.choice.id : e.target.selectedIndex;
        if(val == 0){
            //normal
            $('#toggle-management-normal').addClass('in');
            $('#toggle-registration-closed').addClass('in');
            $('#public_registration').trigger('change');
        }else if(val != undefined){
            //maildomain
            $('#toggle-management-normal').removeClass('in');
            $('#toggle-registration-closed').removeClass('in');
        }else{
            $('#toggle-management-normal').removeClass('in');
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
    $('#management_method').trigger('select2-selecting');
})