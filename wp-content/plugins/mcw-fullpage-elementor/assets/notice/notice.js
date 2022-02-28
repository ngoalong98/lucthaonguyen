jQuery(document).ready(function($) {
  $('.mcw-fullpage-elementor-notice.is-dismissible').each(function(){
    var t = this;
    $(this).find('button.notice-dismiss').click(function(e){
      data = {
        'action': 'mcw-fullpage-elementor-admin-notice',
        'notice': $(t).data('notice'),
        'nonce': McwFullPageElementor.nonce,
      };
      $.post(McwFullPageElementor.ajaxurl, data);
    });
  });
});
