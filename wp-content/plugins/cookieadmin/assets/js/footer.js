function cookieadmin_dotweet(ele){
	window.open(jQuery("#"+ele.id).attr("action")+"?"+jQuery("#"+ele.id).serialize(), "_blank", "scrollbars=no, menubar=no, height=400, width=500, resizable=yes, toolbar=no, status=no");
	return false;
}

document.addEventListener("DOMContentLoaded", function() {
	if(!cookieadmin_is_pro){
		jQuery("[cookieadmin-pro-only]").each(function(index) {
			jQuery(this).find( "input, textarea, select" ).attr("disabled", true);
			jQuery(this).find( "input.cookieadmin-color-input" ).css("margin-left", "0px");
		});
	}
});