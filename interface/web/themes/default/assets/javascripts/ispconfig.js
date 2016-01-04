var ISPConfig = {
	pageFormChanged: false,
	tabChangeWarningTxt: '',
	tabChangeDiscardTxt: '',
	tabChangeWarning: false,
	tabChangeDiscard: false,
	requestsRunning: 0,
	indicatorCompleted: false,
	registeredHooks: new Array(),
	new_tpl_add_id: 0,
	
	options: {
		useLoadIndicator: false,
		useComboBox: false
	},
	
	setOption: function(key, value) {
		ISPConfig.options[key] = value;
	},
	
	setOptions: function(opts) {
		$.extend(ISPConfig.options, opts);
	},
	
	reportError: function(request) {
		/* Error reporting is disabled by default as some browsers like safari
		   sometimes throw errors when a ajax request is delayed even if the
		   ajax request worked. */

		/*alert(request);*/
	},
	
	registerHook: function(name, callback) {
		if(!ISPConfig.registeredHooks[name]) ISPConfig.registeredHooks[name] = new Array();
		var newindex = ISPConfig.registeredHooks[name].length;
		ISPConfig.registeredHooks[name][newindex] = callback;
	},
	
	callHook: function(name, params) {
		if(!ISPConfig.registeredHooks[name]) return;
		for(var i = 0; i < ISPConfig.registeredHooks[name].length; i++) {
			var callback = ISPConfig.registeredHooks[name][i];
			callback(name, params);
		}
	},
	
	resetFormChanged: function() {
		ISPConfig.pageFormChanged = false;
	},

	showLoadIndicator: function() {
		document.body.style.cursor = 'wait';
		
		if(ISPConfig.options.useLoadIndicator == true) {
			ISPConfig.requestsRunning += 1;

			if(ISPConfig.requestsRunning < 2) {
				var indicator = $('#ajaxloader');
				if(indicator.length < 1) {
					indicator = $('<div id="ajaxloader" style="display: none;"></div>');
					indicator.appendTo('body');
				}
				var parent = $('#content');
				if(parent.length < 1) return;
				ISPConfig.indicatorCompleted = false;

				var atx = parent.offset().left + 150; //((parent.outerWidth(true) - indicator.outerWidth(true)) / 2);
				var aty = parent.offset().top + 150;
				indicator.css( {'left': atx, 'top': aty } ).fadeIn('fast', function() {
					// check if loader should be hidden immediately
					ISPConfig.indicatorCompleted = true;
					if(ISPConfig.requestsRunning < 1) $(this).fadeOut('fast', function() { $(this).hide();});
				});
			}
		}
	},

	hideLoadIndicator: function() {
		document.body.style.cursor = '';

		ISPConfig.requestsRunning -= 1;
		if(ISPConfig.requestsRunning < 1) {
			ISPConfig.requestsRunning = 0; // just for the case...
			if(ISPConfig.indicatorCompleted == true) $('#ajaxloader').fadeOut('fast', function() { $('#ajaxloader').hide(); } );
		}
	},

	onAfterSideNavLoaded: function() {
		if(ISPConfig.options.useComboBox == true) {
			$('#sidebar').find("select:not(.chosen-select)").select2({
				placeholder: '',
				width: 'element',
				selectOnBlur: true,
				allowClear: true
			});
		}
	},

	onAfterContentLoad: function(url, data) {
		if(!data) data = '';
		else data = '&' + data;
		
		if(ISPConfig.options.useComboBox == true) {
			$('#pageContent').find("select:not(.chosen-select)").select2({
				placeholder: '',
				width: 'element',
				selectOnBlur: true,
				allowClear: true,
				formatResult: function(o) {
					if(o.id && $(o.element).parent().hasClass('flags')) return '<span class="flags flag-' + o.id.toLowerCase() + '">' + o.text + '</span>';
					else return o.text;
				},
				formatSelection: function(o) {
					if(o.id && $(o.element).parent().hasClass('flags')) return '<span class="flags flag-' + o.id.toLowerCase() + '">' + o.text + '</span>';
					else return o.text;
				}
			}).on('change', function(e) {
				if ($("#pageForm .table #Filter").length > 0) {
					$("#pageForm .table #Filter").trigger('click');
				}
			});
		}
		
		$('input[data-input-element="date"]').datetimepicker({
			'language': 'en', // TODO
			'todayHighlight': true,
			'todayBtn': 'linked',
			'bootcssVer': 3,
			'fontAwesome': true,
			'autoclose': true,
			'minView': 'month'
		});
		$('input[data-input-element="datetime"]').datetimepicker({
			'language': 'en', // TODO
			'todayHighlight': true,
			'todayBtn': 'linked',
			'bootcssVer': 3,
			'fontAwesome': true,
			'autoclose': true
		});
		
		ISPConfig.callHook('onAfterContentLoad', {'url': url, 'data': data });
	},

	/* THIS ONE SHOULD BE REMOVED AFTER CREATING THE STATIC LOGIN PAGE!!! */
	/*submitLoginForm: function(formname) {
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

		var submitFormObj = $.ajax({
			type: "POST",
			url: "content.php",
			data: $('#'+formname).serialize(),
			dataType: "html",
			beforeSend: function() {
				ISPConfig.showLoadIndicator();
			},
			success: function(data, textStatus, jqXHR) {
				if(jqXHR.responseText.indexOf('HEADER_REDIRECT:') > -1) {
					var parts = jqXHR.responseText.split(':');
					ISPConfig.loadContent(parts[1]);
				} else if (jqXHR.responseText.indexOf('LOGIN_REDIRECT:') > -1) {
					// Go to the login page
					document.location.href = 'index.php';
				} else {
					$('#pageContent').html(jqXHR.responseText);
					ISPConfig.onAfterContentLoad('content.php', $('#'+formname).serialize());
					ISPConfig.pageFormChanged = false;
				}
				ISPConfig.loadMenus();
				ISPConfig.hideLoadIndicator();
			},
			error: function() {
				ISPConfig.hideLoadIndicator();
				ISPConfig.reportError('Ajax Request was not successful.110');
			}
		});
	},*/

	submitForm: function(formname, target, confirmation) {
		var successMessage = arguments[3];
		if(!confirmation) confirmation = false;
		
		if(!confirmation || window.confirm(confirmation)) {
			var submitFormObj = $.ajax({
				type: "POST",
				url: target,
				data: $('#'+formname).serialize(),
				dataType: "html",
				beforeSend: function() {
					ISPConfig.showLoadIndicator();
				},
				success: function(data, textStatus, jqXHR) {
					if(successMessage) alert(successMessage);
					if(jqXHR.responseText.indexOf('HEADER_REDIRECT:') > -1) {
						var parts = jqXHR.responseText.split(':');
						ISPConfig.loadContent(parts[1]);
					} else if (jqXHR.responseText.indexOf('LOGIN_REDIRECT:') > -1) {
						// Go to the login page
						document.location.href = '/index.php';
					} else {
						$('#pageContent').html(jqXHR.responseText);
						ISPConfig.onAfterContentLoad(target, $('#'+formname).serialize());
						ISPConfig.pageFormChanged = false;
					}
					ISPConfig.hideLoadIndicator();
				},
				error: function(jqXHR, textStatus, errorThrown) {
					ISPConfig.hideLoadIndicator();
					var parts = jqXHR.responseText.split(':');
					ISPConfig.reportError('Ajax Request was not successful. 111');
				}
			});
		}
	},

	submitUploadForm: function(formname, target) {
		var handleResponse = function(loadedFrame) {
			var response, responseStr = loadedFrame.contentWindow.document.body.innerHTML;

			try {
				response = JSON.parse(responseStr);
			} catch(e) {
				response = responseStr;
			}
			var msg = '';
			var okmsg = $('#OKMsg',response).html();
			if(okmsg){
				msg = '<div id="OKMsg">'+okmsg+'</div>';
			}
			var errormsg = $('#errorMsg',response).html();
			if(errormsg){
				msg = msg+'<div id="errorMsg">'+errormsg+'</div>';
			}
			return msg;

		};

		var frame_id = 'ajaxUploader-iframe-' + Math.round(new Date().getTime() / 1000);
		$('body').after('<iframe width="0" height="0" style="display:none;" name="'+frame_id+'" id="'+frame_id+'"/>');
		$('input[type="file"]').closest("form").attr({target: frame_id, action: target}).submit();
		$('#'+frame_id).load(function() {
			var msg = handleResponse(this);
			$('#errorMsg').remove();
			$('#OKMsg').remove();
			$('input[name="id"]').before(msg);
			$(this).remove();
		  });
	},

	capp: function(module, redirect) {
		var submitFormObj = $.ajax({
			type: "GET",
			url: "capp.php",
			data: "mod="+module+((redirect != undefined) ? '&redirect='+redirect : ''),
			dataType: "html",
			beforeSend: function() {
				ISPConfig.showLoadIndicator();
			},
			success: function(data, textStatus, jqXHR) {
				if(jqXHR.responseText != '') {
					if(jqXHR.responseText.indexOf('HEADER_REDIRECT:') > -1) {
						var parts = jqXHR.responseText.split(':');
						ISPConfig.loadContent(parts[1]);
					} else if (jqXHR.responseText.indexOf('URL_REDIRECT:') > -1) {
						var newUrl= jqXHR.responseText.substr(jqXHR.responseText.indexOf('URL_REDIRECT:') + "URL_REDIRECT:".length);
						document.location.href = newUrl;
					} else {
						//alert(jqXHR.responseText);
					}
				}
				ISPConfig.loadMenus();
				ISPConfig.hideLoadIndicator();
			},
			error: function() {
				ISPConfig.hideLoadIndicator();
				ISPConfig.reportError('Ajax Request was not successful.'+module);
			}
		});
	},
	
	loadContent: function(pagename) {
		var params = arguments[1];
		var pageContentObject2 = $.ajax({
			type: "GET",
			url: pagename,
			data: (params ? params : null),
			dataType: "html",
			beforeSend: function() {
				ISPConfig.showLoadIndicator();
			},
			success: function(data, textStatus, jqXHR) {
				if(jqXHR.responseText.indexOf('HEADER_REDIRECT:') > -1) {
					var parts = jqXHR.responseText.split(':');
					ISPConfig.loadContent(parts[1]);
				} else if (jqXHR.responseText.indexOf('URL_REDIRECT:') > -1) {
					var newUrl= jqXHR.responseText.substr(jqXHR.responseText.indexOf('URL_REDIRECT:') + "URL_REDIRECT:".length);
					document.location.href = newUrl;
				} else {
					//document.getElementById('pageContent').innerHTML = jqXHR.responseText;
					//var reponse = $(jqXHR.responseText);
					//var reponseScript = reponse.filter("script");
					//$.each(reponseScript, function(idx, val) { eval(val.text); } );

					$('#pageContent').html(jqXHR.responseText);
					ISPConfig.onAfterContentLoad(pagename, (params ? params : null));
					ISPConfig.pageFormChanged = false;
				}
				ISPConfig.hideLoadIndicator();
			},
			error: function() {
				ISPConfig.hideLoadIndicator();
				ISPConfig.reportError('Ajax Request was not successful. 113');
			}
		});
	},

	loadContentRefresh: function(pagename) {
		if($('#refreshinterval').val() > 0) {
			var pageContentObject2 = $.ajax({
				type: "GET",
				url: pagename,
				data: "refresh="+document.getElementById('refreshinterval').value,
				dataType: "html",
				beforeSend: function() {
					ISPConfig.showLoadIndicator();
				},
				success: function(data, textStatus, jqXHR) {
					ISPConfig.hideLoadIndicator();
					$('#pageContent').html(jqXHR.responseText);
					ISPConfig.onAfterContentLoad(pagename, "refresh="+document.getElementById('refreshinterval').value);
					ISPConfig.pageFormChanged = false;
				},
				error: function() {
					ISPConfig.hideLoadIndicator();
					ISPConfig.reportError('Ajax Request was not successful.'+pagename);
				}
			});
			setTimeout( "ISPConfig.loadContentRefresh('"+pagename+"&refresh="+document.getElementById('refreshinterval').value+"')", document.getElementById('refreshinterval').value*1000*60 );
		}
	},

	loadInitContent: function() {
		var pageContentObject = $.ajax({
			type: "GET",
			url: "dashboard/dashboard.php",
			data: "",
			dataType: "html",
			beforeSend: function() {
				ISPConfig.showLoadIndicator();
			},
			success: function(data, textStatus, jqXHR) {
				if(jqXHR.responseText.indexOf('HEADER_REDIRECT:') > -1) {
					var parts = jqXHR.responseText.split(":");
					ISPConfig.loadContent(parts[1]);
				} else {
					$('#pageContent').html(jqXHR.responseText);
					ISPConfig.onAfterContentLoad('dashboard/dashboard.php', "");
					ISPConfig.pageFormChanged = false;
				}
				ISPConfig.hideLoadIndicator();
			},
			error: function() {
				ISPConfig.hideLoadIndicator();
				ISPConfig.reportError('Ajax Request was not successful. 114');
			}
		});
		
		ISPConfig.loadMenus();
		ISPConfig.keepalive();
		setTimeout(function() {
			try {
				$('form#pageForm').find('input[name="username"]').focus();
			} catch (e) {
			
			}
		}, 1000);
	},
	
	loadMenus: function() {
		var sideNavObject = $.ajax({
			type: "GET",
			url: "nav.php",
			data: "nav=side",
			dataType: "html",
			beforeSend: function() {
				ISPConfig.showLoadIndicator();
			},
			success: function(data, textStatus, jqXHR) {
				ISPConfig.hideLoadIndicator();
				$('#sidebar').html(jqXHR.responseText);
				ISPConfig.onAfterSideNavLoaded();
				ISPConfig.loadPushyMenu();
			},
			error: function() {
				ISPConfig.hideLoadIndicator();
				ISPConfig.reportError('Ajax Request was not successful. 115');
			}
		});

		var topNavObject = $.ajax({
			type: "GET",
			url: "nav.php",
			data: "nav=top",
			dataType: "html",
			beforeSend: function() {
				ISPConfig.showLoadIndicator();
			},
			success: function(data, textStatus, jqXHR) {
				ISPConfig.hideLoadIndicator();
				$('#topnav-container').html(jqXHR.responseText);
				ISPConfig.loadPushyMenu();
			},
			error: function(o) {
				ISPConfig.hideLoadIndicator();
				ISPConfig.reportError('Ajax Request was not successful. 116');
			}
		});
	},

	changeTab: function(tab, target, force) {
		if(ISPConfig.requestsRunning > 0) return false;
	
		document.pageForm.next_tab.value = tab;

		var idel = $('form#pageForm').find('[name="id"]');
		var id = null;
		if(idel.length > 0) id = idel.val();
		if(ISPConfig.tabChangeDiscard == 'y' && !force) {
			if((idel.length < 1 || id) && (ISPConfig.pageFormChanged == false || window.confirm(ISPConfig.tabChangeDiscardTxt))) {
				var next_tab = tab;
				if(id) ISPConfig.loadContent(target, {'next_tab': next_tab, 'id': id});
				else ISPConfig.loadContent(target, {'next_tab': next_tab});
			} else {
				return false;
			}
		} else {
			if(id && ISPConfig.tabChangeWarning == 'y' && ISPConfig.pageFormChanged == true) {
				if(window.confirm(ISPConfig.tabChangeWarningTxt)) {
					ISPConfig.submitForm('pageForm', target);
				} else {
					var next_tab = tab;
					if(id) ISPConfig.loadContent(target, {'next_tab': next_tab, 'id': id});
					else ISPConfig.loadContent(target, {'next_tab': next_tab});
				}
			} else {
				ISPConfig.submitForm('pageForm',target);
			}
		}
	},

	confirm_action: function(link, confirmation) {
		if(window.confirm(confirmation)) {
			ISPConfig.loadContent(link);
		}
	},

	loadContentInto: function(elementid,pagename) {
		var pageContentObject2 = $.ajax({
			type: "GET",
			url: pagename,
			dataType: "html",
			beforeSend: function() {
			},
			success: function(data, textStatus, jqXHR) {
				$('#'+elementid).html(jqXHR.responseText);
			},
			error: function() {
				ISPConfig.reportError('Ajax Request was not successful. 118');
			}
		});
	},

	loadOptionInto: function(elementid,pagename,callback) {
		var pageContentObject2 = $.ajax({
			type: "GET",
			url: pagename,
			dataType: "html",
			beforeSend: function() {
			},
			success: function(data, textStatus, jqXHR) {
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
				if (typeof(callback) != 'undefined') {
					callback(elementid,pagename);
				}
			},
			error: function() {
				ISPConfig.reportError('Ajax Request was not successful. 119');
			}
		});
	},
	
	keepalive: function() {
		var pageContentObject3 = $.ajax({
			type: "GET",
			url: "keepalive.php",
			dataType: "html",
			success: function(data, textStatus, jqXHR) {
				setTimeout( function() { ISPConfig.keepalive(); }, 1000000 );
			},
			error: function() {
				ISPConfig.reportError('Session expired. Please login again.');
			}
		});
	},
	
	addAdditionalTemplate: function(){
		var tpl_add = $('#template_additional').val();
		var addTemplate = $('#tpl_add_select').val().split('|',2);
		var addTplId = addTemplate[0];
		var addTplText = addTemplate[1];
		if(addTplId > 0) {
			var newVal = tpl_add.split('/');
			ISPConfig.new_tpl_add_id += 1;
			var delbtn = $('<a href="#"></a>').attr('class', 'button icons16 icoDelete').click(function(e) {
				e.preventDefault();
				ISPConfig.delAdditionalTemplate($(this).parent().attr('rel'));
			});
			newVal[newVal.length] = 'n' + ISPConfig.new_tpl_add_id + ':' + addTplId;
			$('<li>' + addTplText + '</li>').attr('rel', 'n' + new_tpl_add_id).append(delbtn).appendTo('#template_additional_list ul');
			$('#template_additional').val(newVal.join('/'));
			alert('additional template ' + addTplText + ' added to customer');
		} else {
			alert('no additional template selcted');
		}
	},

	delAdditionalTemplate: function(tpl_id) {
		var tpl_add = $('#template_additional').val();
		if(tpl_id) {
			// new style
			var $el = $('#template_additional_list ul').find('li[rel="' + tpl_id + '"]').eq(0); // only the first
			var addTplText = $el.text();
			$el.remove();

			var oldVal = tpl_add.split('/');
			var newVal = new Array();
			for(var i = 0; i < oldVal.length; i++) {
				var tmp = oldVal[i].split(':', 2);
				if(tmp.length == 2 && tmp[0] == tpl_id) continue;
				newVal[newVal.length] = oldVal[i];
			}
			$('#template_additional').val(newVal.join('/'));
			alert('additional template ' + addTplText + ' deleted from customer');
		} else if(tpl_add != '') {
			// old style
			var addTemplate = document.getElementById('tpl_add_select').value.split('|',2);
			var addTplId = addTemplate[0];
			var addTplText = addTemplate[1];

			$('#template_additional_list ul').find('li:not([rel])').each(function() {
				var text = $(this).text();
				if(text == addTplText) {
					$(this).remove();
					return false;
				}
				return this;
			});

			var newVal = tpl_add;
			var repl = new RegExp('(^|\/)' + addTplId + '(\/|$)');
			newVal = newVal.replace(repl, '');
			newVal = newVal.replace('//', '/');
			$('#template_additional').val(newVal);
			alert('additional template ' + addTplText + ' deleted from customer');
	  } else {
		alert('no additional template selcted');
	  }
	}
};


$(document).on("change", function(event) {
	var elName = event.target.localName;
	if ($("#pageForm .table #Filter").length > 0 && elName == 'select') {
		event.preventDefault();
		$("#pageForm .table #Filter").trigger('click');
	}
	if(elName == 'select' || elName == 'input' || elName == 'textarea') {
		if($(event.target).hasClass('no-page-form-change') == false) {
			// set marker that something was changed
			ISPConfig.pageFormChanged = true;
		}
	}
});

$(document).on('click', 'a[data-load-content],button[data-load-content]', function(e) {
	e.preventDefault();
	$('html, body').animate({scrollTop: 0}, 1000);
	
	var content_to_load = $(this).attr('data-load-content');
	if(!content_to_load) return this;
	
	ISPConfig.loadContent(content_to_load);
});

$(document).on('click', 'a[data-capp],button[data-capp]', function(e) {
	e.preventDefault();
	$('html, body').animate({scrollTop: 0}, 1000);
	
	var content_to_load = $(this).attr('data-capp');
	if(!content_to_load) return this;
	
	ISPConfig.capp(content_to_load);
});

$(document).on('click', 'a[data-submit-form],button[data-submit-form]', function(e) {
	e.preventDefault();
	$('html, body').animate({scrollTop: 0}, 1000);
	
	var $el = $(this);
	var act = $el.attr('data-form-action');
	var form = $el.attr('data-submit-form');
	
	if($el.attr('data-form-upload') == 'true') ISPConfig.submitUploadForm(form, act);
	else ISPConfig.submitForm(form, act);
});

$(document).bind("keypress", function(event) {
	//Use $ submit with keypress Enter in panel filterbar
	if (event.which == '13' && $("#pageForm .table #Filter").length > 0 && $(event.target).hasClass('ui-autocomplete-input') == false ) {
		event.preventDefault();
		$("#pageForm .table #Filter").trigger('click');
	}
	//Use $ submit with keypress Enter in forms
	if (event.which == '13' && $(".pnl_formsarea button.positive").length > 0 && event.target.localName != 'textarea' && $(event.target).is(':input')) {
		event.preventDefault();
		$(".pnl_formsarea button.positive:first").not("[disabled='disabled']").trigger('click');
	}
});

$(document).on('click', 'th[data-column]', function(e) {
	var $self = $(this);
	var column = $self.attr('data-column');
	if(!column) return this;
	
	if($("#pageForm .table #Filter").length > 0 && $self.attr('data-sortable') != 'false') {
		var $el = $('#Filter');
		var act = $el.attr('data-form-action');
		var form = $el.attr('data-submit-form');
		
		var dir = $self.attr('data-ordered');
		
		var separator = '?';
		if(act.indexOf("?") >= 0){
			separator = '&';
		}
		act = act + separator + 'orderby=' + column;
		ISPConfig.submitForm(form, act);
		
		$(document).ajaxComplete(function() {
			var $self = $('#pageForm .table th[data-column="' + column + '"]');
			$self.parent().children('th[data-column]').removeAttr('data-ordered');
			if(dir && dir == 'asc') $self.attr('data-ordered', 'desc');
			else $self.attr('data-ordered', 'asc');
		});
		
	}
});

$(document).on("click", ".addPlaceholder", function(){
	var placeholderText = $(this).text();
	var template = $(this).siblings(':input');
	template.insertAtCaret(placeholderText);
});

$(document).on("click", ".addPlaceholderContent", function(){
	var placeholderContentText = $(this).find('.addPlaceholderContent').text();
	var template2 = $(this).siblings(':input');
	template2.insertAtCaret(placeholderContentText);
});

$(document).on('ready', function () {
	$.fn.extend({
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
	
	// Animierter Ladefortschritt
	$('.progress .progress-bar').css('width', function () {
		return $(this).attr('aria-valuenow') + '%';
	});
	
	ISPConfig.loadInitContent();

	$('#searchform').submit(function(e) {
		e.preventDefault();
	});
	
	$("#pageForm").submit(function(e){
		//Prevent form submit: e.preventDefault() in lists
		if ($("#pageForm .table #Filter").length > 0) {
			e.preventDefault();
		}
	});
});
