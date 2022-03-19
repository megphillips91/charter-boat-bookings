jQuery(document).ready(function ($) {

// Author: Meg Phillips;
/* Scope: Javascript that affects public and admin facing side of WP
*/


$( '.general_options' ).addClass( 'show_if_charter_booking' ).show();


function lazyloadProductListing (){
	$( '.lazyload-product-listing' ).each(function( index ) {
		if($(this).attr('list_action') != 'undefined'){
			var list_action = $(this).attr('list_action');
		} else {
			var list_action = $(this).attr('list_action');
		}
		var list_action = $(this).attr('list_action');
	var data = {
		'action': 'cb_lazyload_product',
		'cb_date': $(this).attr('date'),
    'product_id': $(this).attr('product_id')
		}
	$.post(chbk_bookings_vars.admin_ajax, data, function(response) {
		$("div[product_id='"+data.product_id+"']").hide();
    $("div[product_id='"+data.product_id+"']").replaceWith(response.html);
		$("div[product_id='"+data.product_id+"']").fadeIn('slowly');
    });
	});
}

lazyloadProductListing ();

$(document).on('click','.cb-list-products-yesterday',function(event){
	var data = {
		'action': 'cb_list_products_yesterday',
		'cb_date': $(this).attr('date'),
		'list_action': $(this).attr('list_action')
		}
	$.post(chbk_bookings_vars.admin_ajax, data, function(response) {
    $('.hold-products-listing').replaceWith(response);
		lazyloadProductListing ();
    });
});

$(document).on('click','.cb-list-products-tomorrow',function(event){
	var data = {
		'action': 'cb_list_products_tomorrow',
		'cb_date': $(this).attr('date'),
		'list_action': $(this).attr('list_action')
		}
	$.post(chbk_bookings_vars.admin_ajax, data, function(response) {
    $('.hold-products-listing').replaceWith(response);
		lazyloadProductListing ();
    });
});

$(document).on('click','.cb-list-products',function(event){
	$('.current-date').removeClass(' current-date ');
	$(this).parents('td').addClass(' current-date ');
	var data = {
		'action': 'cb_list_products',
		'cb_date': $(this).attr('date'),
		'list_action': $(this).attr('list_action')
		}
	$.post(chbk_bookings_vars.admin_ajax, data, function(response) {

    $('.hold-products-listing').replaceWith(response);
		lazyloadProductListing ();
    });
});

$(document).on('click','.cb-refresh-calendar',function(event){
	var calID = $(this).parents('.cb-calendar-container').attr('id');
	var calIDheight = $(this).parents('.cb-calendar-container').height();
	//$('.cb-hold-calendar-loader').css('opacity', '0.5');
	$('.cb-hold-calendar-loader').animate({
    opacity: 0.5
  }, 200, function() {
  });
	$('#'+calID+' .calendar-day').addClass(' none ');
	$('#'+calID+' .day-number').animate({
    opacity: 0
  }, 200, function() {
  });
	$('#'+calID+' .month-headline').animate({
    opacity: 0
  }, 200, function() {
  });

	var data = {
		'action': 'cb_refresh_calendar',
		'date': $(this).attr('date'),
		'product_id': $(this).attr('product_id'),
		'type': $(this).attr('type'),
		'calID': calID,
		'list_action': $(this).attr('list_action')
		}
	$.post(chbk_bookings_vars.admin_ajax, data, function(response) {
		$('.cb-hold-calendar-loader').animate({
	    opacity: 0
	  }, 200, function() {
	  });
		$('#'+data.calID).replaceWith(response);
		});
});

$(document).on('click','.get-forecast',function(event){
	var data = {
		'action': 'cb_get_forecast'
		}
	$.post(chbk_bookings_vars.admin_ajax, data, function(response) {
    	$('.owf-container').html(response.html);
		});
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
	$.post(chbk_bookings_vars.admin_ajax, data, function(response) {
	    $('.cb-lazyload-global-calendar').replaceWith(response.html);
			$('.cb-hold-calendar-loader').css('z-index', '0');
    });
}

lazyloadGlobalCalendar();

});//end jquery wrapper
