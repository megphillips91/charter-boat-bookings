jQuery(document).ready(function ($) {

// Author: Meg Phillips;
/* Scope: Javascript that affects admin side of WP
*/

$(document).on('change','#product-type',function(event){
	if(chbk_admin_scripts_vars.hook_suffix == 'post-new.php'
	&& chbk_admin_scripts_vars.post_type == 'product'
	&& $('#product-type').val() == 'charter_booking'){
		$('#_sold_individually').attr('checked', 'checked');
	}
});


function adminLazyloadProductListing (){
	$( '.lazyload-product-listing' ).each(function( index ) {
  //console.log( index + ": " + $( this ).attr('product_id') );
	var data = {
		'action': 'cb_lazyload_product',
		'cb_date': $(this).attr('date'),
    'product_id': $(this).attr('product_id'),
		'list_action': $(this).attr('list_action')
		}
	$.post(chbk_admin_scripts_vars.admin_ajax, data, function(response) {
    $("div[product_id='"+data.product_id+"']").replaceWith(response.html);
    });
	});
}

function get_url_param (name){
  var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
  if (results==null){
     return null;
  }
  else{
     return results[1] || 0;
  }
}

//add booking
$(document).on('click','.cb-open-booking-addnew',function(event){
  event.preventDefault();
	console.log('clicked add');
  $('.cb-booking-modal-backdrop').css('display','block');
	var data = {
		'action': 'cb_open_booking_add'
		}
	$.post(chbk_admin_scripts_vars.admin_ajax, data, function(response) {
    	$('.cb-hold-booking').html(response.html);
			lazyloadGlobalCalendar();
			adminLazyloadProductListing();
		});
});

//admin booking
$(document).on('click','.cb-open-booking',function(event){
  event.preventDefault();
  $('.cb-booking-modal-backdrop').css('display','block');
	var data = {
		'action': 'cb_open_booking',
    'booking_id': $(this).attr('booking_id')
		}
	$.post(chbk_admin_scripts_vars.admin_ajax, data, function(response) {
    	$('.cb-hold-booking').html(response.html);
		});
});

//cb-close-booking
$(document).on('click','.cb-close-booking',function(event){
  $('.cb-hold-booking').html('');
  $('.cb-booking-modal-backdrop').css('display','none');
});


function lazyloadGlobalCalendar(){
	$('.cb-hold-calendar-loader').animate({
    opacity: 0.5
  }, 200, function() {
  });
	var data = {
		'action': 'cb_lazyload_global_calendar',
    'date': $('.cb-lazyload-global-calendar').attr('date'),
		'list_action': $('.cb-lazyload-global-calendar').attr('list_action')
		}
	$.post(chbk_admin_scripts_vars.admin_ajax, data, function(response) {
    $('.cb-lazyload-global-calendar').replaceWith(response.html);
    });
}



//change view for date_range
$(document).on('change','.woocommerce_page_cb-bookings #date_range',function(event){
  event.preventDefault();
	var data = {
		'action': 'cb_admin_filter_date_range',
    'date_range': $('#date_range').val()
		}
	$.post(chbk_admin_scripts_vars.admin_ajax, data, function(response) {
    	$('#wpbody-content .wrap').replaceWith(response.html);
		});
});

$(document).on('click','.woocommerce_page_cb-bookings .booking_status_fitler',function(event){
  event.preventDefault();
	var data = {
		'action': 'cb_admin_filter_booking_status',
    'booking_status': $(this).attr('booking_status')
		}
	$.post(chbk_admin_scripts_vars.admin_ajax, data, function(response) {
    	$('#wpbody-content .wrap').replaceWith(response.html);
      $('.booking_status_fitler').removeClass('current');
      $("[booking_status='"+data.booking_status+"']").addClass('current');
		});
});


});//end jquery wrapper
