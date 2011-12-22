jQuery.fn.uniform = function(settings) {
  settings = jQuery.extend({
    valid_class    : 'valid',
    invalid_class  : 'invalid',
    focused_class  : 'focused',
    holder_class   : 'ctrlHolder',
    field_selector : 'input, select, textarea'
  }, settings);
  
  return this.each(function() {
    var form = jQuery(this);
    
    // Focus specific control holder
    var focusControlHolder = function(element) {
      var parent = element.parent();
      
      while(typeof(parent) == 'object') {
        if(parent) {
          if(parent[0] && (parent[0].className.indexOf(settings.holder_class) >= 0)) {
            parent.addClass(settings.focused_class);
            return;
          } // if
        } // if
        parent = jQuery(parent.parent());
      } // while
    };
    
    // Select form fields and attach them higlighter functionality
    form.find(settings.field_selector).focus(function() {
      form.find('.' + settings.focused_class).removeClass(settings.focused_class);
      focusControlHolder(jQuery(this));
    }).blur(function() {
      form.find('.' + settings.focused_class).removeClass(settings.focused_class);
    });
  });
};

// Auto set on page load...
$(document).ready(function() {
  jQuery('form.uniForm').uniform();
});

function AR_ResetDates()
{
	if ($("#autoresponder:checked").val() == null) {
		$("form.uniForm select").each(
		 function(){
			$(this).val( $("#" + $(this).attr("id") + " option:first").val() );
		 }
		);
	}
}

function AR_SetNow()
{
	DateTime_SetValues('autoresponder_start_date');
	
	now = new Date();
	end_date = new Date(now.getFullYear(), now.getMonth(), now.getDate()+2, 0, 0);
	
	DateTime_SetValues('autoresponder_end_date', end_date);
}

function DateTime_SetValues(datetime_id, date_obj)
{
	var selects = ['day', 'month', 'year', 'hour', 'minute', 'second'];
	
	if ( (typeof(date_obj) == 'object') && (typeof(date_obj.getDate()) == 'number') ) {
		var now = date_obj;
	} else {
		var now = new Date();
	}
	
	jQuery.each(selects, function() {
		var unit_name = this.toString();
		var unit_value = '';
		
		switch(unit_name)
		{
			case 'day':
				unit_value = now.getDate();
				break;
			case 'month':
				unit_value = now.getMonth() + 1;
				if(unit_value < 10) unit_value = '0'+unit_value;
				break;
			case 'year':
				unit_value = now.getFullYear();
				break;
			case 'hour':
				unit_value = now.getHours();
				break;
			case 'minute':
				unit_value = Math.round(parseInt(now.getMinutes())/5)*5;
				break;
			case 'second':
				unit_value = now.getSeconds();
				break;
		}
		
		unit_obj = $("#"+ datetime_id + "_" + unit_name);
		if (unit_obj.val() !== null) {
			unit_obj.val(unit_value);
		}
	});
}