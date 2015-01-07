function loadPushyMenu() {
  // Off-Canvas Menü
  var $mainNavigation = $('#main-navigation');
  var $subNavigation = $('#sidebar');
  var $responsiveNavigation = $('nav.pushy');

  $responsiveNavigation.html('');
  
  // Hauptnavigation
  $('<ul />').appendTo($responsiveNavigation);

  $($mainNavigation).find('a').each(function () {
    var $item = $(this);
    var $activeClass = $item.hasClass('active') ? ' class="active"' : '';
    
    var capp = $item.attr('data-capp');
    if(capp) $activeClass += ' data-capp="' + capp + '"';
	
	capp = $item.attr('data-load-content');
    if(capp) $activeClass += ' data-load-content="' + capp + '"';

    $responsiveNavigation.find('ul').append($('<li><a href="' + $item.attr('href') + '"' + $activeClass + '><i class="icon ' + $item.data('icon-class') + '"></i>' + $item.text() + '</a></li>'));
  });

  // Subnavigation
  $('<ul class="subnavi" />').appendTo($responsiveNavigation);

  $($subNavigation).find('a').each(function () {
    var $item = $(this);
    
    var addattr = '';
	var capp = $item.attr('data-capp');
    if(capp) addattr += ' data-capp="' + capp + '"';
	
	capp = $item.attr('data-load-content');
    if(capp) addattr += ' data-load-content="' + capp + '"';

    $responsiveNavigation.find('ul.subnavi').append($('<li><a href="' + $item.attr('href') + '"' + addattr + '>' + $item.text() + '</a></li>'));
  });
};
