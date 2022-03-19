jQuery(document).ready(function ($) {

// Author: Meg Phillips;
/* Scope: Javascript that affects public side of wordpress
*/


$(document).on('click','.cb-reservation-tocart',function(event){
	var data = {
		'action': 'cb_reservation_tocart',
		'cb_date': $(this).attr('date'),
    'product_id': $(this).attr('product_id')
		}
	$.post(chbk_public_vars.admin_ajax, data, function(response) {
    	window.location.href = chbk_public_vars.home+'/cart/';
		});
});

$(document).on('click','.cb-fullcharter-tocart',function(event){
	var data = {
		'action': 'cb_fullcharter_tocart',
		'cb_date': $(this).attr('date'),
    'product_id': $(this).attr('product_id')
		}
	$.post(chbk_public_vars.admin_ajax, data, function(response) {
    	window.location.href = chbk_public_vars.home+'/cart/';
		});
});

$(document).on('click','.cb-finalbalance-tocart',function(event){
	var data = {
		'action': 'cb_finalbalance_tocart',
		'booking_id': $(this).attr('booking_id')
		}
	$.post(chbk_public_vars.admin_ajax, data, function(response) {
    	window.location.href = chbk_public_vars.home+'/cart/';
		});
});


//lazyload product summary on single charter booking product page
$( '.lazyload-cb-summary' ).each(function( index ) {
	var data = {
		'action': 'cb_summary',
    'product_id': $(this).attr('product_id')
		}
	$.post(chbk_public_vars.admin_ajax, data, function(response) {
    $(".cb-entry-summary").replaceWith(response.html);
    });
});


//cb-single-book-now-tab
$( '.cb-single-book-now-tab' ).each(function( index ) {
  //console.log( index + ": " + $( this ).attr('product_id') );
	var data = {
		'action': 'cb_book_now_tab',
    'product_id': $(this).attr('product_id')
		}
	$.post(chbk_public_vars.admin_ajax, data, function(response) {
    $(".cb-single-book-now-tab").replaceWith(response.html);
    });
});

//this updates the product listing when an available date on the calendar is clicked
$(document).on('click','.cb-product-calendar-link',function(event){
	$('.current-date').removeClass('current-date');
	var data = {
		'action': 'cb_list_product',
		'cb_date': $(this).attr('date'),
    'product_id': $(this).attr('product_id')
		}
	$.post(chbk_public_vars.admin_ajax, data, function(response) {
    $('.hold-product-listing').replaceWith(response.html);
		$('td[date="'+data.cb_date+'"]').addClass('current-date');
    });
});

//updates the product listing when the arrow is clicked to show tomorrow.
$(document).on('click','.cb-list-product-tomorrow',function(event){
	var data = {
		'action': 'cb_list_product_tomorrow',
		'cb_date': $(this).attr('date'),
    'product_id': $(this).attr('product_id')
		}
	$.post(chbk_public_vars.admin_ajax, data, function(response) {
    $('.hold-product-listing').replaceWith(response.html);
    });
});

//updates the product listing when the arrow is clicked to show yesterday.
$(document).on('click','.cb-list-product-yesterday',function(event){
	var data = {
		'action': 'cb_list_product_yesterday',
		'cb_date': $(this).attr('date'),
    'product_id': $(this).attr('product_id')
		}
	$.post(chbk_public_vars.admin_ajax, data, function(response) {
    $('.hold-product-listing').replaceWith(response.html);
    });
});

});//end jquery wrapper
