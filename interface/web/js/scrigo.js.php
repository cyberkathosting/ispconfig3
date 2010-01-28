<?php
	session_start();
	include('../../lib/config.inc.php');
	include_once(ISPC_ROOT_PATH.'/web/strengthmeter/lib/lang/'.$_SESSION['s']['language'].'_strengthmeter.lng');
?>

redirect = '';

function loadContentRefresh(pagename) {
	var pageContentCallbackRefresh = {
		success: function(o) {
			document.getElementById('pageContent').innerHTML = o.responseText;
		},
		failure: function(o) {
			alert('Ajax Request was not successful.'+pagename);
		}
	}
	
  if(document.getElementById('refreshinterval').value > 0) {
  	var pageContentObject2 = YAHOO.util.Connect.asyncRequest('GET', pagename+"&refresh="+document.getElementById('refreshinterval').value, pageContentCallbackRefresh);
  	setTimeout( "loadContentRefresh('"+pagename+"&refresh="+document.getElementById('refreshinterval').value+"')", document.getElementById('refreshinterval').value*1000 );
  }
}

function capp(module) {
	var cappCallback = {
		success: function(o) {
			if(o.responseText != '') {
				if(o.responseText.indexOf('HEADER_REDIRECT:') > -1) {
					var parts = o.responseText.split(':');
					loadContent(parts[1]);
				} else {
					alert(o.responseText);
				}
			}
			loadMenus();
		},
		failure: function(o) {
			alert('Ajax Request was not successful.');
		}
	}
	var submitFormObj = YAHOO.util.Connect.asyncRequest('GET', 'capp.php?mod='+module, cappCallback);
}

function submitLoginForm(formname) {
	
	var submitFormCallback = {
		success: function(o) {
			if(o.responseText.indexOf('HEADER_REDIRECT:') > -1) {
				var parts = o.responseText.split(':');
				//alert(parts[1]);
				loadContent(parts[1]);
				//redirect = parts[1];
				//window.setTimeout('loadContent(redirect)', 1000);
			} else if (o.responseText.indexOf('LOGIN_REDIRECT:') > -1) {
				// Go to the login page
				document.location.href = 'index.php';
			} else {
				document.getElementById('pageContent').innerHTML = o.responseText;
			}
			loadMenus();
		},
		failure: function(o) {
			alert('Ajax Request was not successful.');
		}
	}
	
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
	YAHOO.util.Connect.setForm(formname);
	var submitFormObj = YAHOO.util.Connect.asyncRequest('POST', 'content.php', submitFormCallback);
	/*
	if(redirect != '') {
		loadContent(redirect);
		redirect = '';
	}
	document.getElementById('footer').innerHTML = 'Powered by <a href="http://www.ispconfig.org" target="_blank">ISPConfig</a>';
	*/
	
}

function submitForm(formname,target) {
	
	var submitFormCallback = {
		success: function(o) {
			if(o.responseText.indexOf('HEADER_REDIRECT:') > -1) {
				var parts = o.responseText.split(':');
				//alert(parts[1]);
				loadContent(parts[1]);
				//redirect = parts[1];
				//window.setTimeout('loadContent(redirect)', 1000);
			} else {
				document.getElementById('pageContent').innerHTML = o.responseText;
			}
		},
		failure: function(o) {
			var parts = o.responseText.split(':');
			alert('Ajax Request was not successful. '+parts[1]);
		}
	}
	
	YAHOO.util.Connect.setForm(formname);
	var submitFormObj = YAHOO.util.Connect.asyncRequest('POST', target, submitFormCallback);
	/*
	if(redirect != '') {
		loadContent(redirect);
		redirect = '';
	}
	*/
}

function submitUploadForm(formname,target) {
	
	var submitFormCallback = {
		success: function(o) {
			if(o.responseText.indexOf('HEADER_REDIRECT:') > -1) {
				var parts = o.responseText.split(':');
				//alert(parts[1]);
				loadContent(parts[1]);
				//redirect = parts[1];
				//window.setTimeout('loadContent(redirect)', 1000);
			} else {
				document.getElementById('pageContent').innerHTML = o.responseText;
			}
		},
		upload: function(o) {
        	if(o.responseText.indexOf('HEADER_REDIRECT:') > -1) {
				var parts = o.responseText.split(':');
				//alert(parts[1]);
				loadContent(parts[1]);
				//redirect = parts[1];
				//window.setTimeout('loadContent(redirect)', 1000);
			} else {
				document.getElementById('pageContent').innerHTML = o.responseText;
			}
        },
		failure: function(o) {
			alert('Ajax Request was not successful. 1');
		}
	}
	
	YAHOO.util.Connect.setForm(formname,true);
	var submitFormObj = YAHOO.util.Connect.asyncRequest('POST', target, submitFormCallback);
	/*
	if(redirect != '') {
		loadContent(redirect);
		redirect = '';
	}
	*/
}

function loadContent(pagename) {
	var pageContentCallback2 = {
		success: function(o) {
			if(o.responseText.indexOf('HEADER_REDIRECT:') > -1) {
				var parts = o.responseText.split(':');
				loadContent(parts[1]);
			} else if (o.responseText.indexOf('URL_REDIRECT:') > -1) {
				var newUrl= o.responseText.substr(o.responseText.indexOf('URL_REDIRECT:') + "URL_REDIRECT:".length);
				document.location.href = newUrl;
			} else {
				document.getElementById('pageContent').innerHTML = o.responseText;
			}
		},
		failure: function(o) {
			alert('Ajax Request was not successful.');
		}
	}
	

  var pageContentObject2 = YAHOO.util.Connect.asyncRequest('GET', pagename, pageContentCallback2);
}


function loadInitContent() {

  var pageContentCallback = {
		success: function(o) {
			if(o.responseText.indexOf('HEADER_REDIRECT:') > -1) {
				var parts = o.responseText.split(":");
				loadContent(parts[1]);
			} else {
				document.getElementById('pageContent').innerHTML = o.responseText;
			}
			
			/*
			var items = document.getElementsByTagName('input');
			for(i=0;i<items.length;i++) {
				//var oButton = new YAHOO.widget.Button(items[i].id);
				if(items[i].type == 'button') {
					//alert(items[i].id);
					var oButton = new YAHOO.widget.Button(items[i].id);
					oButton.addListener("click",submitLoginForm);
				}
			}
			//var oButton = new YAHOO.widget.Button("submit");
			*/
		},
		failure: function(o) {
			alert('Ajax Request was not successful.');
		}
	}
	
  var pageContentObject = YAHOO.util.Connect.asyncRequest('GET', 'content.php?s_mod=login&s_pg=index', pageContentCallback);
  
  loadMenus();
  keepalive();
  setTimeout("setFocus()",1000);

}

function setFocus() {
/*
	var flag=false;
		for(z=0;z<document.forms.length;z++) {
			var form = document.forms[z];
			var elements = form.elements;
			for (var i=0;i<elements.length;i++) {
				var element = elements[i];
				if(element.type == 'text' &&
					!element.readOnly &&
					!element.disabled) {
						element.focus();
						flag=true;
						break;
					}
			}
			if(flag)break;
		}
*/
  document.pageForm.username.focus();
}


function loadMenus() {
	
	var sideNavCallback = {
		success: function(o) {
			document.getElementById('sideNav').innerHTML = o.responseText;
		},
		failure: function(o) {
			alert('Ajax Request was not successful.');
		}
	}
	
  var sideNavObject = YAHOO.util.Connect.asyncRequest('GET', 'nav.php?nav=side', sideNavCallback);
	
	var topNavCallback = {
		success: function(o) {
			document.getElementById('topNav').innerHTML = o.responseText;
		},
		failure: function(o) {
			alert('Ajax Request was not successful.');
		}
	}
	
  var topNavObject = YAHOO.util.Connect.asyncRequest('GET', 'nav.php?nav=top', topNavCallback);

}

function changeTab(tab,target) {
	//document.forms[0].next_tab.value = tab;
	document.pageForm.next_tab.value = tab;
	submitForm('pageForm',target);
}



function reportError(request)
	{
		alert('Sorry. There was an error.');
	}
	
function del_record(link,confirmation) {
  if(window.confirm(confirmation)) {
          loadContent(link);
  }
}

function loadContentInto(elementid,pagename) {
	var itemContentCallback = {
		success: function(o) {
			document.getElementById(elementid).innerHTML = o.responseText;
		},
		failure: function(o) {
			alert('Ajax Request was not successful.');
		}
	}
	

  var pageContentObject2 = YAHOO.util.Connect.asyncRequest('GET', pagename, itemContentCallback);
}

function loadOptionInto(elementid,pagename) {
	var itemContentCallback = {
		success: function(o) {
			var teste = o.responseText;
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
		failure: function(o) {
		alert('Ajax Request was not successful.');
		}
	}
	var pageContentObject2 = YAHOO.util.Connect.asyncRequest('GET', pagename, itemContentCallback);
}

function keepalive() {
	var pageContentCallbackKeepalive = {
		success: function(o) {
			setTimeout( keepalive, 1000000 );
		},
		failure: function(o) {
			alert('Sorry. There was an error.');
		}
	}
	
  	var pageContentObject3 = YAHOO.util.Connect.asyncRequest('GET', 'keepalive.php', pageContentCallbackKeepalive);
  	//setTimeout( keepalive, 1000000 );
}



var pass_minimum_length = 5;
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
	
	if (pass_contains(password, "ABCDEFGHIJKLNMOPQRSTUVWXYZ")) {
		points += 1;
	}
	
	if (pass_contains(password, "0123456789")) {
		points += 1;
	}
	
	if (pass_contains(password, "`~!@#$%^&*()_+|\=-[]}{';:/?.>,<\" ")) {
		points += 1;
	}
	
	if (points == 0) {
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

function addAdditionalTemplate(){
	var tpl_add = document.getElementById('template_additional').value;
	
	  var tpl_list = document.getElementById('template_additional_list').innerHTML;
	  var addTemplate = document.getElementById('tpl_add_select').value.split('|',2);
	  var addTplId = addTemplate[0];
	  var addTplText = addTemplate[1];
	if(addTplId > 0) {
	  var newVal = tpl_add + '/' + addTplId + '/';
	  newVal = newVal.replace('//', '/');
	  var newList = tpl_list + '<br>' + addTplText;
	  newList = newList.replace('<br><br>', '<br>');
	  document.getElementById('template_additional').value = newVal;
	  document.getElementById('template_additional_list').innerHTML = newList;
	  alert('additional template ' + addTplText + ' added to customer');
	} else {
	  alert('no additional template selcted');
	}
}

function delAdditionalTemplate(){
	var tpl_add = document.getElementById('template_additional').value;
	if(tpl_add != '') {
		var tpl_list = document.getElementById('template_additional_list').innerHTML;
		var addTemplate = document.getElementById('tpl_add_select').value.split('|',2);
		var addTplId = addTemplate[0];
		var addTplText = addTemplate[1];
		var newVal = tpl_add;
		newVal = newVal.replace(addTplId, '');
		newVal = newVal.replace('//', '/');
		var newList = tpl_list.replace(addTplText, '');
		newList = newList.replace('<br><br>', '<br>');
		document.getElementById('template_additional').value = newVal;
		document.getElementById('template_additional_list').innerHTML = newList;
		alert('additional template ' + addTplText + ' deleted from customer');
  } else {
  	alert('no additional template selcted');
  }
  
}
