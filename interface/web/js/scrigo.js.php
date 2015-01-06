<?php
include '../../lib/config.inc.php';
header('Content-Type: text/javascript; charset=utf-8'); // the config file sets the content type header so we have to override it here!
require_once '../../lib/app.inc.php';
$lang = (isset($_SESSION['s']['language']) && $_SESSION['s']['language'] != '')?$_SESSION['s']['language']:'en';
include_once ISPC_ROOT_PATH.'/web/strengthmeter/lib/lang/'.$lang.'_strengthmeter.lng';

$app->uses('ini_parser,getconf');
$server_config_array = $app->getconf->get_global_config();
?>
var pageFormChanged = false;
var tabChangeWarningTxt = '';
var tabChangeDiscardTxt = '';
var tabChangeWarning = false;
var tabChangeDiscard = false;
var requestsRunning = 0;
var indicatorPaddingH = -1;
var indicatorPaddingW = -1;
var indicatorCompleted = false;
var registeredHooks = new Array();
redirect = '';

function reportError(request) {
	/* Error reporting is disabled by default as some browsers like safari
	   sometimes throw errors when a ajax request is delayed even if the
	   ajax request worked. */

	/*alert(request);*/
}

function registerHook(name, callback) {
    if(!registeredHooks[name]) registeredHooks[name] = new Array();
    var newindex = registeredHooks[name].length;
    registeredHooks[name][newindex] = callback;
}

function callHook(name, params) {
    if(!registeredHooks[name]) return;
    for(var i = 0; i < registeredHooks[name].length; i++) {
        var callback = registeredHooks[name][i];
        callback(name, params);
    }
}

function resetFormChanged() {
    pageFormChanged = false;
}

function showLoadIndicator() {
    document.body.style.cursor = 'wait';

<?php
if($server_config_array['misc']['use_loadindicator'] == 'y'){
?>
    requestsRunning += 1;

    if(requestsRunning < 2) {
        var indicator = jQuery('#ajaxloader');
        if(indicator.length < 1) {
            indicator = jQuery('<div id="ajaxloader" style="display: none;"></div>');
            indicator.appendTo('body');
        }
        var parent = jQuery('#content');
        if(parent.length < 1) return;
        indicatorCompleted = false;

        var atx = parent.offset().left + 150; //((parent.outerWidth(true) - indicator.outerWidth(true)) / 2);
        var aty = parent.offset().top + 150;
        indicator.css( {'left': atx, 'top': aty } ).fadeIn('fast', function() {
            // check if loader should be hidden immediately
            indicatorCompleted = true;
            if(requestsRunning < 1) $(this).fadeOut('fast', function() { $(this).hide();});
        });
    }
<?php
}
?>
}

function hideLoadIndicator() {
    document.body.style.cursor = '';

    requestsRunning -= 1;
    if(requestsRunning < 1) {
        requestsRunning = 0; // just for the case...
        if(indicatorCompleted == true) jQuery('#ajaxloader').fadeOut('fast', function() { jQuery('#ajaxloader').hide(); } );
    }
}

function onAfterContentLoad(url, data) {
    if(!data) data = '';
    else data = '&' + data;
<?php
if($server_config_array['misc']['use_combobox'] == 'y'){
?>


    $('#pageContent').find("select:not(.chosen-select)").select2({
		placeholder: '',
		width: 'element',
		allowClear: true,
	}).on('change', function(e) {
            if (jQuery(".panel #Filter").length > 0) {
                jQuery(".panel #Filter").trigger('click');
            }
    });
    /* TODO: find a better way! */
    //$('.chosen-select').chosen({no_results_text: "<?php echo $wb['globalsearch_noresults_text_txt']; ?>", width: '300px'});
<?php
}
?>
    callHook('onAfterContentLoad', {'url': url, 'data': data });
}

function loadContentRefresh(pagename) {

  if(document.getElementById('refreshinterval').value > 0) {
	var pageContentObject2 = jQuery.ajax({	type: "GET",
											url: pagename,
											data: "refresh="+document.getElementById('refreshinterval').value,
											dataType: "html",
											beforeSend: function() {
												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
                                                hideLoadIndicator();
												jQuery('#pageContent').html(jqXHR.responseText);
                                                onAfterContentLoad(pagename, "refresh="+document.getElementById('refreshinterval').value);
                                                pageFormChanged = false;
											},
											error: function() {
                                                hideLoadIndicator();
												reportError('Ajax Request was not successful.'+pagename);
											}
										});
  	setTimeout( "loadContentRefresh('"+pagename+"&refresh="+document.getElementById('refreshinterval').value+"')", document.getElementById('refreshinterval').value*1000*60 );
  }
}

function capp(module, redirect) {
	var submitFormObj = jQuery.ajax({		type: "GET",
											url: "capp.php",
											data: "mod="+module+((redirect != undefined) ? '&redirect='+redirect : ''),
											dataType: "html",
											beforeSend: function() {
												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
												if(jqXHR.responseText != '') {
													if(jqXHR.responseText.indexOf('HEADER_REDIRECT:') > -1) {
														var parts = jqXHR.responseText.split(':');
														loadContent(parts[1]);
													} else if (jqXHR.responseText.indexOf('URL_REDIRECT:') > -1) {
														var newUrl= jqXHR.responseText.substr(jqXHR.responseText.indexOf('URL_REDIRECT:') + "URL_REDIRECT:".length);
														document.location.href = newUrl;
													} else {
														//alert(jqXHR.responseText);
													}
												}
												loadMenus();
                                                hideLoadIndicator();
											},
											error: function() {
                                                hideLoadIndicator();
												reportError('Ajax Request was not successful.'+module);
											}
									});
}

function submitLoginForm(formname) {
    //* Validate form. TODO: username and password with strip();
    var frm = document.getElementById(formname);
    var userNameObj = frm.username;
    if(userNameObj.value == ''){
        userNameObj.focus();
        return;
    }
    var passwordObj = frm.passwort;
    if(passwordObj.value == ''){
        passwordObj.focus();
        return;
    }

	$('#dummy_username').val(userNameObj.value);
	$('#dummy_passwort').val(passwordObj.value);
	$('#dummy_login_form').submit();

	var submitFormObj = jQuery.ajax({		type: "POST",
											url: "content.php",
											data: jQuery('#'+formname).serialize(),
											dataType: "html",
											beforeSend: function() {
												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
												if(jqXHR.responseText.indexOf('HEADER_REDIRECT:') > -1) {
													var parts = jqXHR.responseText.split(':');
													//alert(parts[1]);
													loadContent(parts[1]);
													//redirect = parts[1];
													//window.setTimeout('loadContent(redirect)', 1000);
												} else if (jqXHR.responseText.indexOf('LOGIN_REDIRECT:') > -1) {
													// Go to the login page
													document.location.href = 'index.php';
												} else {
													jQuery('#pageContent').html(jqXHR.responseText);
                                                    onAfterContentLoad('content.php', jQuery('#'+formname).serialize());
                                                    pageFormChanged = false;
												}
												loadMenus();
                                                hideLoadIndicator();
											},
											error: function() {
                                                hideLoadIndicator();
												reportError('Ajax Request was not successful.110');
											}
									});
	/*
	if(redirect != '') {
		loadContent(redirect);
		redirect = '';
	}
	document.getElementById('footer').innerHTML = 'Powered by <a href="http://www.ispconfig.org" target="_blank">ISPConfig</a>';
	*/

}

function submitForm(formname,target) {
	var submitFormObj = jQuery.ajax({		type: "POST",
											url: target,
											data: jQuery('#'+formname).serialize(),
											dataType: "html",
											beforeSend: function() {
												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
												if(jqXHR.responseText.indexOf('HEADER_REDIRECT:') > -1) {
													var parts = jqXHR.responseText.split(':');
													//alert(parts[1]);
													loadContent(parts[1]);
													//redirect = parts[1];
													//window.setTimeout('loadContent(redirect)', 1000);
												} else {
													jQuery('#pageContent').html(jqXHR.responseText);
                                                    onAfterContentLoad(target, jQuery('#'+formname).serialize());
                                                    pageFormChanged = false;
												}
                                                hideLoadIndicator();
											},
											error: function(jqXHR, textStatus, errorThrown) {
                                                hideLoadIndicator();
												var parts = jqXHR.responseText.split(':');
												reportError('Ajax Request was not successful. 111');
											}
									});
	/*
	if(redirect != '') {
		loadContent(redirect);
		redirect = '';
	}
	*/
}

function submitFormConfirm(formname,target,confirmation) {
	var successMessage = arguments[3];
	if(window.confirm(confirmation)) {
		var submitFormObj = jQuery.ajax({	type: "POST",
											url: target,
											data: jQuery('#'+formname).serialize(),
											dataType: "html",
											beforeSend: function() {
												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
												if(successMessage) alert(successMessage);
												if(jqXHR.responseText.indexOf('HEADER_REDIRECT:') > -1) {
													var parts = jqXHR.responseText.split(':');
													//alert(parts[1]);
													loadContent(parts[1]);
													//redirect = parts[1];
													//window.setTimeout('loadContent(redirect)', 1000);
												} else {
													jQuery('#pageContent').html(jqXHR.responseText);
                                                    onAfterContentLoad(target, jQuery('#'+formname).serialize());
                                                    pageFormChanged = false;
												}
                                                hideLoadIndicator();
											},
											error: function(jqXHR, textStatus, errorThrown) {
                                                hideLoadIndicator();
												var parts = jqXHR.responseText.split(':');
												reportError('Ajax Request was not successful. 111');
											}
									});
	}
}

function submitUploadForm(formname,target) {
	var handleResponse = function(loadedFrame) {
		var response, responseStr = loadedFrame.contentWindow.document.body.innerHTML;

		try {
			response = JSON.parse(responseStr);
		} catch(e) {
			response = responseStr;
		}
		var msg = '';
		var okmsg = jQuery('#OKMsg',response).html();
		if(okmsg){
			msg = '<div id="OKMsg">'+okmsg+'</div>';
		}
		var errormsg = jQuery('#errorMsg',response).html();
		if(errormsg){
			msg = msg+'<div id="errorMsg">'+errormsg+'</div>';
		}
		return msg;

    };

	var frame_id = 'ajaxUploader-iframe-' + Math.round(new Date().getTime() / 1000);
	jQuery('body').after('<iframe width="0" height="0" style="display:none;" name="'+frame_id+'" id="'+frame_id+'"/>');
	jQuery('input[type="file"]').closest("form").attr({target: frame_id, action: target}).submit();
	jQuery('#'+frame_id).load(function() {
        var msg = handleResponse(this);
		jQuery('#errorMsg').remove();
		jQuery('#OKMsg').remove();
		jQuery('input[name="id"]').before(msg);
		jQuery(this).remove();
      });

	/*
	if(redirect != '') {
		loadContent(redirect);
		redirect = '';
	}
	*/
}

function loadContent(pagename) {
  var params = arguments[1];
  var pageContentObject2 = jQuery.ajax({	type: "GET",
											url: pagename,
                                            data: (params ? params : null),
											dataType: "html",
											beforeSend: function() {
												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
												if(jqXHR.responseText.indexOf('HEADER_REDIRECT:') > -1) {
													var parts = jqXHR.responseText.split(':');
													loadContent(parts[1]);
												} else if (jqXHR.responseText.indexOf('URL_REDIRECT:') > -1) {
													var newUrl= jqXHR.responseText.substr(jqXHR.responseText.indexOf('URL_REDIRECT:') + "URL_REDIRECT:".length);
													document.location.href = newUrl;
												} else {
													//document.getElementById('pageContent').innerHTML = jqXHR.responseText;
													//var reponse = jQuery(jqXHR.responseText);
													//var reponseScript = reponse.filter("script");
													//jQuery.each(reponseScript, function(idx, val) { eval(val.text); } );

													jQuery('#pageContent').html(jqXHR.responseText);
                                                    onAfterContentLoad(pagename, (params ? params : null));
                                                    pageFormChanged = false;
												}
                                                hideLoadIndicator();
											},
											error: function() {
                                                hideLoadIndicator();
												reportError('Ajax Request was not successful. 113');
											}
									});
}


function loadInitContent() {
	var pageContentObject = jQuery.ajax({	type: "GET",
											url: "content.php",
											data: "s_mod=login&s_pg=index",
											dataType: "html",
											beforeSend: function() {
												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
												if(jqXHR.responseText.indexOf('HEADER_REDIRECT:') > -1) {
													var parts = jqXHR.responseText.split(":");
													loadContent(parts[1]);
												} else {
													jQuery('#pageContent').html(jqXHR.responseText);
                                                    onAfterContentLoad('content.php', "s_mod=login&s_pg=index");
                                                    pageFormChanged = false;
												}
                                                hideLoadIndicator();
											},
											error: function() {
                                                hideLoadIndicator();
												reportError('Ajax Request was not successful. 114');
											}
										});

  loadMenus();
  keepalive();
  setTimeout("setFocus()",1000);

}

function setFocus() {
	try {
		jQuery('form#pageForm').find('input[name="username"]').focus();
	} catch (e) {
	}
}


function loadMenus() {
  var sideNavObject = jQuery.ajax({			type: "GET",
											url: "nav.php",
											data: "nav=side",
											dataType: "html",
											beforeSend: function() {
												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
                                                hideLoadIndicator();
												jQuery('#sidebar').html(jqXHR.responseText);
											},
											error: function() {
                                                hideLoadIndicator();
												reportError('Ajax Request was not successful. 115');
											}
									});

  var topNavObject = jQuery.ajax({			type: "GET",
											url: "nav.php",
											data: "nav=top",
											dataType: "html",
											beforeSend: function() {
												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
                                                hideLoadIndicator();
												jQuery('#topnav-container').html(jqXHR.responseText);
											},
											error: function(o) {
                                                hideLoadIndicator();
												reportError('Ajax Request was not successful. 116');
											}
								});

}

function changeTab(tab,target,force) {
	if(requestsRunning > 0) return false;
	
	//document.forms[0].next_tab.value = tab;
	document.pageForm.next_tab.value = tab;

    var idel = jQuery('form#pageForm').find('[name="id"]');
    var id = null;
    if(idel.length > 0) id = idel.val();
    if(tabChangeDiscard == 'y' && !force) {
        if((idel.length < 1 || id) && (pageFormChanged == false || window.confirm(tabChangeDiscardTxt))) {
            var next_tab = tab;
            if(id) loadContent(target, {'next_tab': next_tab, 'id': id});
            else loadContent(target, {'next_tab': next_tab});
        } else {
            return false;
        }
    } else {
        if(id && tabChangeWarning == 'y' && pageFormChanged == true) {
            if(window.confirm(tabChangeWarningTxt)) {
                submitForm('pageForm', target);
            } else {
                var next_tab = tab;
                if(id) loadContent(target, {'next_tab': next_tab, 'id': id});
                else loadContent(target, {'next_tab': next_tab});
            }
        } else {
            submitForm('pageForm',target);
        }
    }
}

function del_record(link,confirmation) {
  if(window.confirm(confirmation)) {
          loadContent(link);
  }
}

function confirm_action(link,confirmation) {
  if(window.confirm(confirmation)) {
          loadContent(link);
  }
}

function loadContentInto(elementid,pagename) {
  var pageContentObject2 = jQuery.ajax({	type: "GET",
											url: pagename,
											dataType: "html",
											beforeSend: function() {
//												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
//                                                hideLoadIndicator();
												jQuery('#'+elementid).html(jqXHR.responseText);
											},
											error: function() {
//                                                hideLoadIndicator();
												reportError('Ajax Request was not successful. 118');
											}
										});
}

function loadOptionInto(elementid,pagename) {
	var pageContentObject2 = jQuery.ajax({	type: "GET",
											url: pagename,
											dataType: "html",
											beforeSend: function() {
//												showLoadIndicator();
											},
											success: function(data, textStatus, jqXHR) {
//                                                hideLoadIndicator();
												var teste = jqXHR.responseText;
												var elemente = teste.split('#');
												el=document.getElementById(elementid);
												el.innerHTML='';
												for (var i = 0; i < elemente.length; ++i){

													var foo2 = document.createElement("option");
													foo2.appendChild(document.createTextNode(elemente[i]));
													foo2.value=elemente[i];
													el.appendChild(foo2);
												}
											},
											error: function() {
//                                                hideLoadIndicator();
												reportError('Ajax Request was not successful. 119');
											}
										});
}

function keepalive() {
	var pageContentObject3 = jQuery.ajax({	type: "GET",
											url: "keepalive.php",
											dataType: "html",
											success: function(data, textStatus, jqXHR) {
												setTimeout( keepalive, 1000000 );
											},
											error: function() {
												reportError('Session expired. Please login again.');
											}
										});
  	//setTimeout( keepalive, 1000000 );
}


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
	document.getElementById("passBar").innerHTML = '<div style="float:left; height: 10px; padding:0px; background-color: ' + pass_messages[points]['color'] + '; width: ' + width + 'px;" />';
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

var new_tpl_add_id = 0;
function addAdditionalTemplate(){
    var tpl_add = jQuery('#template_additional').val();
    var addTemplate = jQuery('#tpl_add_select').val().split('|',2);
	var addTplId = addTemplate[0];
	var addTplText = addTemplate[1];
	if(addTplId > 0) {
        var newVal = tpl_add.split('/');
        new_tpl_add_id += 1;
        var delbtn = jQuery('<a href="#"></a>').attr('class', 'button icons16 icoDelete').click(function(e) {
            e.preventDefault();
            delAdditionalTemplate($(this).parent().attr('rel'));
        });
        newVal[newVal.length] = 'n' + new_tpl_add_id + ':' + addTplId;
	    jQuery('<li>' + addTplText + '</li>').attr('rel', 'n' + new_tpl_add_id).append(delbtn).appendTo('#template_additional_list ul');
	    jQuery('#template_additional').val(newVal.join('/'));
	    alert('additional template ' + addTplText + ' added to customer');
	} else {
	    alert('no additional template selcted');
	}
}

function delAdditionalTemplate(tpl_id){
    var tpl_add = jQuery('#template_additional').val();
	if(tpl_id) {
        // new style
		var $el = jQuery('#template_additional_list ul').find('li[rel="' + tpl_id + '"]').eq(0); // only the first
        var addTplText = $el.text();
        $el.remove();

		var oldVal = tpl_add.split('/');
		var newVal = new Array();
        for(var i = 0; i < oldVal.length; i++) {
            var tmp = oldVal[i].split(':', 2);
            if(tmp.length == 2 && tmp[0] == tpl_id) continue;
            newVal[newVal.length] = oldVal[i];
        }
        jQuery('#template_additional').val(newVal.join('/'));
		alert('additional template ' + addTplText + ' deleted from customer');
    } else if(tpl_add != '') {
        // old style
		var addTemplate = document.getElementById('tpl_add_select').value.split('|',2);
		var addTplId = addTemplate[0];
		var addTplText = addTemplate[1];

		jQuery('#template_additional_list ul').find('li:not([rel])').each(function() {
            var text = jQuery(this).text();
            if(text == addTplText) {
                jQuery(this).remove();
                return false;
            }
            return this;
        });

		var newVal = tpl_add;
        var repl = new RegExp('(^|\/)' + addTplId + '(\/|$)');
		newVal = newVal.replace(repl, '');
		newVal = newVal.replace('//', '/');
		jQuery('#template_additional').val(newVal);
		alert('additional template ' + addTplText + ' deleted from customer');
  } else {
  	alert('no additional template selcted');
  }

}

function getInternetExplorerVersion() {
    var rv = -1; // Return value assumes failure.
    if (navigator.appName == 'Microsoft Internet Explorer') {
        var ua = navigator.userAgent;
        var re = new RegExp("MSIE ([0-9]{1,}[\.0-9]{0,})");
        if (re.exec(ua) != null)
            rv = parseFloat(RegExp.$1);
    }
    return rv;
}

function password(minLength, special, num_special){
	minLength = minLength || 10;
	if(minLength < 8) minLength = 8;
	var maxLength = minLength + 5;
	var length = getRandomInt(minLength, maxLength);
	
	var alphachars = "abcdefghijklmnopqrstuvwxyz";
	var upperchars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    var numchars = "1234567890";
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
	var newPWField = oldPWField.clone();
	newPWField.attr('type', 'text').attr('id', 'tmp'+passwordFieldID).insertBefore(oldPWField);
	oldPWField.remove();
	var pword = password(<?php echo $min_password_length; ?>, false, 1);
	jQuery('#'+repeatPasswordFieldID).val(pword);
	newPWField.attr('id', passwordFieldID).val(pword).trigger('keyup').select();
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

jQuery(document).on("click", ".addPlaceholder", function(){
	var placeholderText = jQuery(this).text();
	var template = jQuery(this).siblings(':input');
	template.insertAtCaret(placeholderText);
});

jQuery(document).on("click", ".addPlaceholderContent", function(){
	var placeholderContentText = jQuery(this).find('.addPlaceholderContent').text();
	var template2 = jQuery(this).siblings(':input');
	template2.insertAtCaret(placeholderContentText);
});

jQuery.fn.extend({
	insertAtCaret: function(myValue){
		return this.each(function(i) {
			if (document.selection) {
				//For browsers like Internet Explorer
				this.focus();
				sel = document.selection.createRange();
				sel.text = myValue;
				this.focus();
			} else if (this.selectionStart || this.selectionStart == '0') {
				//For browsers like Firefox and Webkit based
				var startPos = this.selectionStart;
				var endPos = this.selectionEnd;
				var scrollTop = this.scrollTop;
				this.value = this.value.substring(0, startPos)+myValue+this.value.substring(endPos,this.value.length);
				this.focus();
				this.selectionStart = startPos + myValue.length;
				this.selectionEnd = startPos + myValue.length;
				this.scrollTop = scrollTop;
			} else {
				this.value += myValue;
				this.focus();
			}
		})
	}
});
