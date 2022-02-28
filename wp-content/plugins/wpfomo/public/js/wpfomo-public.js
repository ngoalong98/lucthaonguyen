jQuery(function() {

  getProduct();

  function getProduct() {
    var num = Math.floor(Math.random() * settings.buyer_name.length);

    jQuery(".wpfomo-buyer-name").text( (settings.buyer_name[num]) );
    jQuery(".wpfomo-product-name").text( (settings.product_name[num]) );
    jQuery(".wpfomo-secondary-text").text( (settings.purchase_time[num]) );
    jQuery(".wpfomo-product-thumb").attr('src',settings.product_image[num]);
    jQuery(".wpfomo-product-name").attr('href', (settings.custom_url[num]) );    
  }
  
  // Loop the notification
  (function loop() {
 
      setTimeout(function() {
        changeNotification();
        loop();  
      }, 1800000);
  }());

  // Change notification
  function changeNotification() {
    showNotification();
    setTimeout(function() {
      hideNotification();
    }, 5000) // duration
  }
  
  // Show notification
  function showNotification() {
    jQuery("#wpfomo").addClass("is-visible");
  }
  
  // Hide notification
  function hideNotification() {
    jQuery("#wpfomo").removeClass("is-visible");
    setTimeout(function() {
      getProduct();
    }, 500)
  }

});