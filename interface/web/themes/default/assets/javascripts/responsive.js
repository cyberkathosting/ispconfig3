$(document).on('ready', function () {
  // Off-Canvas Menü
  var $mainNavigation = $('#main-navigation');
  var $subNavigation = $('.subnavigation');
  var $responsiveNavigation = $('nav.pushy');

  // Hauptnavigation
  $('<ul />').appendTo($responsiveNavigation);

  $($mainNavigation).find('a').each(function () {
    var $item = $(this);
    var $activeClass = $item.hasClass('active') ? ' class="active"' : '';

    $responsiveNavigation.find('ul').append($('<li><a href="' + $item.attr('href') + '"' + $activeClass + '><i class="icon ' + $item.data('icon-class') + '"></i>' + $item.text() + '</a></li>'));
  });

  // Subnavigation
  $('<ul class="subnavi" />').appendTo($responsiveNavigation);

  $($subNavigation).find('a').each(function () {
    var $item = $(this);
    $responsiveNavigation.find('ul.subnavi').append($('<li><a href="' + $item.attr('href') + '">' + $item.text() + '</a></li>'));
  });
});
