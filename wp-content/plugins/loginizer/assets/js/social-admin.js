
jQuery(document).ready(function() {
	jQuery(".loginizer-social-wrapper").sortable({
		handle : '.loginizer-social-grip',
		placeholder : 'loginizer-sortable-placeholder',
		stop : loginizer_sort_social
	});
});

// Used to sort and save the order of Social Login Apps.
function loginizer_sort_social(event, ui) {
	var target= jQuery(event.target),
	sortables = target.find('.loginizer-social-provider'),
	
	sorted_arr = sortables.map(function() {
		return jQuery(this).data('provider');
	}).get();

	// To show the saving order text.
	let saving_order = jQuery(ui.item).find('.loginizer-social-saving-order');
	saving_order.show();

	jQuery.ajax({
		method : 'POST',
		url : loginizer_social.ajax_url,
		data : {
			security: loginizer_social.nonce,
			order: sorted_arr,
			action: 'loginizer_social_order' 
		},
		success: function(res){
			saving_order.hide();
		}}
	);
}