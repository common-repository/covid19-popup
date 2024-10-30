jQuery(document).ready(function($){
  $('.covid19-popup-close').on('click',function(){
    $(this).parent().fadeOut();
  });
});