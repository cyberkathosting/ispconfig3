<?php
include '../../lib/config.inc.php';
header('Content-Type: text/javascript; charset=utf-8'); // the config file sets the content type header so we have to override it here!
require_once '../../lib/app.inc.php';
$lang = (isset($_SESSION['s']['language']) && $_SESSION['s']['language'] != '')?$_SESSION['s']['language']:'en';
$lang = $app->functions->check_language($lang);
include_once ISPC_ROOT_PATH.'/web/strengthmeter/lib/lang/'.$lang.'_strengthmeter.lng';

$app->uses('ini_parser,getconf');
$server_config_array = $app->getconf->get_global_config();
?>

<?php
$min_password_length = 5;
if(isset($server_config_array['misc']['min_password_length'])) {
	$min_password_length = $app->functions->intval($server_config_array['misc']['min_password_length']);
}
?>
var pass_minimum_length = <?php echo $min_password_length; ?>;
var pass_messages = new Array();

var pass_message = new Array();
pass_message['text'] = "<?php echo $wb['password_strength_0_txt']?>";
pass_message['color'] = "#d0d0d0";
pass_messages[0] = pass_message;

var pass_message = new Array();
pass_message['text'] = "<?php echo $wb['password_strength_1_txt']?>";
pass_message['color'] = "red";
pass_messages[1] = pass_message;

var pass_message = new Array();
pass_message['text'] = "<?php echo $wb['password_strength_2_txt']?>";
pass_message['color'] = "yellow";
pass_messages[2] = pass_message;

var pass_message = new Array();
pass_message['text'] = "<?php echo $wb['password_strength_3_txt']?>";
pass_message['color'] = "#00ff00";
pass_messages[3] = pass_message;

var pass_message = new Array();
pass_message['text'] = "<?php echo $wb['password_strength_4_txt']?>";
pass_message['color'] = "green";
pass_messages[4] = pass_message;

var pass_message = new Array();
pass_message['text'] = "<?php echo $wb['password_strength_5_txt']?>";
pass_message['color'] = "green";
pass_messages[5] = pass_message;

var special_chars = "`~!@#$%^&*()_+|\=-[]}{';:/?.>,<\" ";

function pass_check(password) {
	var length = password.length;
	var points = 0;
	if (length < pass_minimum_length) {
		pass_result(0);
		return;
	}

	if (length < 5) {
		pass_result(1);
		return;
	}
	
	var different = 0;
	
	if (pass_contains(password, "abcdefghijklnmopqrstuvwxyz")) {
		different += 1;
	}
	
	if (pass_contains(password, "ABCDEFGHIJKLNMOPQRSTUVWXYZ")) {
		points += 1;
		different += 1;
	}

	if (pass_contains(password, "0123456789")) {
		points += 1;
		different += 1;
	}

	if (pass_contains(password, special_chars)) {
		points += 1;
		different += 1;
	}

	if (points == 0 || different < 3) {
		if (length >= 5 && length <=6) {
			pass_result(1);
		} else if (length >= 7 && length <=8) {
			pass_result(2);
		} else {
			pass_result(3);
		}
	} else if (points == 1) {
		if (length >= 5 && length <=6) {
			pass_result(2);
		} else if (length >= 7 && length <=10) {
			pass_result(3);
		} else {
			pass_result(4);
		}
	} else if (points == 2) {
		if (length >= 5 && length <=8) {
			pass_result(3);
		} else if (length >= 9 && length <=10) {
			pass_result(4);
		} else {
			pass_result(5);
		}
	} else if (points == 3) {
		if (length >= 5 && length <=6) {
			pass_result(3);
		} else if (length >= 7 && length <=8) {
			pass_result(4);
		} else {
			pass_result(5);
		}
	} else if (points >= 4) {
		if (length >= 5 && length <=6) {
			pass_result(4);
		} else {
			pass_result(5);
		}
	}
}



function pass_result(points, message) {
	if (points == 0) {
		width = 10;
	} else {
		width = points*20;
	}
	document.getElementById("passBar").innerHTML = '<div style="background-color: ' + pass_messages[points]['color'] + '; width: ' + width + 'px;" />';
	document.getElementById("passText").innerHTML = pass_messages[points]['text'];
}
function pass_contains(pass, check) {
	for (i = 0; i < pass.length; i++) {
		if (check.indexOf(pass.charAt(i)) > -1) {
			return true;
		}
	}
	return false;
}



function password(minLength, special, num_special){
	minLength = minLength || 10;
	if(minLength < 8) minLength = 8;
	var maxLength = minLength + 5;
	var length = getRandomInt(minLength, maxLength);
	
	var alphachars = "abcdefghijkmnopqrstuvwxyz";
	var upperchars = "ABCDEFGHJKLMNPQRSTUVWXYZ";
    var numchars = "23456789";
    var specialchars = "!@#_";
	
	if(num_special == undefined) num_special = 0;
	if(special != undefined && special == true) {
		num_special = Math.floor(Math.random() * (length / 4)) + 1;
	}
	var numericlen = getRandomInt(1, 2);
	var alphalen = length - num_special - numericlen;
	var upperlen = Math.floor(alphalen / 2);
	alphalen = alphalen - upperlen;
	var password = "";
	
	for(i = 0; i < alphalen; i++) {
		password += alphachars.charAt(Math.floor(Math.random() * alphachars.length));
	}
	
	for(i = 0; i < upperlen; i++) {
		password += upperchars.charAt(Math.floor(Math.random() * upperchars.length));
	}
	
	for(i = 0; i < num_special; i++) {
		password += specialchars.charAt(Math.floor(Math.random() * specialchars.length));
	}
	
	for(i = 0; i < numericlen; i++) {
		password += numchars.charAt(Math.floor(Math.random() * numchars.length));
	}
	
	password = password.split('').sort(function() { return 0.5 - Math.random(); }).join('');
	
	return password;
}

<?php
$min_password_length = 10;
if(isset($server_config_array['misc']['min_password_length'])) {
	$min_password_length = $app->functions->intval($server_config_array['misc']['min_password_length']);
}
?>

function generatePassword(passwordFieldID, repeatPasswordFieldID){
	var oldPWField = jQuery('#'+passwordFieldID);
	oldPWField.removeAttr('readonly');
	var newPWField = oldPWField.clone();
	newPWField.attr('type', 'text').attr('id', 'tmp'+passwordFieldID).insertBefore(oldPWField);
	oldPWField.remove();
	var pword = password(<?php echo $min_password_length; ?>, false, 1);
	jQuery('#'+repeatPasswordFieldID).val(pword);
	jQuery('#'+repeatPasswordFieldID).removeAttr('readonly');
	newPWField.attr('id', passwordFieldID).val(pword).trigger('keyup').select();
	newPWField.unbind('keyup').on('keyup', function(e) {
		if($(this).val() != pword) {
			var pos = $(this).getCursorPosition();
			$(this).attr('type', 'password').unbind('keyup').setCursorPosition(pos);
		}
	});
}

var funcDisableClick = function(e) { e.preventDefault(); return false; };

function checkPassMatch(pwField1,pwField2){
    var rpass = jQuery('#'+pwField2).val();
    var npass = jQuery('#'+pwField1).val();
    if(npass!= rpass) {
		jQuery('#confirmpasswordOK').hide();
        jQuery('#confirmpasswordError').show();
		jQuery('button.positive').attr('disabled','disabled');
        jQuery('.tabbox_tabs ul li a').each(function() {
            var $this = $(this);
            $this.data('saved_onclick', $this.attr('onclick'));
            $this.removeAttr('onclick');
            $this.click(funcDisableClick);
        });
        return false;
    } else {
		jQuery('#confirmpasswordError').hide();
        jQuery('#confirmpasswordOK').show();
		jQuery('button.positive').removeAttr('disabled');
		jQuery('.tabbox_tabs ul li a').each(function() {
            var $this = $(this);
            $this.unbind('click', funcDisableClick);
            if($this.data('saved_onclick') && !$this.attr('onclick')) $this.attr('onclick', $this.data('saved_onclick'));
        });
    }
}

function getRandomInt(min, max){
    return Math.floor(Math.random() * (max - min + 1)) + min;
}
