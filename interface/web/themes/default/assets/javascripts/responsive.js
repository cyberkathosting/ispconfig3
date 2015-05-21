ISPConfig.loadPushyMenu = function() {
  // Off-Canvas Menü
  var $mainNavigation = $('#main-navigation');
  var $subNavigation = $('#sidebar');
  var $responsiveNavigation = $('nav.pushy');

  $responsiveNavigation.html('');
  
  // Hauptnavigation
  $('<ul />').appendTo($responsiveNavigation);

  var $addto = false;
  $($mainNavigation).find('a').each(function () {
    var $item = $(this);
    var $activeClass = $item.hasClass('active') ? ' class="active"' : '';
    var isactive = $activeClass != '' ? true : false;
    
    var capp = $item.attr('data-capp');
    if(capp) $activeClass += ' data-capp="' + capp + '"';
	
	capp = $item.attr('data-load-content');
    if(capp) $activeClass += ' data-load-content="' + capp + '"';

	var $newel = $('<li><a href="' + $item.attr('href') + '"' + $activeClass + '><i class="icon ' + $item.data('icon-class') + '"></i>' + $item.text() + '</a></li>');
	if(isactive != '') $addto = $newel;
    $responsiveNavigation.find('ul').append($newel);
  });

  // Subnavigation
  if(!$addto) $addto = $responsiveNavigation;
  $('<ul class="subnavi" />').appendTo($addto);

  $($subNavigation).find('a').each(function () {
    var $item = $(this);
    
    var addattr = '';
	var capp = $item.attr('data-capp');
    if(capp) addattr += ' data-capp="' + capp + '"';
	
	capp = $item.attr('data-load-content');
    if(capp) addattr += ' data-load-content="' + capp + '"';

	capp = $item.hasClass('subnav-header');
	if(capp) addattr += ' class="subnav-header"';
	
    $responsiveNavigation.find('ul.subnavi').append($('<li><a href="' + $item.attr('href') + '"' + addattr + '>' + $item.text() + '</a></li>'));
  });
};
