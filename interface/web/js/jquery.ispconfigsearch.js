/*
Copyright (c) 2012, ISPConfig UG (haftungsbeschränkt)
All rights reserved.

Redistribution and use in source and binary forms, with or without modification,
are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice,
      this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice,
      this list of conditions and the following disclaimer in the documentation
      and/or other materials provided with the distribution.
    * Neither the name of ISPConfig nor the names of its contributors
      may be used to endorse or promote products derived from this software without
      specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/
(function($) {
	$.fn.ispconfigSearch = function(settings){

		var defaultSettings = {
			dataSrc: '',
			timeout: 500,
			minChars: 2,
			resultBox: '-resultbox',
			resultBoxPosition: 's', // n = north, e = east, s = south, w = west
			cssPrefix: 'gs-',
			fillSearchField: false,
			fillSearchFieldWith: 'title',
			ResultsTextPrefix: '',
			resultsLimit: '$ of % results',
			noResultsText: 'No results.',
			noResultsLimit: '0 results',
			searchFieldWatermark: 'Search',
			displayEmptyCategories: false,
			runJS: true
		};
		
		var previousQ = '';
		var data;
		var settings = $.extend(defaultSettings, settings);
		settings.resultBox = $(this).attr('id')+settings.resultBox;
		
		$(this).attr('autocomplete', 'off');
		if($(this).val() == '') $(this).val(settings.searchFieldWatermark);
		$(this).wrap('<div class="'+settings.cssPrefix+'container" />');
		$(this).after('<ul id="'+settings.resultBox+'" class="'+settings.cssPrefix+'resultbox" style="display:none;"></ul>');
		var searchField = $(this);
		var resultBox = $('#'+settings.resultBox);

		var timeout = null;
		searchField.keyup(function(event) {
			// 13 = enter, 9 = tab
			if (event.keyCode != 13 && event.keyCode != 9){
				// query value
				var q = searchField.val();
				
				if (settings.minChars > q.length || (q == '' && settings.minChars > 0)){
					resultBox.fadeOut();
					resetTimer(timeout);
				} else {
					if (timeout != null){
						resetTimer(timeout);
					}
					
					timeout = setTimeout(function(){
						searchField.addClass(settings.cssPrefix+'loading');

						// we don't have to perform a new search if the query equals the previous query
						previousQ = q;
						var queryStringCombinator = '?';
						if(settings.dataSrc.indexOf('?') != -1){
							queryStringCombinator = '&';
						}
						$.getJSON(settings.dataSrc+queryStringCombinator+"q="+q, function(data, textStatus){
							if (textStatus == 'success'){
								var output = '';
								var resultsFound = false;

								if($.isEmptyObject(data) === false){
									$.each(data, function(i, category){
										if (category['cdata'].length > 0){
											resultsFound = true;
										}
									});
								}

								if (!resultsFound){
									output += '<li class="'+settings.cssPrefix+'cheader"><p class="'+settings.cssPrefix+'cheader-title">'+(settings.ResultsTextPrefix == '' ? '' : settings.ResultsTextPrefix+': ')+settings.noResultsText+'</p><p class="'+settings.cssPrefix+'cheader-limit">'+settings.noResultsLimit+'</p></li>';
								} else {
								
									$.each(data, function(i, category){
										
										if (settings.displayEmptyCategories || (!settings.displayEmptyCategories && category['cdata'].length != 0)){
											var limit = category['cheader']['limit'];
											var cnt = 0;

											output += '<li class="'+settings.cssPrefix+'cheader"><p class="'+settings.cssPrefix+'cheader-title">'+(settings.ResultsTextPrefix == '' ? '' : settings.ResultsTextPrefix+': ')+category['cheader']['title']+'</p><p class="'+settings.cssPrefix+'cheader-limit">'+settings.resultsLimit.replace("%", category['cheader']['total']).replace("$", (category['cheader']['limit'] < category['cdata'].length ? category['cheader']['limit'] : category['cdata'].length))+'</p></li>';

											var fillSearchFieldCode = (settings.fillSearchField) ? 'document.getElementById(\''+searchField.attr('id')+'\').value = \'%\';' : '';
											//var fillSearchFieldCode = 'document.getElementById(\''+searchField.attr('id')+'\').value = \'%\';';
											
											$.each(category['cdata'], function (j, item){
												if (cnt < limit){
													//var link = '<a href="'+((item['url'] != undefined) ? item['url'] : 'javascript:void(0);')+'" '+((item['onclick'] != undefined) ? ' onclick="'+fillSearchFieldCode.replace("%", ((settings.fillSearchField) ? item[settings.fillSearchFieldWith] : ''))+(settings.runJS ? item['onclick'] : '')+'"' : '')+((item['target'] != undefined) ? ' target="'+item['target']+'"' : '')+'>';
													var link = '<a href="'+((item['url'] != undefined) ? item['url'] : 'javascript:void(0);')+'" '+((item['onclick'] != undefined) ? ' onclick="'+fillSearchFieldCode.replace("%", item[settings.fillSearchFieldWith])+(settings.runJS ? item['onclick'] : '')+'"' : '')+((item['target'] != undefined) ? ' target="'+item['target']+'"' : '')+'>';

													output += '<li class="'+settings.cssPrefix+'cdata">'+link+"\n";
													output += '<p>';
													output += (item['title'] != undefined) ? '<span class="'+settings.cssPrefix+'cdata-title">'+item['title']+"</span>\n": '';
													output += (item['description'] != undefined) ? ''+item['description']+''+"\n" : '';
													output += '</p>'+"\n";
													output += '</a></li>'+"\n";
												}
												cnt++;
											});
										}
									});
								}

								//resultBox.html(output).css({'position' : 'absolute', 'top' : searchField.position().top+searchField.outerHeight(), 'right' : '0'}).fadeIn();
								if(settings.resultBoxPosition == 'n'){
									resultBox.html(output).css({'position' : 'absolute', 'top' : searchField.position().top-resultBox.outerHeight(), 'left' : searchField.position().left+searchField.outerWidth()-resultBox.outerWidth()}).fadeIn();
								} else if(settings.resultBoxPosition == 'e'){
									resultBox.html(output).css({'position' : 'absolute', 'top' : searchField.position().top, 'left' : searchField.position().left+searchField.outerWidth()}).fadeIn();
								} else if(settings.resultBoxPosition == 's'){
									resultBox.html(output).css({'position' : 'absolute', 'top' : searchField.position().top+searchField.outerHeight(), 'left' : searchField.position().left+searchField.outerWidth()-resultBox.outerWidth()}).fadeIn();
								} else if(settings.resultBoxPosition == 'w'){
									resultBox.html(output).css({'position' : 'absolute', 'top' : searchField.position().top, 'left' : searchField.position().left-resultBox.outerWidth()}).fadeIn();
								} else {
									resultBox.html(output).fadeIn();
								}

								searchField.removeClass(settings.cssPrefix+'loading');
							}
						});
					}, settings.timeout);
				}
			}		
		});
		
		searchField.blur(function(){
			resultBox.fadeOut();
			if (searchField.val() == ''){
				searchField.val(settings.searchFieldWatermark);
			}
		});

		searchField.focus(function(){
			if (searchField.val() == previousQ && searchField.val() != ''){
				resultBox.fadeIn();
			} else if(searchField.val() == settings.searchFieldWatermark){
				searchField.val('');
				if(settings.minChars == 0) searchField.trigger('keyup');
			} else if (searchField.val() != ''){
				searchField.trigger('keyup');
			}
		});

	};
	
	function resetTimer(timeout){
		clearTimeout(timeout);
		timeout = null;
	};
})(jQuery);