jQuery(document).ready(function($) {
  var ch_url = jQuery(document).find('.checkout-button').attr('href');
  var sPageURL = window.location.search.substring(1),
      sURLVariables = sPageURL.split('&'),
      sParameterName,
      i;
  var cart_type = false;
  var cart_qty = '';
  for (i = 0; i < sURLVariables.length; i++) {
      sParameterName = sURLVariables[i].split('=');

      if (sParameterName[0] === 'cart_type') {
        cart_type=true;
      }

      if(cart_type===true){
        jQuery(document).find('.checkout-button').attr('href',ch_url+'?'+sPageURL);
      } else {
        jQuery(document).find('.checkout-button').attr('href',ch_url+'?type=app_webview');
      }
  }
  // console.log(sURLVariables);
  
  jQuery(document).on('click','.back-link',function(e){
    if(jQuery(document).find('body').hasClass('woocommerce-cart')){
      e.preventDefault();
      var response = {};
      response.button = 'back';
      if(cart_type===true){
        var pro_count=[];
        var cart_count='';
        jQuery('.cart_item').each(function(e){
            var qty = jQuery(this).find('.input-text.qty').val();
            var p_id = jQuery(this).find('.remove-item').data('product_id');
            var p_count= p_id+','+qty;
            pro_count.push(p_count);
            cart_count=pro_count.join('|')
        });
        response.cartData = cart_count;
        // window.postMessage(cart_count);
      }
      window.postMessage(JSON.stringify(response));
    }
  });

  jQuery(document).on('change','.cart_item .qty',function(e){
      var response = {};
      response.button = '';
      var pro_count=[];
      var cart_count='';
      jQuery('.cart_item').each(function(e){
          var qty = jQuery(this).find('.input-text.qty').val();
          var p_id = jQuery(this).find('.remove-item').data('product_id');
          var p_count= p_id+','+qty;
          pro_count.push(p_count);
          cart_count=pro_count.join('|')
      });
      response.cartData = cart_count;
      window.postMessage(JSON.stringify(response));
  });
  jQuery(document).ready(function(e){
      setTimeout(function(){
        var response = {};
        response.button = '';
        var pro_count=[];
        var cart_count='';
        jQuery('.cart_item').each(function(e){
            var qty = jQuery(this).find('.input-text.qty').val();
            var p_id = jQuery(this).find('.remove-item').data('product_id');
            var p_count= p_id+','+qty;
            pro_count.push(p_count);
            cart_count=pro_count.join('|')
        });
        response.cartData = cart_count;
        window.postMessage(JSON.stringify(response));
      },500);
  });

  jQuery(document).on('click','.return-shop,.empty-cart-block-kiana a.btn.black',function(e){
    if(jQuery(document).find('body').hasClass('woocommerce-cart')){
      e.preventDefault();
      var response = {};
      response.button = 'shop';
      if(cart_type===true){
        var pro_count=[];
        var cart_count='';
        jQuery('.cart_item').each(function(e){
            var qty = jQuery(this).find('.input-text.qty').val();
            var p_id = jQuery(this).find('.remove-item').data('product_id');
            var p_count= p_id+','+qty;
            pro_count.push(p_count);
            cart_count=pro_count.join('|')
        });
        response.cartData = cart_count;
        // window.postMessage(cart_count);
      }
      window.postMessage(JSON.stringify(response));
    }
  });
});
function returnToShopMsg()
{
  var response = {};
  response.button = 'back';
  response.cartData = '';
  response.message = 'success';
  // window.postMessage("order_completed");
  window.postMessage(JSON.stringify(response));
}