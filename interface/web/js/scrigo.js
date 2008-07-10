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
			alert('Ajax Request was not successful. 1');
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