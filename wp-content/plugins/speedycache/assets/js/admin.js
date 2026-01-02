(function($){
	window.addEventListener('DOMContentLoaded', function(){
		speedycache_handle_tab();
		
		window.addEventListener('hashchange', speedycache_handle_tab);

		jQuery('.speedycache-save-settings-wrapper button, .speedycache-btn-spl-wrapper button').on('click', speedycache_save_settings);
		jQuery('#speedycache-analyze').on('click', speedycache_analyze_speed);
		
		jQuery('.speedycache-tooltip-link').on('mouseover', function(){
			let jEle = jQuery(this),
			tooltip = jEle.find('.speedycache-link-tooltip'),
			elementHeight = Math.ceil(tooltip.outerHeight()/2);

			tooltip.fadeIn('fast').css({
                top: `-${elementHeight+10}px`, // Position it above the parent
            });
		});
		
		jQuery('.speedycache-tooltip-link').on('mouseleave', function(){
			let jEle = jQuery(this);
			jEle.find('.speedycache-link-tooltip').hide();
		});
		
		// Delay JS
		jQuery('#speedycache_delay_js').on('change', function() {
			if(!jQuery(this).is(':checked')){
				speedycache_toggle_settings_link(jQuery(this));
				return;
			}

			speedycache_toggle_settings_link(jQuery(this));
			speedycache_open_modal(jQuery(this));
		});

		jQuery('#speedycache_purge_varnish').on('change', function() {
			if(!jQuery(this).is(':checked')){
				speedycache_toggle_settings_link(jQuery(this));
				return;
			}
			
			speedycache_toggle_settings_link(jQuery(this));
			speedycache_open_modal(jQuery(this));
		});
		
		jQuery('#speedycache_critical_images').on('change', function() {
			if(!jQuery(this).is(':checked')){
				speedycache_toggle_settings_link(jQuery(this));
				return;
			}
			
			speedycache_toggle_settings_link(jQuery(this));
			speedycache_open_modal(jQuery(this));
		});
		
		jQuery('#speedycache_lazy_load_html').on('change', function() {
			if(!jQuery(this).is(':checked')){
				speedycache_toggle_settings_link(jQuery(this));
				return;
			}
			
			speedycache_toggle_settings_link(jQuery(this));
			speedycache_open_modal(jQuery(this));
		});
		
		jQuery('#speedycache_preload').on('change', function() {
			if(!jQuery(this).is(':checked')){
				speedycache_toggle_settings_link(jQuery(this));
				return;
			}
			
			speedycache_toggle_settings_link(jQuery(this));
			speedycache_open_modal(jQuery(this));
		});
		
		jQuery('#speedycache_dns_prefetch').on('change', function() {
			if(!jQuery(this).is(':checked')){
				speedycache_toggle_settings_link(jQuery(this));
				return;
			}
			
			speedycache_toggle_settings_link(jQuery(this));
			speedycache_open_modal(jQuery(this));
		});

		jQuery('#speedycache_speculative_loading').on('change',function(){
			if(!jQuery(this).is(':checked')){
				speedycache_toggle_settings_link(jQuery(this));
				return;
			}

			speedycache_toggle_settings_link(jQuery(this));
			speedycache_open_modal(jQuery(this));
			
			let instant_page = jQuery('#speedycache_instant_page');
			if(instant_page && instant_page.is(':checked')){
				instant_page.prop('checked', false);
				alert('Instant page and Speculation loading are similar feature, so you should not use them together');
			}
		})
		
		jQuery('#speedycache_preload_resources').on('change', function() {
			if(!jQuery(this).is(':checked')){
				speedycache_toggle_settings_link(jQuery(this));
				return;
			}
			
			speedycache_toggle_settings_link(jQuery(this));
			speedycache_open_modal(jQuery(this));
		});

		jQuery('#speedycache_render_blocking').on('change', function() {
			if(!jQuery(this).is(':checked')){
				speedycache_toggle_settings_link(jQuery(this));
				return;
			}
			
			speedycache_toggle_settings_link(jQuery(this));
			speedycache_open_modal(jQuery(this));
		});
		
		// Critical CSS Status
		jQuery('#speedycache_critical_css').on('change', function(e) {
			let prevent_open = true;

			if(e.isTrigger){
				prevent_open = false;			
			}
			
			if(!jQuery(this).is(':checked')){
				speedycache_toggle_settings_link(jQuery(this));
				return;
			}

			speedycache_toggle_settings_link(jQuery(this));
			speedycache_open_modal(jQuery(this), prevent_open);
		});
		
		jQuery('#speedycache_pre_connect').on('change', function() {
			if(!jQuery(this).is(':checked')){
				speedycache_toggle_settings_link(jQuery(this));
				return;
			}
			
			speedycache_toggle_settings_link(jQuery(this));
			speedycache_open_modal(jQuery(this));
		});
		
		jQuery('#speedycache_unused_css').on('change', function() {
			if(!jQuery(this).is(':checked')){
				speedycache_toggle_settings_link(jQuery(this));
				return;
			}
			
			speedycache_toggle_settings_link(jQuery(this));
			speedycache_open_modal(jQuery(this));
		});
		
		jQuery('.speedycache-action-link').on('click', function(){
			let action_name = jQuery(this).attr('action-name');

			switch(action_name){
				case 'speedycache_critical_css':
					speedycache_critical_css();
					break;
			}
		});
		
		// Lazy Load
		jQuery('#speedycache_lazy_load').on('change', function() {
			if(!jQuery(this).is(':checked')){
				speedycache_toggle_settings_link(jQuery(this));
				return;
			}
			
			speedycache_toggle_settings_link(jQuery(this));
			speedycache_open_modal(jQuery(this));
		});
		
		jQuery('#speedycache_update_heartbeat').on('change', function() {
			if(!jQuery(this).is(':checked')){
				speedycache_toggle_settings_link(jQuery(this));
				return;
			}
			
			speedycache_toggle_settings_link(jQuery(this));
			speedycache_open_modal(jQuery(this));
		});
		
		jQuery('#speedycache_limit_post_revision').on('change', function() {
			if(!jQuery(this).is(':checked')){
				speedycache_toggle_settings_link(jQuery(this));
				return;
			}
			
			speedycache_toggle_settings_link(jQuery(this));
			speedycache_open_modal(jQuery(this));
		});

		// Add Suggested Scripts
		var $textarea = jQuery('#speedycache_delay_js_scripts');

		jQuery('.speedycache-delay-suggestions').on('click', function(event) {
			event.preventDefault();
			if($textarea.length){
				var $suggestions_text = jQuery('.speedycache-modal-scripts').text().trim().replace(/<br\s*\/?>/gi, '\n').replace(/\n\s+/g, '\n').replace(/\s+\n/g, '\n').replace(/\n+/g, '\n'),
				current_text = $textarea.val().trim(),
				new_text = current_text ? current_text + '\n' + $suggestions_text : $suggestions_text;
				//Only add unique values
				var unique_text = [...new Set(new_text.split('\n'))].join('\n');
				$textarea.val(unique_text);
			}
		});
		
		//Event Listener for Settings link for popup options
		jQuery('.speedycache-modal-settings-link').off('click').on('click', function() {
			var id = jQuery(this).attr('setting-id'),
			input = jQuery('#'+id);
			
			input.trigger('change');
		});
		
		// Add Excludes Btn
		jQuery('#speedycache_add_excludes').on('click', function() {
			speedycache_open_modal(jQuery(this));
		});
		
		jQuery('#speedycache-cdn-type').on('change', function(e){

			let cdn_type = jQuery(e.target).val(),
			cdn_key = jQuery('#speedycache-cdn-key').closest('.speedycache-stacked-option-wrap'),
			cdn_url = jQuery('#speedycache-cdn-url').closest('.speedycache-stacked-option-wrap');

			if(cdn_type == 'cloudflare'){
				cdn_url.hide();
				cdn_key.show();
				
				return;
			}else if(cdn_type == 'bunny'){
				cdn_url.show();
				cdn_key.show();

				return;
			}
			
			// For other we only need to show the URL field
			cdn_url.show();
			cdn_key.hide();
			
			return;
		});
		
		// Toggle exact time field of cache lifespan
		jQuery('#speedycache-run-exact-time').on('click', function(e){
			if(jQuery(this).is(':checked')){
				jQuery('#speedycache-exact-time-selector').css('display', 'flex');
				return;
			}

			jQuery('#speedycache-exact-time-selector').hide();
		});
		
		jQuery('#speedycache-ll-type').on('change', function(e){
			let type = jQuery(e.target).val(),
			custom_input = jQuery('#speedycache-custom-ll-url');

			if(type == 'default'){
				custom_input.hide();
				return;
			}
			
			custom_input.show();
		});

		jQuery('#speedycache-import-export').on('change', function(e){
      let task = jQuery(e.target).val(),
			import_block = jQuery('.speedycache-import-block'),
			export_block = jQuery('.speedycache-export-block');

			if(task == 'import'){
				import_block.show();
				export_block.hide();
			} else if(task == 'export'){
				export_block.show();
				import_block.hide();
			}
		})
		
		jQuery('#speedycache-cdn-type').trigger('change');
		
		jQuery('#speedycache-exclude-type').on('change', speedycache_update_excluded_options);
		jQuery('#speedycache-exclude-rule-prefix').on('change', speedycache_update_excluded_prefix);
		jQuery('.speedycache-exclude-btn-wrap button').on('click', speedycache_update_excludes);
		jQuery('#speedycache-type-filter').on('change', speedycache_filter_exclude_type);
		jQuery(document).on('click', '.speedycache-delete-rule', speedycache_delete_exclude_rule);
		jQuery('.speedycache-db-optm-btn').on('click', speedycache_db_optm);
		jQuery('.speedycache-preloading-add').on('click', speedycache_add_preload_resource);
		jQuery('.speedycache-preloading-table').on('click', '.dashicons-trash', speedycache_delete_preload_resource);
		jQuery('.speedycache-flush-db').on('click', speedycache_flush_objects);
		jQuery('.speedycache-import-settings').on('click', speedycache_import_settings);
		jQuery('.speedycache-export-settings').on('click', speedycache_export_settings);
		jQuery('#speedycache-license-btn').on('click', speedycache_verify_license);
	});
})(jQuery);

function speedycache_handle_tab(){

	let hash = location.hash.trim().replace('#', ''),
	nav = jQuery('#speedycache-navigation');

	if(!hash.length){
		let tab = jQuery('#speedycache-dashboard');
		
		tab.siblings().hide();
		tab.css('display', 'flex');
		nav.find('.speedycache-nav-selected').removeClass('speedycache-nav-selected');
		nav.find('a[href=\\#'+hash+']').addClass('speedycache-nav-selected')
		return
	}
	
	let tab = jQuery('#speedycache-'+hash);
	
	// Loading the stats for DB tab
	if(hash == 'db' && typeof speedycache_pro_get_db_optm === 'function'){
		speedycache_pro_get_db_optm();
	}
	
	// Loading the stats for image optm tab
	if(hash == 'image' && !speedycache_ajax.load_img){
		speedycache_ajax.load_img = true;
		speedycache_image_optimization();
	}

	tab.siblings().hide();
	tab.css('display', 'flex');
	nav.find('.speedycache-nav-selected').removeClass('speedycache-nav-selected');
	nav.find('a[href=\\#'+hash+']').addClass('speedycache-nav-selected')

}

function speedycache_save_settings(){
	event.preventDefault();

	let jEle = jQuery(event.target),
	has_error = false;
	
	jEle.find('span.speedycache-spinner').addClass('speedycache-spinner-active');
	
	form_data = jEle.closest('form').serializeArray();

	jQuery.ajax({
		url : speedycache_ajax.url,
		method : "POST",
		data : form_data,
		success: function(res){
			if(res.success){
				return;
			}
			
			has_error = true;
			if(res.data){
				alert(res.data);
			}
			
			alert("Something went wrong");
		}
	}).always(function(){
		jEle.find('span.speedycache-spinner')?.removeClass('speedycache-spinner-active');
		
		// Need to show a tick if the save was success
		if(!has_error){
			let check = jEle.find('svg.speedycache-spinner-done');
      if(check){
  			check.addClass('speedycache-spinner-done-active');
  			setTimeout(() => {
  				check.removeClass('speedycache-spinner-done-active');
  			}, 2000);
      }
		}
	});
}

function speedycache_filter_exclude_type(){
	let jEle = jQuery(event.target),
	list = jQuery('.speedycache-exclude-list'),
	filter = jEle.val();

	list.find('tbody tr').filter(function(){
		jQuery(this).toggle(jQuery(this).find('td').eq(0).text().toLowerCase().indexOf(filter) > - 1);
	});
}

function speedycache_delete_exclude_rule(){
	event.preventDefault();

	let jEle = jQuery(event.target),
	tr = jEle.closest('tr'),
	rule_id = tr.data('id')
	
	jEle.find('span').addClass('speedycache-spinner-active');
	

	jQuery.ajax({
		url : speedycache_ajax.url,
		method : "POST",
		data : {
			'_ajax_nonce' : speedycache_ajax.nonce,
			'action' : 'speedycache_delete_exclude_rule',
			'rule_id' : rule_id			
		},
		success: function(res){
			if(res.success){
				tr.slideUp();
				return;
			}
			
			if(res.data){
				alert(res.data);
				return;
			}

			alert('Something went wrong deleting the rule');
		}
	}).always(function(){
		jEle.find('span').removeClass('speedycache-spinner-active');
	});
}

function speedycache_toggle_settings_link(jEle) {
	var wrap = jEle.closest('.speedycache-option-wrap'),
	setting = wrap.find('.speedycache-modal-settings-link, .speedycache-action-link');
	
	if(jEle.is(':checked')) {
		setting.show();
		return;
	}
	
	setting.hide();
}

function speedycache_open_modal(jEle, prevent_open) {
	var id_attr = 'id';
	
	if(prevent_open){
		return;
	}
	
	if(jEle.attr('modal-id')) {
		id_attr = 'modal-id'
	}
	
	//For Settings Link
	if(jEle.attr('setting-id')) {
		id_attr = 'setting-id';
	}
	
	var modal_id = jEle.attr(id_attr),
	speedycache_modal = jQuery("div[modal-id='"+modal_id+"']");
	
	if(speedycache_modal && speedycache_modal.css('visibility') === 'hidden') {
		speedycache_modal.css('visibility','visible');
		speedycache_close_modal();
	}
}

function speedycache_update_excluded_options(){
	let jEle = jQuery(event.target),
	prefix = jQuery('#speedycache-exclude-rule-prefix'),
	exclude_type = jEle.val();

	prefix.val(""); // Resets to select value option
	prefix.find('option').filter(function(){
		jQuery(this).toggle(jQuery(this).data('partof').toLowerCase().indexOf(exclude_type) > - 1);
	});
}

// Toggles content input of excludes settings
function speedycache_update_excluded_prefix(){
	let jEle = jQuery(event.target),
	val = jEle.val(),
	content = jQuery('[for="speedycache-exclude-rule-content"]').closest('.speedycache-input-wrap');
	
	if(val == 'contain' || val == 'exact' || val == 'startwith' || val == 'post_id' || val == 'shortcode'){
		content.show();
		content.find('textarea, input')?.remove();
		let input = jQuery('<input>', {
			type: 'text',
			name: 'content',
			id: 'speedycache-exclude-rule-content',
			'class': 'speedycache-100',
		});
		content.append(input);
		return;
	}

	content.hide();
}

function speedycache_update_excludes(){
	event.preventDefault();
	
	let jEle = jQuery(event.target),
	form = jEle.closest('form');
	
	jEle.find('span').addClass('speedycache-spinner-active');
	
	form_data = form.serializeArray();
	let prefix_field = form_data.find(field => field.name === 'prefix');
	let content_field = form_data.find(field => field.name === 'content');

	if (prefix_field && prefix_field.value === 'post_id' && content_field) {
		if (!/^\d+(,\d+)*$/.test(content_field.value)) {
			alert("Invalid format! Only numbers and commas are allowed, without starting, ending, or consecutive commas.");
			jEle.find('span').removeClass('speedycache-spinner-active');
			return;
		}
	}

	jQuery.ajax({
		url : speedycache_ajax.url,
		method : "POST",
		data : form_data,
		success: function(res){
			
			if(res.success){
				form.trigger('reset');
				jQuery('#speedycache-exclude-list').load(window.location.href + ' #speedycache-exclude-list');
				return;
			}
			
			if(res.data){
				alert(res.data);
				return;
			}

			alert('Something went wrong saving the details');
			
		}
	}).always(function(){
		jEle.find('span').removeClass('speedycache-spinner-active');
	});
}

//Close SpeedyCache Modal
function speedycache_close_modal() {
	jQuery('.speedycache-modal-footer > button, .speedycache-close-modal').on('click', function() {

		//Remove duplicate entries when Submit or Close Button is clicked
		var $textarea = jQuery(this).closest('.speedycache-modal').find('#speedycache_delay_js_scripts');
		if ($textarea.length) {
			var current_text = $textarea.val().trim();
			var unique_text = [...new Set(current_text.split('\n'))].join('\n');
			$textarea.val(unique_text);
		}

		jQuery(this).closest('.speedycache-modal').find('form').trigger('reset');
		jQuery(this).closest('.speedycache-modal *').off();
		jQuery(this).closest('.speedycache-modal').css('visibility','hidden');
	});
}

function speedycache_analyze_speed(){
	jEle = jQuery(event.target);
	jEle.text('[Analysing...]');
	
	jQuery.ajax({
		url : speedycache_ajax.url,
		method : 'GET',
		data : {
			security : speedycache_ajax.nonce,
			action : 'speedycache_test_pagespeed',
		},
		success : function(res){

			if(!res.data || !res.data['score']){
				return
			}

			let donut = jQuery('.speedycache-perf-score-donut'),
			tspan = donut.find('tspan'),
			lowerCircle = donut.find('circle:first-child'),
			strokeCircle = lowerCircle.next();

			lowerCircle.attr('fill', res.data['color'][1]);
			strokeCircle.css('stroke', res.data['color'][0]);
			strokeCircle.attr('stroke-dasharray', res.data['score']+' '+(100 - res.data['score']));

			tspan.text(res.data['score']); // Updated the score
			tspan.css('fill', res.data['color'][2]);
			
		}
	}).always(function(){
		jEle.text('[Updating results]');
		setTimeout(() => {jEle.text('[Analyse]')}, 1000)
		
	});
}

function speedycache_db_optm(){
	event.preventDefault();
	
	let proceed = confirm('Are you sure you want to proceed with this DB optimization action');

	if(!proceed){
		return;
	}
	
	let jEle = jQuery(event.target),
	db_action = jEle.closest('.speedycache-db-row').attr('speedycache-db-name'),
	spinner = jEle.find('.speedycache-spinner');
	spinner.addClass('speedycache-spinner-active');

	jQuery.ajax({
		url : speedycache_ajax.url,
		method : 'POST',
		data : {
			security : speedycache_ajax.nonce,
			action : 'speedycache_optm_db',
			db_action : db_action,
		},
		success: function(res){			
			if(res.success){
				// TODO: make this update the UI too chaning the numbers.
				return false;
			}
			
			if(res.message){
				alert(res.message);
				return;
			}

			alert("Something went wrong unable to optimize this option");
		}
	}).always(function(){
		spinner.removeClass('speedycache-spinner-active');
	});
}

function speedycache_add_preload_resource() {
	event.preventDefault();
	
	let ele = jQuery(event.target),
	loader = ele.find('.speedycache-spinner'),
	form = ele.closest('form'),
	error = false;

	if(!form){
		alert('Unable to get the form details!');
		return;
	}
	
	let form_type = form.data('type');

	// Disabling Add Button
	ele.prop('disabled', true);

	let form_val = {};
	form_data = form.serializeArray();
	
	form_data.forEach((field) => {
		form_val[field.name] = field.value;
		
		let non_required_fields = ['fetch_priority', 'device'];
		if(!field.value && !non_required_fields.includes(field.name)){
			error = true;
		}
	});

	if(error){
		alert('Fill all the fields before adding');
		ele.prop('disabled', false);
		return;
	}
	loader.addClass('speedycache-spinner-active');

	jQuery.ajax({
		'method' : 'POST',
		'url' : speedycache_ajax.url,
		'data' : {
			action : 'speedycache_preloading_add_settings',
			settings : form_val,
			type : form_type,
			security : speedycache_ajax.nonce
		},
		'success' : function(res){
			ele.prop('disabled', false);
			
			if(!res){
				alert('Something went wrong, the response returned is empty');
				return;
			}
			
			if(!res.success){
				alert(res.data);
				return;
			}
			
			let table = ele.closest('.speedycache-modal-content').find('table');
			
			html = `<td>${form_val.resource}</td>
				
				${form_type != 'pre_connect_list' ? '<td>'+form_val.type+'</td>' : ''} 
				<td>${form_val.crossorigin ? 'Yes' : 'No'}</td>
				${form_type != 'pre_connect_list' ? '<td>'+(form_val.fetch_priority ? form_val.fetch_priority : 'Auto')+'</td><td>'+(form_val.device ? form_val.device : 'All')+'</td>' : ''}
				<td data-key="${res.data}"><span class="dashicons dashicons-trash"></span></td>`;
			
			
			if(table.find('.speedycache-preloading-empty').length  > 0){
				let tr = table.find('.speedycache-preloading-empty').closest('tr');
				table.find('.speedycache-preloading-empty').remove();
				
				tr.append(html);
			} else {
				let tbody = table.find('tbody');

				tbody.append('<tr>'+html+'</tr>');
			}
			
			// Resetting the form
			form.find('input, select').map(function(){
				let type = jQuery(this).prop('type');
				
				if(type == 'checkbox'){
					jQuery(this).prop('checked', false);
					return;
				} else 
				
				jQuery(this).val('');
				
			});

			alert('Settings Saved Successfully');
		}
	}).always(function(){
		loader.removeClass('speedycache-spinner-active');
	});
}

function speedycache_delete_preload_resource(){
	let ele = jQuery(event.target),
	key = ele.closest('td').data('key'),
	type = ele.closest('table').data('type'),
	tr = ele.closest('tr');

	tr.css('backgroundColor', 'rgba(255,0,0,0.2)');

	jQuery.ajax({
		'method' : 'POST',
		'url' : speedycache_ajax.url,
		'data' : {
			action : 'speedycache_preloading_delete_resource',
			type : type,
			key : key,
			security : speedycache_ajax.nonce
		},
		success : function(res){
			if(!res || !res.success){
				alert(res.data ? res.data : 'Unable to delete this resource');
				return;
			}
			
			ele.closest('tr').remove();
		}
	});
}

function speedycache_flush_objects() {
	event.preventDefault();

	let jEle = jQuery(event.target),
	spinner = jEle.find('.speedycache-spinner');
	spinner.addClass('speedycache-spinner-active');
	
	jQuery.ajax({
		'method' : 'GET',
		'url' : speedycache_ajax.url + '?action=speedycache_flush_objects&security='+speedycache_ajax.nonce,
		'success' : function(res){
			if(res.success){
				return;
			}
			
			if(res.data){
				alert(res.data);
				return;
			}
			
			alert("Unable to flush Object Cache");
			
		}
  }).always(function(){
		spinner.removeClass('speedycache-spinner-active');
	});
}

function speedycache_import_settings(){
	event.preventDefault();

	let jEle = jQuery(event.target),
	spinner = jEle.find('.speedycache-spinner');
	spinner.addClass('.speedycache-spinner-active');
	let fileInput = jQuery('#speedycache_import_file')[0];

	if(!fileInput.files.length){
		alert('Please select a JSON file to import.');
		return;
	}
	
	if(fileInput.files[0].type && fileInput.files[0].type != 'application/json'){
		alert('The file you have uploaded is not a JSON file.');
		return;
	}

	if(!fileInput.files[0].size){
		alert('Your settings file is empty.');
		return;
	}
	
	let expected_file_name_reg = /speedycache-settings-\d{4}-\d{2}-\d{2}.*\.json/;
	if(fileInput.files[0].name && !expected_file_name_reg.test(fileInput.files[0].name)){
		alert('The format of the name of the file is not valid.');
		return;
	}

	let formData = new FormData();
	formData.append('security', speedycache_ajax.nonce);
	formData.append('file', fileInput.files[0]);

	jQuery.ajax({
		url : speedycache_ajax.url + '?action=speedycache_import_settings',
		type : 'POST',
		data : formData,
		processData : false,
		contentType : false,
		success : function(response){
			if(response.success){
				alert('Settings imported successfully');
				location.reload();
			} else {
				alert(response.data || 'Something went wrong while importing.');
			}
		},
		error: function(){
			alert('Ajax error occurred');
		}
	}).always(function(){
		spinner.removeClass('speedycache-spinner-active');
	})
}

function speedycache_export_settings(){
  event.preventDefault();

	let jEle = jQuery(event.target),
	spinner = jEle.find('.speedycache-spinner');
	spinner.addClass('speedycache-spinner-active');

	jQuery.ajax({
		url : speedycache_ajax.url + '?action=speedycache_export_settings',
		type : 'POST',
		data : {
			'security' : speedycache_ajax.nonce
		},
		success: function(response){
			const blob = new Blob([JSON.stringify(response.data, null, 2)], { type: 'application/json' });
			const link = document.createElement('a');
			link.href = URL.createObjectURL(blob);
			link.download = 'speedycache-settings-' + new Date().toISOString().slice(0,10) + '.json';
			document.body.appendChild(link);
			link.click();
			document.body.removeChild(link);
		},
		error: function(){
			alert('Export failed. Please try again.');
		}
	}).always(function(){
		spinner.removeClass('speedycache-spinner-active');
	});
}

function speedycache_image_optimization() {
	var stats,
		total_page = {
			value: 0,
			set: function (value) {
				this.value = value;
				this.update_num();
				disabling_paging_btn(jQuery('#speedycache-image-list'));
			},
			update_num : function(){
				jQuery('.speedycache-total-pages').text(this.value);
			}
		},
		current_page = {
			value: 0,
			set: function (value) {
				this.value = value;
				this.update_num();
				disabling_paging_btn(jQuery('#speedycache-image-list'));
			},
			update_num : function(){
				jQuery('.speedycache-current-page').text(this.value+1);
			}
		};
	
	//Gets Stats
	var get_stats = function(onload = false) {
		jQuery.ajax({
			type : 'GET',
			url : speedycache_ajax.url + '?action=speedycache_statics_ajax_request',
			cache : false,
			data : {
				'security' : speedycache_ajax.nonce
			},
			beforeSend: function(){
				jQuery('.speedycache-img-stat-update-status').show();	
			},
			success : function(res){
				stats = res;

				jQuery('.speedycache-img-stat-update-status').hide();

				//For pagination
				var $total_page = jQuery('.speedycache-total-pages'),
				optimized = res.optimized
				$total_page.text(Math.ceil(optimized/5));
				total_page.set($total_page.text());
				
				if(total_page == '1') {
					jQuery('.speedycache-image-list-next-page').addClass('disabled');
					jQuery('.speedycache-image-list-last-page').addClass('disabled');
				}
			
				if(!onload) {
					optm_count = `${optimized}/${stats.total_image_number}`;
					jQuery('.speedycache-img-optm-count').text(optm_count);
					
					reduction = res.reduction > 10000 ? (res.reduction/1000).toFixed(2) + 'MB' : res.reduction.toFixed(2) + 'KB';

					var stat_block = jQuery('.speedycache-img-stats');
					
					stat_block.find('.speedycache-img-reduced-size').text(reduction);
					stat_block.find('.speedycache-donut-percent').text(res.percent + '%');
					stat_block.find('.speedycache-img-success-per').text(res.percent + '%');
					stat_block.find('.speedycache-img-error-count').text(res.error);
					
					var sub = 100 - parseInt(res.percent);
					
					stat_block.find('.speedycache-donut-segment-2').attr('stroke-dasharray', res.percent+' '+sub);
					var donut_style = stat_block.closest('.speedycache-tab-image').find('style').eq(0);
					
					//this regex wont work in PHP as it dosent supports look behind without fixed size
					var dash_array = donut_style.text();
					
					//(?<=100%\s*{(?:\s*|\n)stroke-dasharray\s*:\s*)([\d]+\s*[\d]+[^;]) this reg ex can be used too its more precise and gets just numbers but need to update it to handle floats
					dash_array = dash_array.replace(/100%.*(?:[\d]|[\d]+\.[\d]+)[^;]/, `100%{stroke-dasharray:${res.percent}, ${sub}`);
				
					var segment = stat_block.find('.speedycache-donut-segment-2');
					segment.removeClass('speedycache-donut-segment-2');
					segment.addClass('speedycache-donut-segment-2');
					
					donut_style.text(dash_array);
				}
			
				if(res.uncompressed > 0) {
					jQuery('.speedycache_img_optm_status').css('backgroundColor', '#EED202');
					jQuery('.speedycache_img_optm_status').next().text(`${res.uncompressed} File(s) needed to be optimized`);
				}else {
					jQuery('.speedycache_img_optm_status').css('backgroundColor', '#90ee90');
					jQuery('.speedycache_img_optm_status').next().text(`All images are optimized`);
				}
			}
		});
	}

	//Updates Image Optimization Stats on load
	get_stats();
	
	jQuery('.speedycache-img-opt-settings input').on('change', function() {
		
		var settings = jQuery('.speedycache-img-opt-settings').serializeArray();
		settings = speedycache_convert_serialized(settings);
		
		jQuery.ajax({
			type: 'POST',
			url : speedycache_ajax.url + '?action=speedycache_update_image_settings',
			data : {
				'security' : speedycache_ajax.nonce,
				'settings' : settings
			},
			success: function(res) {
				//Succeed or Fail silently
			}
		});
	});
	
	var file_counter = 1,
	optm_stopped = false,
	optm_ajax;
	
	jQuery('.speedycache-img-optm-btn').on('click', function() {
		if(optm_ajax && optm_stopped) {
			optm_ajax.abort();
			optm_stopped = false;
			file_counter = 1;

			return;
		}
		
		var inner_content = `
			<div class="speedycache-img-optm-counter">${file_counter - 1}/${stats.uncompressed}</div>
			<div class="speedycache-progress">
				<div class="speedycache-progress-value"></div>
			</div>
			<div class="speedycache-optm-close">
				<button class="speedycache-button speedycache-image-optm-stop speedycache-btn-black">Stop</button>
				<button class="speedycache-button speedycache-img-optm-close">Close</button></div>
			</div>`;
		
		
		//If all images are optimized
		if(stats.uncompressed == 0) {
			inner_content = `
			<div class="speedycache-already-optm">
				<span class="dashicons dashicons-yes-alt"></span>
				<span>All images are Optimized</span>
			</div>
			<div class="speedycache-optm-close">
				<button class="speedycache-btn speedycache-btn-success speedycache-img-optm-close" style="display:block;">Close</button></div>
			</div>
			`;
		}
		
		var inc_per = parseInt(100/stats.uncompressed),
		modal_html = `<div modal-id="speedycache-modal-optimize-all" class="speedycache-modal">
			<div class="speedycache-modal-wrap" style="padding:10px;">
				<div style="text-align:center;"><h2>Optimizing Images</h2></div>
					<div class="speedycache-optm-prog-list">
					</div>
					${inner_content}
			</div>
		</div>`;
		
		var optm_modal = jQuery('[modal-id="speedycache-modal-optimize-all"]');
		
		if(optm_modal.length == 0) {
			jQuery('body').append(modal_html);
			speedycache_open_modal(jQuery(this));
			optm_modal = jQuery('[modal-id="speedycache-modal-optimize-all"]');
		}
		
		optm_modal.find('.speedycache-optm-close button').off('click').on('click', function() {
			optm_modal.remove();
			speedycache_update_list();
			get_stats();
			
			if(stats.uncompressed != 0) {
				optm_stopped = true;
			}
			
			file_counter++;
		});
		
		optm_ajax = jQuery.ajax({
			type : 'POST',
			url : speedycache_ajax.url + '?action=speedycache_optimize_image_ajax_request',
			data : {
				'id' : null,
				'security' : speedycache_ajax.nonce
			},
			success: function(res) {
				var progress = jQuery('[modal-id="speedycache-modal-optimize-all"] .speedycache-progress-value'),
				new_per = file_counter * inc_per;
				progress.css('width', `${new_per}%`);
				
				file_counter++
				
				var modal = progress.closest('.speedycache-modal-wrap');
				
				if(!res.id && res.message != 'finish') {
					var error_html = `<div class="speedycache-img-optm-error">
						<p>Something Went Wrong<br/>
							${res.message}
						</p>
					</div>`;
					
					progress.parent().before(error_html);
					progress.css({'width': '100%', 'backgroundColor' : 'var(--speedycache-red)'});
					
					setTimeout( () => {
						optm_modal.find('.speedycache-img-optm-close').show();
						optm_modal.find('.speedycache-image-optm-stop').hide();
					},700);
					
					return;
				} 
	
				if(res.message != 'finish' && file_counter <= stats.uncompressed + 1) {
					modal.find('.speedycache-img-optm-counter').text((file_counter) - 1 +'/'+stats.uncompressed);
					
					jQuery('.speedycache-img-optm-btn').trigger('click');
					return;
				}
				
				progress.css('width', '100%');
				
				//To show when Optimization completes
				var success_html = `
				<div class="speedycache-already-optm" style="display:none;">
					<span class="dashicons dashicons-yes-alt"></span>
					<span>Images optimized Successfully</span>
				</div>
				`;
				
				progress.parent().before(success_html);
				modal.find('.speedycache-img-optm-counter').hide('slow');
				modal.find('.speedycache-already-optm').show('slow');
				
				setTimeout( () => {
					optm_modal.find('.speedycache-img-optm-close').show();
					optm_modal.find('.speedycache-image-optm-stop').hide();
				},700);
			}
		});
	});
	
	//revert Image
	var revert_image = function() {
		var jEle = jQuery(this),
		post_id = jEle.find('input').val();
	
		if(!post_id) {
			return;
		}
		
		//speedycache_add_loader();
		
		jQuery.ajax({
			type : 'GET',
			url : speedycache_ajax.url + '?action=speedycache_revert_image_ajax_request&id='+post_id,
			data : {
				'security' : speedycache_ajax.nonce,
			},
			beforeSend : function(){
				jEle.closest('tr').css('backgroundColor', 'rgba(255,0,0,0.2)');
			},
			success : function(res) {
				speedycache_update_list(jEle);
				get_stats();
				//speedycache_hide_loader();
			},
			error: function(err) {
				//speedycache_hide_loader();
				jEle.closest('tr').css('backgroundColor', 'rgb(255,255,255)');
			}
		});
	}
	
	//Revert the image conversion listener
	jQuery('.speedycache-revert').on('click', revert_image);
	
	jQuery('.speedycache-img-delete-all-conv').on('click', function(e) {
		e.preventDefault();
		
		var confirm_modal = jQuery('[modal-id="speedycache-modal-all-img-revert"]');
		
		if(confirm_modal.length == 0) {
			return;
		}
		
		speedycache_open_modal(confirm_modal);
		
		confirm_modal.find('.speedycache-db-confirm-yes').off().on('click', function() {
			//speedycache_add_loader();
			confirm_modal.css('visibility','hidden');
			
			jQuery.ajax({
				type : 'GET',
				url : speedycache_ajax.url + '?action=speedycache_img_revert_all',
				data : {
					'security' : speedycache_ajax.nonce
				},
				success : function(res) {
					
					if(res.success) {
						//speedycache_hide_loader();
						speedycache_update_list();
						get_stats();
						return;
					}
				
					//speedycache_hide_loader();
					alert(res.message);
				}
			});
		});
		
		confirm_modal.find('.speedycache-db-confirm-no').off().on('click', function() {
			confirm_modal.css('visibility','hidden');
		});
	});	
	
	var speedycache_update_list = function(jEle = null) {
		var img_list = jQuery('#speedycache-image-list'),
			search = img_list.find('#speedycache-image-search-input'),
			per_page = img_list.find('#speedycache-image-per-page'),
			per_page_val = per_page.val() ? per_page.val() : 5,
			filter = img_list.find('#speedycache-image-list-filter'),
			page = 0;
			
		if(jEle) {	
			if(jEle.hasClass('disabled')) {
				return;
			}	
			
			if(jEle.data('page-action')) {
				switch(jEle.data('page-action')) {
					case 'last-page':
						current_page.set(total_page.value - 1);
						break;
						
					case 'next-page':
						current_page.set(current_page.value + 1);
						break;
					
					case 'first-page':
						current_page.set(0);
						break;
					
					case 'prev-page':
						current_page.set(current_page.value > 0 ? current_page.value - 1 : 0);
						break;
				}
			}
		}
		
		var optimized = stats.total_image_number - stats.uncompressed;
		
		if(optimized <= per_page_val) {
			current_page.set(0);
		}
		
		jQuery.ajax({
			type : 'GET',
			url : speedycache_ajax.url + '?action=speedycache_update_image_list_ajax_request',
			data : {
				'search' : search.val(),
				'per_page' : per_page_val,
				'filter' : filter.val(),
				'page' : current_page.value,
				'security' : speedycache_ajax.nonce
			},
			success: function(res) {
				if(!res.content) {
					return;
				}
				
				total_page.set(Math.ceil(res.result_count/per_page_val));
				
				if(total_page.value - 1 == current_page.value) {
					img_list.find('.speedycache-image-list-next-page').addClass('disabled');
					img_list.find('.speedycache-image-list-last-page').addClass('disabled');
				}
				
				jQuery('#speedycache-image-list tbody').empty();
				jQuery('#speedycache-image-list tbody').append(res.content);
				jQuery('.speedycache-revert').on('click', revert_image);
				jQuery('.speedycache-open-image-details').on('click', open_img_details);
			}
		});
	}
	
	var disabling_paging_btn = function(img_list) {
		if(current_page.value == 0 && total_page.value - 1 == 0) {
			img_list.find('.speedycache-image-list-first-page').addClass('disabled');
			img_list.find('.speedycache-image-list-prev-page').addClass('disabled');
			img_list.find('.speedycache-image-list-last-page').addClass('disabled');
			img_list.find('.speedycache-image-list-next-page').addClass('disabled');
		}else if(current_page.value == 0) {
			img_list.find('.speedycache-image-list-first-page').addClass('disabled');
			img_list.find('.speedycache-image-list-prev-page').addClass('disabled');
			img_list.find('.speedycache-image-list-last-page').removeClass('disabled');
			img_list.find('.speedycache-image-list-next-page').removeClass('disabled');
		} else if(current_page.value == total_page.value - 1) {
			img_list.find('.speedycache-image-list-first-page').removeClass('disabled');
			img_list.find('.speedycache-image-list-prev-page').removeClass('disabled');
			img_list.find('.speedycache-image-list-last-page').addClass('disabled');
			img_list.find('.speedycache-image-list-next-page').addClass('disabled');
		} else {
			img_list.find('.speedycache-image-list-first-page').removeClass('disabled');
			img_list.find('.speedycache-image-list-prev-page').removeClass('disabled');
			img_list.find('.speedycache-image-list-last-page').removeClass('disabled');
			img_list.find('.speedycache-image-list-next-page').removeClass('disabled');
		}
	}
	
	//Toggles the image variants
	var open_img_details = function() {
		var post_id = jQuery(this).closest('tr').attr('post-id');
		
		if(!post_id) {
			return;
		}
		
		var details = jQuery('tr[post-id="'+post_id+'"][post-type="detail"]');
		
		if(details.is(':hidden')) {
			details.show();
			jQuery(this).find("span").attr('class', 'dashicons dashicons-arrow-up-alt2')
		} else {
			details.hide();
			jQuery(this).find("span").attr('class', 'dashicons dashicons-arrow-down-alt2');
		}
	}
	
	//Downloading cwebp
	jQuery('button.speedycache-webp-download').on('click', function(e) {
		e.preventDefault();
		
		type = jQuery(this).data('type') ? jQuery(this).data('type') : 'cwebp';
		
		jQuery.ajax({
			url : speedycache_ajax.url + '?action=speedycache_download_cwebp',
			type : 'GET',
			data : {
				security : speedycache_ajax.nonce,
				type : type
			},
			beforeSend : function() {
				//speedycache_add_loader();
			},
			success : function(res) {
				//speedycache_hide_loader();
				
				if(res.success) {
					location.reload();
					return;
				}
				
				if(!res.error_message) {
					alert('Something went wrong try again later!');
				}
				
				alert(res.error_message);
			}
		})
	});
	
	//Listener For Scheduled Count
	jQuery('span.speedycache-scheduled-count').on('click', function() {
		speedycache_open_modal(jQuery(this));
	});
	
	//Listeners Starts here
	
	//Search button listener
	jQuery('#speedycache-image-search-button').on('click', function() {
		speedycache_update_list(jQuery(this));
	});
	
	//All or Error image filter
	jQuery('#speedycache-image-list-filter').on('change', function() {
		speedycache_update_list(jQuery(this));
	});
	
	//Per page listener
	jQuery('#speedycache-image-per-page').on('change', function() {
		speedycache_update_list(jQuery(this));
	});
	
	//Paging Number Listeners
	jQuery('.speedycache-image-list-first-page, .speedycache-image-list-prev-page, .speedycache-image-list-next-page, .speedycache-image-list-last-page').on('click', function() {
		speedycache_update_list(jQuery(this));
	});
	
	//Toggles the image variants Listener
	jQuery('.speedycache-open-image-details').on('click', open_img_details);
}

/*
	Converts the format of jQuery serializeArray
	i.e, [ 0:{name:someName, value:expectedvalue} ] to
	{ someName:expectedvalue }
*/
function speedycache_convert_serialized(arr) {
	var converted_obj = {};
	
	for(var i of arr) {
		converted_obj[i.name] = i.value;
	}
	
	return converted_obj;
}

function speedycache_critical_css(){
	jQuery.ajax({
		type: 'GET',
		url : speedycache_ajax.url + '?action=speedycache_critical_css&security='+speedycache_ajax.nonce,
		success: function(res){
			if(!res.success){
				alert(res.data.message ? res.data.message : 'Something went wrong ! Unable to intitiate Critical CSS!');
				return;
			}
			
			alert(res.data.message);
		}
	})
}

function speedycache_verify_license(){
	event.preventDefault();
	let jEle = jQuery(event.target),
	form = jEle.closest('form'),
	form_data = form.serializeArray(),
	spinner = jEle.find('.speedycache-spinner');
	
	spinner.addClass('speedycache-spinner-active');
	
	jQuery.ajax({
		type: 'GET',
		url : speedycache_ajax.url,
		data : form_data,
		success: function(res){
			if(res.success){
				alert('License verified, please reload the page');
				return;
			}
			
			if(res.data){
				alert(res.data);
				return;
			}
			
			alert('Something went wrong when trying to verify license');
		}
	}).always(function(){
		spinner.removeClass('speedycache-spinner-active');
	})
}
