$(document).on('ready', function () {
  // Animierter Ladefortschritt
  $('.progress .progress-bar').css('width', function () {
    return $(this).attr('aria-valuenow') + '%';
  });
});
