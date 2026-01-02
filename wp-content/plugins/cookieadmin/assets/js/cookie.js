jQuery(document).ready(function($){
	
	var law = '';
	
	$(".cookieadmin_showmore").on("click", function(){
		if($(".cookieadmin_showmore").html().includes(cookieadmin_policy.lang.show_more)){
			$(".cookieadmin_preference").height("auto");
			$(this).html(cookieadmin_policy.lang.show_less);
		}else{
			$(".cookieadmin_preference").height("");
			$(this).html(cookieadmin_policy.lang.show_more);
		}
	});
	
	$("#cookieadmin_layout_footer").on("change", function(){
		$("input[name=cookieadmin_position]").prop("checked", false);
		$(".consent-position").slideDown();
		$(".cookieadmin_foter_layout").fadeIn(800);
		$(".cookieadmin_box_layout").hide();
		$(".consent-modal-layout").show();
	});
	
	$("#cookieadmin_layout_box").on("change", function(){
		$("input[name=cookieadmin_position]").prop("checked", false);
		$(".consent-position").slideDown();
		$(".cookieadmin_foter_layout").hide();
		$(".cookieadmin_box_layout").fadeIn(800);
		$(".consent-modal-layout").slideDown();
	});

	$("#cookieadmin_layout_popup").on("change", function(){
		$("input[name=cookieadmin_position]").prop("checked", false);
		$(".consent-position").slideUp();
		$(".consent-modal-layout").slideUp();
	});
	
	setTimeout( function(){
		$('.updated, .error').not('.no-autohide').fadeOut('slow');
	}, 5000);

	//==== Notice Section Preview

	$('#cookieadmin_notice_title_layout').on('input', function () {
		const titleValue = $(this).val().trim();
		$('#cookieadmin_notice_title').text(titleValue);
	});

	$('#cookieadmin_notice_layout').on('input', function () {
		const detailValue = $(this).val().trim();
		$('#cookieadmin_notice').text(detailValue);
	});

	$('#cookieadmin_consent_inside_bg_color_box').on('input', function () {
		const color = $(this).val().trim();
		$('.cookieadmin_consent_inside').css('background-color', color);
	});

	$('#cookieadmin_consent_inside_border_color_box').on('input', function () {
		const color = $(this).val().trim();
		$('.cookieadmin_consent_inside').css('border', "1px solid" + color);
	});

	$('#cookieadmin_notice_color_box').on('input', function () {
		const color = $(this).val().trim();
		$('#cookieadmin_notice').css('color', color);
	});

	$('#cookieadmin_notice_title_color_box').on('input', function () {
		const color = $(this).val().trim();
		$('#cookieadmin_notice_title').css('color', color);
	});

	//====== Notice Buttons Preview

	$('#cookieadmin_customize_btn').on('input', function () {
		const textData = $(this).val().trim();
		$('#cookieadmin_customize_button').text(textData);
		$('#cookieadmin_customize_modal_button').text(textData);
	});

	$('#cookieadmin_reject_btn').on('input', function () {
		const textData = $(this).val().trim();
		$('#cookieadmin_reject_button').text(textData);
		$('#cookieadmin_reject_modal_button').text(textData);
	});

	$('#cookieadmin_accept_btn').on('input', function () {
		const textData = $(this).val().trim();
		$('#cookieadmin_accept_button').text(textData);
		$('#cookieadmin_accept_modal_button').text(textData);
	});

	$('#cookieadmin_save_btn').on('input', function () {
		const textData = $(this).val().trim();
		$('#cookieadmin_prf_modal_button').text(textData);
	});

	$('#cookieadmin_customize_btn_color_box').on('input', function () {
		const color = $(this).val().trim();
		$('#cookieadmin_customize_button').css('color', color);
		$('#cookieadmin_customize_modal_button').css('color', color);
	});

	$('#cookieadmin_accept_btn_color_box').on('input', function () {
		const color = $(this).val().trim();
		$('#cookieadmin_accept_button').css('color', color);
		$('#cookieadmin_accept_modal_button').css('color', color);
	});

	$('#cookieadmin_reject_btn_color_box').on('input', function () {
		const color = $(this).val().trim();
		$('#cookieadmin_reject_button').css('color', color);
		$('#cookieadmin_reject_modal_button').css('color', color);
	});

	$('#cookieadmin_save_btn_color_box').on('input', function () {
		const color = $(this).val().trim();
		$('#cookieadmin_prf_modal_button').css('color', color);
	});

	$('#cookieadmin_customize_btn_bg_color_box').on('input', function () {
		const color = $(this).val().trim();
		$('#cookieadmin_customize_button').css('background-color', color);
		$('#cookieadmin_customize_modal_button').css('background-color', color);
	});

	$('#cookieadmin_accept_btn_bg_color_box').on('input', function () {
		const color = $(this).val().trim();
		$('#cookieadmin_accept_button').css('background-color', color);
		$('#cookieadmin_accept_modal_button').css('background-color', color);

	});

	$('#cookieadmin_reject_btn_bg_color_box').on('input', function () {
		const color = $(this).val().trim();
		$('#cookieadmin_reject_button').css('background-color', color);
		$('#cookieadmin_reject_modal_button').css('background-color', color);
	});

	$('#cookieadmin_save_btn_bg_color_box').on('input', function () {
		const color = $(this).val().trim();
		$('#cookieadmin_prf_modal_button').css('background-color', color);
	});

	//======= Preference Section Preview

	$('#cookieadmin_preference_title_layout').on('input', function () {
		const titleValue = $(this).val().trim();
		$('#cookieadmin_preference_title').text(titleValue);
	});

	$('#cookieadmin_preference_layout').on('input', function () {
		const detailValue = $(this).val().trim();
		$('#cookieadmin_preference').text(detailValue);
	});

	$("#cookieadmin_cookie_modal_bg_color_box").on('input', function () {
		const color = $(this).val().trim();
		$(".cookieadmin_cookie_modal").css('background-color', color);
	});

	$("#cookieadmin_cookie_modal_border_color_box").on('input', function () {
		const color = $(this).val().trim();
		$(".cookieadmin_cookie_modal").css('border', "1px solid" + color);
	});

	$("#cookieadmin_details_wrapper_color_box").on('input', function () {
		const color = $(this).val().trim();
		$(".cookieadmin_details_wrapper").css('color', color);
	});

	$("#cookieadmin_preference_title_color_box").on('input', function () {
		const color = $(this).val().trim();
		$("#cookieadmin_preference_title").css('color', color);
	});
	
	//set default or set values in input and preview on page load.
	for(law in cookieadmin_policy){
		
		law = cookieadmin_policy[cookieadmin_policy["set"]];
		
		$("#cookieadmin_consent_type").find("#"+cookieadmin_policy["set"]).attr("selected", true);
		
		$("#cookieadmin_consent_expiry").val(law.cookieadmin_days);
		$("#cookieadmin_layout_"+law.cookieadmin_layout).prop("checked", true).trigger("change");
		
		if(!!law.cookieadmin_position){
			$("#cookieadmin_position_"+law.cookieadmin_position).prop("checked", true);
		}else{
			$(".consent-position").hide();
		}
		$("#cookieadmin_modal_" + law.cookieadmin_modal).prop("checked", true);
		$(".cookieadmin_cookie_modal").addClass("cookieadmin_" + law.cookieadmin_modal);

		$(".cookieadmin_consent_inside").css('background-color', law.cookieadmin_consent_inside_bg_color);
		$("#cookieadmin_notice_title").css('color', law.cookieadmin_notice_title_color);
		$("#cookieadmin_notice").css('color', law.cookieadmin_notice_color);
		$(".cookieadmin_consent_inside").css('border', "1px solid" + law.cookieadmin_consent_inside_border_color);

		$(".cookieadmin_cookie_modal").css('background-color', law.cookieadmin_cookie_modal_bg_color);
		$("#cookieadmin_preference_title").css('color', law.cookieadmin_preference_title_color);
		$(".cookieadmin_details_wrapper").css('color', law.cookieadmin_details_wrapper_color);
		$(".cookieadmin_cookie_modal").css('border', "1px solid" + law.cookieadmin_cookie_modal_border_color);
    
		// $("#cookieadmin_notice_title_layout").val(law.cookieadmin_notice_title);
		$("#cookieadmin_notice_title").html(law.cookieadmin_notice_title);
		
		// $("#cookieadmin_notice_layout").val(law.cookieadmin_notice);
		$("#cookieadmin_notice").html(law.cookieadmin_notice);
		
		// $("#cookieadmin_preference_title_layout").val(law.cookieadmin_preference_title);
		$("#cookieadmin_preference_title").html(law.cookieadmin_preference_title);
		
		// $("#cookieadmin_preference_layout").val(law.cookieadmin_preference);
		$("#cookieadmin_preference").html(law.cookieadmin_preference);

		$(".cookieadmin_customize_btn").text(law.cookieadmin_customize_btn);
		$(".cookieadmin_customize_btn").css('background-color', $("#cookieadmin_customize_btn_bg_color").val());
		$(".cookieadmin_customize_btn").css('color', $("#cookieadmin_customize_btn_color").val());

		$(".cookieadmin_reject_btn").text(law.cookieadmin_reject_btn);
		$(".cookieadmin_reject_btn").css('background-color', $("#cookieadmin_reject_btn_bg_color").val());
		$(".cookieadmin_reject_btn").css('color', $("#cookieadmin_reject_btn_color").val());

		$(".cookieadmin_accept_btn").text(law.cookieadmin_accept_btn);
		$(".cookieadmin_accept_btn").css('background-color', $("#cookieadmin_accept_btn_bg_color").val());
		$(".cookieadmin_accept_btn").css('color', $("#cookieadmin_accept_btn_color").val());

		$(".cookieadmin_save_btn").text(law.cookieadmin_save_btn);
		$(".cookieadmin_save_btn").css('background-color', $("#cookieadmin_save_btn_bg_color").val());
		$(".cookieadmin_save_btn").css('color', $("#cookieadmin_save_btn_color").val());

		$(".cookieadmin_showmore").css('color', law.cookieadmin_links_color);
		$(".act").css('color', law.cookieadmin_links_color);

		const sliders = $("#cookieadmin_wrapper .cookieadmin_slider");
		$(sliders[0]).css('background-color', law.cookieadmin_slider_on_bg_color);
		$(sliders[1]).css('background-color', law.cookieadmin_slider_off_bg_color);
		$(sliders[2]).css('background-color', law.cookieadmin_slider_off_bg_color);
		$(sliders[3]).css('background-color', law.cookieadmin_slider_off_bg_color);

		//Also set layout of consents
		if(!!law.cookieadmin_position){
			$.each(law.cookieadmin_position.split("_"), function(i,v){
				$(".cookieadmin_law_container").addClass("cookieadmin_" + v);
			});
		}
		
		if(!!law.cookieadmin_layout){
			$.each(law.cookieadmin_layout.split("_"), function(i,v){
				$(".cookieadmin_law_container").addClass("cookieadmin_" + v);
			});
		}
		
		cookieadmin_policy['set'] == 'cookieadmin_gdpr' ? $(".setting-prior").show() : $(".setting-prior").hide();
		
		break;
	}
	
	function show_modal(){
		if($(".cookieadmin_cookie_modal").css("display") === "none"){
			$(".cookieadmin_cookie_modal").css("display", "flex");
		}
		else{
			$(".cookieadmin_cookie_modal").css("display", "none");
		}
	}
	
	$("#cookieadmin_show_preview").on("click", function(){
		if($("#cookieadmin_layout_popup").prop("checked")){
			show_modal();
		}else{
			$(".cookieadmin_law_container").toggle();
		}
	});
	
	$(".cookieadmin_customize_btn").on("click", function(){
		show_modal();
	});
	
	$(".cookieadmin_close_pref").on("click", function(){
		$(".cookieadmin_cookie_modal").hide();
	});
	
	$("#cookieadmin_consent_type").on("change", function(){
		
		var law = $("#cookieadmin_consent_type").find(":selected").attr("name");
		
		$("#cookieadmin_consent_expiry").val(cookieadmin_policy[law].cookieadmin_days);
		
		$("[id^=cookieadmin_layout_]").prop("checked", false);
		$("#cookieadmin_layout_"+cookieadmin_policy[law].cookieadmin_layout).prop("checked", true);
		
		if(cookieadmin_policy[law].cookieadmin_layout == "box"){
			$(".cookieadmin_foter_layout").hide();
			$(".cookieadmin_box_layout").show();
		}else{
			$(".cookieadmin_foter_layout").show();
			$(".cookieadmin_box_layout").hide();
		}
		
		$("[id^=cookieadmin_position_]").prop("checked", false);
		$("#cookieadmin_position_"+cookieadmin_policy[law].cookieadmin_position).prop("checked", true);
		
		$("[id^=cookieadmin_modal_]").prop("checked", false);
		$("#cookieadmin_modal_"+cookieadmin_policy[law].cookieadmin_modal).prop("checked", true);
		
		$("#cookieadmin_notice_title_layout").val(cookieadmin_policy[law].cookieadmin_notice_title);
		$("#cookieadmin_notice_layout").val(cookieadmin_policy[law].cookieadmin_notice);

		$("#cookieadmin_notice_title_color_box").val(cookieadmin_policy[law].cookieadmin_notice_title_color);
		$("#cookieadmin_notice_title_color").val(cookieadmin_policy[law].cookieadmin_notice_title_color);
		$("#cookieadmin_notice_color_box").val(cookieadmin_policy[law].cookieadmin_notice_color);
		$("#cookieadmin_notice_color").val(cookieadmin_policy[law].cookieadmin_notice_color);
		$("#cookieadmin_consent_inside_bg_color_box").val(cookieadmin_policy[law].cookieadmin_consent_inside_bg_color);
		$("#cookieadmin_consent_inside_bg_color").val(cookieadmin_policy[law].cookieadmin_consent_inside_bg_color);
		$("#cookieadmin_consent_inside_border_color_box").val(cookieadmin_policy[law].cookieadmin_consent_inside_border_color);
		$("#cookieadmin_consent_inside_border_color").val(cookieadmin_policy[law].cookieadmin_consent_inside_border_color);

		$("#cookieadmin_customize_btn").val(cookieadmin_policy[law].cookieadmin_customize_btn);
		$("#cookieadmin_customize_btn_color_box").val(cookieadmin_policy[law].cookieadmin_customize_btn_color);
		$("#cookieadmin_customize_btn_color").val(cookieadmin_policy[law].cookieadmin_customize_btn_color);
		$("#cookieadmin_customize_btn_bg_color_box").val(cookieadmin_policy[law].cookieadmin_customize_btn_bg_color);		
		$("#cookieadmin_customize_btn_bg_color").val(cookieadmin_policy[law].cookieadmin_customize_btn_bg_color);

		$("#cookieadmin_reject_btn").val(cookieadmin_policy[law].cookieadmin_reject_btn);
		$("#cookieadmin_reject_btn_color_box").val(cookieadmin_policy[law].cookieadmin_reject_btn_color);
		$("#cookieadmin_reject_btn_color").val(cookieadmin_policy[law].cookieadmin_reject_btn_color);
		$("#cookieadmin_reject_btn_bg_color_box").val(cookieadmin_policy[law].cookieadmin_reject_btn_bg_color);
		$("#cookieadmin_reject_btn_bg_color").val(cookieadmin_policy[law].cookieadmin_reject_btn_bg_color);

		$("#cookieadmin_accept_btn").val(cookieadmin_policy[law].cookieadmin_accept_btn);
		$("#cookieadmin_accept_btn_color_box").val(cookieadmin_policy[law].cookieadmin_accept_btn_color);
		$("#cookieadmin_accept_btn_color").val(cookieadmin_policy[law].cookieadmin_accept_btn_color);
		$("#cookieadmin_accept_btn_bg_color_box").val(cookieadmin_policy[law].cookieadmin_accept_btn_bg_color);
		$("#cookieadmin_accept_btn_bg_color").val(cookieadmin_policy[law].cookieadmin_accept_btn_bg_color);

		$("#cookieadmin_save_btn").val(cookieadmin_policy[law].cookieadmin_save_btn);
		$("#cookieadmin_save_btn_color_box").val(cookieadmin_policy[law].cookieadmin_save_btn_color);
		$("#cookieadmin_save_btn_color").val(cookieadmin_policy[law].cookieadmin_save_btn_color);
		$("#cookieadmin_save_btn_bg_color_box").val(cookieadmin_policy[law].cookieadmin_save_btn_bg_color);
		$("#cookieadmin_save_btn_bg_color").val(cookieadmin_policy[law].cookieadmin_save_btn_bg_color);
		
		$("#cookieadmin_preference_title_layout").val(cookieadmin_policy[law].cookieadmin_preference_title);
		$("#cookieadmin_preference_layout").val(cookieadmin_policy[law].cookieadmin_preference);

		$("#cookieadmin_preference_title_color_box").val(cookieadmin_policy[law].cookieadmin_preference_title_color);
		$("#cookieadmin_preference_title_color").val(cookieadmin_policy[law].cookieadmin_preference_title_color);
		$("#cookieadmin_details_wrapper_color_box").val(cookieadmin_policy[law].cookieadmin_details_wrapper_color);
		$("#cookieadmin_details_wrapper_color").val(cookieadmin_policy[law].cookieadmin_details_wrapper_color);
		$("#cookieadmin_cookie_modal_bg_color_box").val(cookieadmin_policy[law].cookieadmin_cookie_modal_bg_color);
		$("#cookieadmin_cookie_modal_bg_color").val(cookieadmin_policy[law].cookieadmin_cookie_modal_bg_color);
		$("#cookieadmin_cookie_modal_border_color_box").val(cookieadmin_policy[law].cookieadmin_cookie_modal_border_color);
		$("#cookieadmin_cookie_modal_border_color").val(cookieadmin_policy[law].cookieadmin_cookie_modal_border_color);

		$("#cookieadmin_slider_on_bg_color_box").val(cookieadmin_policy[law].cookieadmin_slider_on_bg_color);
		$("#cookieadmin_slider_on_bg_color").val(cookieadmin_policy[law].cookieadmin_slider_on_bg_color);
		$("#cookieadmin_slider_off_bg_color_box").val(cookieadmin_policy[law].cookieadmin_slider_off_bg_color);
		$("#cookieadmin_slider_off_bg_color").val(cookieadmin_policy[law].cookieadmin_slider_off_bg_color);

		$("#cookieadmin_links_color_box").val(cookieadmin_policy[law].cookieadmin_links_color);
		$("#cookieadmin_links_color").val(cookieadmin_policy[law].cookieadmin_links_color);
		
		law == 'cookieadmin_gdpr' ? $(".setting-prior").show() : $(".setting-prior").hide();
	});

	$("consent_page").submit(function(){
		var checked = false;
		
		if (!$("input[name=cookieadmin_position]:checked").length && !$("#cookieadmin_layout_popup").prop("checked")) {
			alert('Please select one of the radio buttons.');
			event.preventDefault(); // Prevent form submission
		}
	});
	
	$("[id$=_preload]").on("click", function(){
		
		if(!$(".setting-prior").find("[id$=_preload]:checked").length && $(".cookieadmin-settings").find(".cookieadmin-collapsible-notice").length){
			$(".cookieadmin-settings").find(".cookieadmin-collapsible-notice").remove();
			return;
		}
		
		if(!$(".cookieadmin-settings").find(".cookieadmin-collapsible-notice").length){
			$(".setting-prior").after("<p class=\"cookieadmin-collapsible-notice\">Loading cookies prior to receiving user consent will make your website non-compliant with GDPR.</p>");
			$(".cookieadmin-collapsible-notice").show();
		}
		
	});
	
	$(".cookieadmin-scan").on("click", function(){
		
		let cookieadmin_btn_val = $(this).prop('value');
		$(this).prop("disabled", true).prop('value', 'Scanning...');
		
		$.ajax({
			url: cookieadmin_policy.admin_url,
			method: "POST",
			data : {
				action : 'cookieadmin_ajax_handler',
				cookieadmin_act : 'scan_cookies',
				cookieadmin_security : cookieadmin_policy.cookieadmin_nonce,
			},
			success: function(result){
				
				if (result.success) {
								
					$('.cookieadmin-cookie-categorized tbody').find("tr:gt(1)").remove();
					$.each(result.data, function(index, res){
						
						var row = '';
						exp = 'Session';
						
						if(!!res.expires){
							exp = Math.round((res.expires - Date.now())/86400);
							if(exp < 1 && !!res['Max-Age']){
								exp = res['Max-Age'] + " days";
							}else{
								exp = 'Session';
							}
						}
						
						row = '<tr>';
						row += '<td>' + res.cookie_name + '</td>';
						row += '<td>' + res.description + '</td>';
						row += '<td>' + exp + '</td>';
						row += '<td> <span class="dashicons dashicons-edit cookieadmin_edit_icon" id="edit_'+res.id+'"></span> <span class="dashicons dashicons-trash cookieadmin_delete_icon" id="delete_'+res.id+'"></span> </td>';
						row += '</tr>';
						categorized_cookies[res.id] = res;
						categorized_cookies[res.id]['expires'] = exp;
						
						if(!!res.category){
							type = "#" + res.category.toLowerCase() + "_tbody";
							$(type).find('.cookieadmin-empty-row').remove();
							$(type).append(row);
						}else{
							$("#unknown_tbody").find('.cookieadmin-empty-row').remove();
							$("#unknown_tbody").append(row);
						}
					});
					
				} else {
					alert(result.data.message);
				}
				
			},	
			error: function(xhr, status, error) {
				alert("Request failed: " + error);
			},
			complete: function() {
				$(".cookieadmin-scan").prop("disabled", false).prop("value", cookieadmin_btn_val);
			}
		});
	});
	
	$("#cookieadmin_dialog_save_btn").on("click", function(){
		
		$(this).prop("disabled", true);
		
		let cookie_info = {
			name: $("#cookieadmin-dialog-cookie-name").val(),
			id: $(this).attr("cookieadmin_cookie_id"),
			description: $("#cookieadmin-dialog-cookie-desc").val(),
			duration: $("#cookieadmin-dialog-cookie-duration").val(),
			type: $("#cookieadmin-dialog-cookie-category").val()
		};
		
		$.ajax({
			url: cookieadmin_policy.admin_url,
			method: "POST",
			data : {
				action : 'cookieadmin_ajax_handler',
				cookieadmin_act : 'cookieadmin-edit-cookie',
				cookie_info : cookie_info,
				cookieadmin_security : cookieadmin_policy.cookieadmin_nonce,
			},
			success: function(result){
				
				if(result.success){
						
					var row = '';
										
					row = '<tr>';
					row += '<td>' + cookie_info.name + '</td>';
					row += '<td>' + cookie_info.description + '</td>';
					row += '<td>' + cookie_info.duration + '</td>';
					row += '<td> <span class="dashicons dashicons-edit cookieadmin_edit_icon" id="edit_'+cookie_info.id+'"></span> <span class="dashicons dashicons-trash cookieadmin_delete_icon" id="edit_'+cookie_info.id+'"></span> </td>';
					row += '</tr>';
					
					$("#edit_"+cookie_info.id).parents("tr").remove();
					tbody = "#" + cookie_info.type.toLowerCase() + "_tbody";
					$(tbody).find('.cookieadmin-empty-row').remove();
					$(tbody).append(row);

					categorized_cookies[cookie_info.id]['cookie_name'] = cookie_info.name;
					categorized_cookies[cookie_info.id]['description'] = cookie_info.description;
					categorized_cookies[cookie_info.id]['expires'] = cookie_info.duration;
					categorized_cookies[cookie_info.id]['category'] = cookie_info.type;
					
				}else{
					alert(result.data.message);
				}
			}
		});
		$(this).prop("disabled", false);		
	});
	

	$("input[type=color]").on("input", function(){
		elemt = $(this).attr("id").replace("_box", "");
		$("#"+elemt).val($(this).val());
	});
	
	
	$(document).on("click", ".cookieadmin_delete_icon", function(){
		
		if(confirm("Are you sure you want to delete ?")){
			
			cookie_raw_id = $(this).attr("id").replace("delete_", "");
			
			$.ajax({
				url: cookieadmin_policy.admin_url,
				method: "POST",
				data : {
					action : 'cookieadmin_ajax_handler',
					cookieadmin_act : 'cookieadmin-edit-cookie',
					cookie_raw_id : cookie_raw_id,
					cookieadmin_security : cookieadmin_policy.cookieadmin_nonce,
				},
				success: function(result){
					
					if (result.success) {
						delete categorized_cookies[cookie_raw_id];
					}else{
						alert(result.data.message);
					}
				}
			});
			
			$(this).parents("tr").remove();
		}
	});
	
	
	$(document).on("click", ".cookieadmin_edit_icon, .cookieadmin_dialog_modal_close_btn", function(){
		
		const modal = document.getElementById('edit-cookie-modal');
		
		if(modal.classList.contains("active")){
			modal.classList.remove('active');
		}else{
			modal.classList.add('active');
			var cookie_id = $(this).attr("id").replace("edit_", "");
			
			$("#cookieadmin_dialog_save_btn").attr("cookieadmin_cookie_id", cookie_id);
			$("#cookieadmin-dialog-cookie-name").val(categorized_cookies[cookie_id]['cookie_name']);
			$("#cookieadmin-dialog-cookie-desc").val(categorized_cookies[cookie_id]['description']);
			$("#cookieadmin-dialog-cookie-duration").val(categorized_cookies[cookie_id]['expires']);
			if(!!categorized_cookies[cookie_id]['category']){
				$("#cookieadmin-dialog-cookie-category").val(categorized_cookies[cookie_id]['category']);
			}else{
				$("#cookieadmin-dialog-cookie-category").val("unknown");
			}
		}		
	});
	
	
	$(document).on('click', '.cookieadmin-cookie-categorized tbody > tr:first-child', function() {
	  var $tbody = jQuery(this).closest('tbody');
	  var $rows = $tbody.find('tr:not(:first-child)');

	  if ($rows.is(':visible')) {
  		$rows.slideUp();
  		$tbody.addClass('collapsed');
	  } else {
  		$rows.slideDown();
  		$tbody.removeClass('collapsed');
	  }
	});
	
	
});