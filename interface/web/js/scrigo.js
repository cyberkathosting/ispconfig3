redirect = '';


function capp(module) {
	var cappCallback = {
		success: function(o) {
			if(o.responseText != '') {
				if(o.responseText.indexOf("HEADER_REDIRECT:") > -1) {
					var parts = o.responseText.split(":");
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
			if(o.responseText.indexOf("HEADER_REDIRECT:") > -1) {
				var parts = o.responseText.split(":");
				//alert(parts[1]);
				loadContent(parts[1]);
				//redirect = parts[1];
				//window.setTimeout('loadContent(redirect)', 1000);
			} else {
				document.getElementById('pageContent').innerHTML = o.responseText;
			}
			loadMenus();
		},
		failure: function(o) {
			alert('Ajax Request was not successful.');
		}
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
			if(o.responseText.indexOf("HEADER_REDIRECT:") > -1) {
				var parts = o.responseText.split(":");
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

function loadContent(pagename) {
	var pageContentCallback2 = {
		success: function(o) {
			//alert(o.responseText);
			if(o.responseText.indexOf("HEADER_REDIRECT:") > -1) {
				var parts = o.responseText.split(":");
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
			if(o.responseText.indexOf("HEADER_REDIRECT:") > -1) {
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
	
	function del_record(link) {
  if(window.confirm("<tmpl_var name='delete_confirmation'>")) {
          loadContent(link);
  }
}