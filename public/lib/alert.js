function pianoAlert (status, msg, time, reload) {
  var className = status === true ? 'alert-success' : 'alert-danger';
  $('#alertSuccessWrapper').find('div').addClass(className);
  $('#alertSuccessText').text(msg);
  $("#alertSuccessWrapper").fadeIn();
  setTimeout(function(){
    $("#alertSuccessWrapper").fadeOut();
    if (reload === true) {
      location.reload()
    }
  }, time)
}