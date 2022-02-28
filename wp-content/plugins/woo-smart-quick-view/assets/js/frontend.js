'use strict';

var woosq_ids = [],
    woosq_products = [];

(function($) {
  $(document).ready(function() {
    $('.woosq-btn').each(function() {
      var id = $(this).attr('data-id');
      var pid = $(this).attr('data-pid');
      var product_id = $(this).attr('data-product_id');

      if (typeof pid !== typeof undefined && pid !== false) {
        id = pid;
      }

      if (typeof product_id !== typeof undefined && product_id !== false) {
        id = product_id;
      }

      if (-1 === $.inArray(id, woosq_ids)) {
        woosq_ids.push(id);
        woosq_products.push(
            {src: woosq_vars.ajax_url + '?product_id=' + id});
      }
    });
  });

  $(document).on('click touch', '[href*="#woosq-"]', function(e) {
    var href = $(this).attr('href');
    var reg = /#woosq-([0-9]+)/g;
    var match = reg.exec(href);

    if (match[1] !== undefined) {
      var id = match[1];

      woosq_open(id);
      e.preventDefault();
    }
  });

  $(document).on('click touch', '.woosq-btn', function(e) {
    var id = $(this).attr('data-id');
    var pid = $(this).attr('data-pid');
    var product_id = $(this).attr('data-product_id');
    var effect = $(this).attr('data-effect');

    if (typeof pid !== typeof undefined && pid !== false) {
      id = pid;
    }

    if (typeof product_id !== typeof undefined && product_id !== false) {
      id = product_id;
    }

    woosq_open(id, effect);
    e.preventDefault();
  });

  $(document).on('added_to_cart', function() {
    $.magnificPopup.close();
  });

  $(document).on('woosq_loaded', function() {
    if (!$('#woosq-popup .woosq-redirect').length) {
      if ((woosq_vars.cart_redirect === 'yes') &&
          (woosq_vars.cart_url !== '')) {
        $('#woosq-popup form').
            prepend(
                '<input class="woosq-redirect" name="woosq-redirect" type="hidden" value="' +
                woosq_vars.cart_url + '"/>');
      } else {
        $('#woosq-popup form').
            prepend(
                '<input class="woosq-redirect" name="woosq-redirect" type="hidden" value="' +
                window.location.href + '"/>');
      }
    }
  });

  $(document).on('found_variation', function(e, t) {
    if ($(e['target']).closest('#woosq-popup').length) {
      if (t['woosq_image'] !== undefined && t['woosq_image'] !== '') {
        $('#woosq-popup .thumbnails-ori').hide();

        if ($('#woosq-popup .thumbnails-new').length) {
          $('#woosq-popup .thumbnails-new').
              html('<div class="thumbnail">' +
                  t['woosq_image'] + '</div>').
              show();
        } else {
          $(
              '<div class="thumbnails thumbnails-new"><div class="thumbnail">' +
              t['woosq_image'] + '</div></div>').
              insertAfter('#woosq-popup .thumbnails-ori');
        }
      } else {
        $('#woosq-popup .thumbnails-new').hide();
        $('#woosq-popup .thumbnails-ori').show();
      }
    }
  });

  $(document).on('reset_data', function(e) {
    if ($(e['target']).closest('#woosq-popup').length) {
      $('#woosq-popup .thumbnails-new').hide();
      $('#woosq-popup .thumbnails-ori').show();
    }
  });

  $(window).on('resize', function() {
    if ($(window).width() > 1023) {
      $('#woosq-popup').css('height', 'auto');

      if (woosq_vars.scrollbar === 'yes') {
        $('#woosq-popup .summary-content').
            perfectScrollbar('destroy').
            perfectScrollbar({theme: 'wpc'});
      }
    } else {
      if (woosq_vars.scrollbar === 'yes') {
        $('#woosq-popup .summary-content').perfectScrollbar('destroy');
      }

      $('#woosq-popup').
          css('height', document.documentElement.clientHeight * 0.9);
    }
  });
})(jQuery);

function woosq_open(id, effect) {
  if (-1 === jQuery.inArray(id, woosq_ids)) {
    woosq_ids.push(id);
    woosq_products.push(
        {src: woosq_vars.ajax_url + '?product_id=' + id});
  }

  var index = woosq_get_key(woosq_products, 'src',
      woosq_vars.ajax_url + '?product_id=' + id);

  jQuery.magnificPopup.open({
    items: woosq_products,
    type: 'ajax',
    mainClass: 'mfp-woosq',
    removalDelay: 160,
    overflowY: 'scroll',
    fixedContentPos: true,
    gallery: {
      enabled: true,
    },
    ajax: {
      settings: {
        type: 'GET',
        data: {
          action: 'woosq_quickview',
        },
      },
    },
    callbacks: {
      beforeOpen: function() {
        if (typeof effect !== typeof undefined && effect !== false) {
          this.st.mainClass = 'mfp-woosq ' + effect;
        } else {
          this.st.mainClass = 'mfp-woosq ' + woosq_vars.effect;
        }
      },
      ajaxContentAdded: function() {
        var form_variation = jQuery('#woosq-popup').find('.variations_form');

        form_variation.each(function() {
          jQuery(this).wc_variation_form();
        });

        if (jQuery(window).width() > 1023) {
          jQuery('#woosq-popup').css('height', 'auto');

          if (woosq_vars.scrollbar === 'yes') {
            jQuery('#woosq-popup .summary-content').
                perfectScrollbar('destroy').
                perfectScrollbar({theme: 'wpc'});
          }
        } else {
          if (woosq_vars.scrollbar === 'yes') {
            jQuery('#woosq-popup .summary-content').perfectScrollbar('destroy');
          }

          jQuery('#woosq-popup').
              css('height', document.documentElement.clientHeight * 0.9);
        }

        // slick slider
        var is_rtl = woosq_vars.is_rtl === '1';

        if (jQuery('#woosq-popup .thumbnails img').length > 1) {
          jQuery('#woosq-popup .thumbnails').slick({
            slidesToShow: 1,
            slidesToScroll: 1,
            dots: true,
            arrows: true,
            adaptiveHeight: false,
            rtl: is_rtl,
          });
        }

        jQuery(document.body).trigger('woosq_loaded', [id]);
      },
      afterClose: function() {
        jQuery(document.body).trigger('woosq_close', [id]);
      },
    },
  }, index);

  jQuery(document.body).trigger('woosq_open', [id]);
}

function woosq_get_key(array, key, value) {
  for (var i = 0; i < array.length; i++) {
    if (array[i][key] === value) {
      return i;
    }
  }

  return -1;
}