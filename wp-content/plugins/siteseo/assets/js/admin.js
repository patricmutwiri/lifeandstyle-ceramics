jQuery(document).ready(function($){

	$('.tag-title-btn').on('click', function(e){
		e.preventDefault();

		let input_field = $(this).closest('.wrap-tags').prev('input[type="text"], textarea'),
		current_value = input_field.val(),
		tag = $(this).data('tag');
    
		current_value = current_value || '';
		
		// We need to add a space if there is some content
		if(current_value.length > 0){
			tag = ' ' + tag;
		}

		input_field.val(current_value + tag);
		input_field.focus();
	});

	$('.siteseo-container a').on('click', function(e){
        e.preventDefault(); 
        $('.siteseo-container a').removeClass('active');
        $(this).addClass('active');
    });

	$("[id^='siteseo-toggle-meta-']").on('click', function(){
		$(this).toggleClass('active');
		if($(this).hasClass('active')){
			$('#toggle_state_posts').text('Click to hide any SEO metaboxes / columns for this post type');
			$(this).closest('.siteseo-toggle-cnt').find('.siteseo-suboption-toggle').val("");
		} else{
			$('#toggle_state_posts').text(' Click to show any SEO metaboxes / columns for this post type');
			$(this).closest('.siteseo-toggle-cnt').find('.siteseo-suboption-toggle').val(true);
		}
	});

	$('#siteseo-dismiss-get-started').on('click', function(e){
		e.preventDefault();
		$(this).closest('.siteseo-dashbord-intro').slideUp();

		$.ajax({
			url : siteseoAdminAjax.url,
			type : 'POST',
			data : {
				action : 'siteseo_dismiss_intro',
				nonce : siteseoAdminAjax.nonce
			}
		})
	});

	// toggle handler function
	function handleToggle($toggle, toggleKey, action) {

		const $container = $toggle.closest('.siteseo-toggle-cnt');
		const $stateText = $container.find(`.toggle_state_${toggleKey}`);
		const $input = $(`#${toggleKey}`);

		$container.addClass('loading');
		$toggle.toggleClass('active');

		const newValue = $toggle.hasClass('active') ? '1' : '0';
		$input.val(newValue);
		$stateText.text($toggle.hasClass('active') ? 'Disable' : 'Enable');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: action,
				toggle_value: newValue,
				nonce: $toggle.data('nonce')
			},
			success: function(response) {
				if (response.success) {
					// Show the custom toast message
					showToast('Your settings have been saved.');
				} else {
					console.error('Failed to save toggle state');
					toggleRollback($toggle, $input, $stateText);
					showToast(response.data.message || 'Failed to save toggle state', 'error');
				}
			},
			error: function() {
				console.error('Ajax request failed');
				toggleRollback($toggle, $input, $stateText);
				showToast('Unable to save settings', 'error');
			},
			complete: function() {
				$container.removeClass('loading');
			}
		});
	}

	// Rollback function in case of AJAX error
	function toggleRollback($toggle, $input, $stateText) {
		$toggle.toggleClass('active');
		$input.val($toggle.hasClass('active') ? '1' : '0');
		$stateText.text($toggle.hasClass('active') ? 'Disable' : 'Enable');
	}

	$('.siteseo-toggle-Sw').on('click', function() {
		const $toggle = $(this);
		const toggleKey = $toggle.data('toggle-key');
		const action = $toggle.data('action');

		handleToggle($toggle, toggleKey, action);
	});

	// toast msg
	function showToast(message, type = 'success') {
		const toast = $('<div>')
			.addClass('siteseo-toast')
			.addClass(type) 
			.html(`<span class="dashicons dashicons-yes"></span> ${message}`);

		$('body').append(toast); 

		// 3 seconds
		toast.fadeIn(300).delay(3000).fadeOut(300, function () {
			toast.remove();
		});
	}

	// default off
	$('.siteseo-suggetion').hide();

    $('.tag-select-btn').click(function(e){
        e.preventDefault();
        e.stopPropagation();
		
        $('.siteseo-suggetion').not($(this).siblings('.siteseo-suggestions-wrapper').find('.siteseo-suggetion')).hide();
        
        $(this).siblings('.siteseo-suggestions-wrapper').find('.siteseo-suggetion').toggle();
    });

    $('.siteseo-suggestions-container .section').click(function(e){
        e.preventDefault();
        e.stopPropagation();
        
        let tag = $(this).find('.tag').text();
    		let $wrapTags = $(this).closest('.siteseo-suggetion').closest('.wrap-tags');

    		let targetField = $wrapTags.prev('input[type="text"], textarea');

    		// for global schema
    		if(targetField.length === 0){
    			targetField = $wrapTags.find('input[type="text"], textarea');
    		}
        
        insertAtCursor(targetField, tag);
        
        $(this).closest('.siteseo-suggetion').hide();
    });

    $(document).click(function(e){
        if(!$(e.target).closest('.wrap-tags').length){
            $('.siteseo-suggetion').hide();
        }
    });

    $('.siteseo-search-box').on('input', function(){
        var searchText = $(this).val().toLowerCase();
        $(this).closest('.siteseo-suggetion').find('.section').each(function() {
            var sectionText = $(this).text().toLowerCase();
            $(this).toggle(sectionText.indexOf(searchText) > -1);
        });
    });
	
	function insertAtCursor(field, text){
		if(!field || field.length === 0) return;

		field = field[0];
		let scroll_pos = field.scrollTop || 0;
		let current_value = field.value;

		let caretPos = field.selectionStart,
			before = current_value.substring(0, caretPos),
			after = current_value.substring(caretPos);

		let hash_index = before.lastIndexOf('#');

		if(hash_index !== -1){
			// If '#' exists before the caret, replace the last '#' with a space and insert the text
			before = before.substring(0, hash_index) + " ";
			let new_value = before + text + after;
			field.value = new_value;

			// Set caret after inserted text
			let new_position = before.length + text.length;
			field.setSelectionRange(new_position, new_position);
		} else{
			// If no '#', insert at the end, normal case
			field.value = current_value + text;
			let new_position = field.value.length;
			field.setSelectionRange(new_position, new_position);
		}

		field.scrollTop = scroll_pos;
		field.focus();
	}

    $('.tag-title-btn').click(function(e){
        e.preventDefault();
        e.stopPropagation();

        var tag = '';
        var btnId = $(this).attr('id');
        if(btnId === 'tag-select-btn'){
            tag = '%%sitetitle%%'; // replace
        }
        
        if(tag){
            var targetField = $(this).closest('.wrap-tags').prev('input[type="text"], textarea');
            insertAtCursor(targetField, tag);
        }
    });

	// facebook upload Image
	$('#facebook_upload_logo').click(function(e){
		var mediaUploader;
		e.preventDefault();

		if(mediaUploader){
			mediaUploader.open();
			return;
		}

		mediaUploader = wp.media.frames.file_frame = wp.media({
			title: 'Media',
			button:{
				text: 'Select'
			},
			multiple: false
		});

		mediaUploader.on('select', function(){
			var attachment = mediaUploader.state().get('selection').first().toJSON();
			$('#facebook_org_image_url').val(attachment.url);
		});

		mediaUploader.open();
	});
	
	//twitter cart image
	$('#twitter_logo').click(function(e){
		var mediaUploader;
		e.preventDefault();

		if(mediaUploader){
			mediaUploader.open();
			return;
		}

		mediaUploader = wp.media.frames.file_frame = wp.media({
			title: 'Media',
			button:{
				text: 'Select'
			},
			multiple: false
		});

		mediaUploader.on('select', function(){
			var attachment = mediaUploader.state().get('selection').first().toJSON();
			$('#twitter_logo_url').val(attachment.url);
		});

		mediaUploader.open();
	});
	
	//knowledgen org
	$('#knowledge_org_logo').on('click', function(e){
		var mediaUploader;
		e.preventDefault();

		if(mediaUploader){
			mediaUploader.open();
			return;
		}

		mediaUploader = wp.media.frames.file_frame = wp.media({
			title: 'Media',
			button:{
				text: 'Select'
			},
			multiple: false
		});

		mediaUploader.on('select', function(){
			var attachment = mediaUploader.state().get('selection').first().toJSON();
			$('#knowledge_org_logo_url').val(attachment.url);
		});

		mediaUploader.open();
	});
	
	// get active tab
	function getDefaultTab(){
		return $('.siteseo-tab.active').attr('id') || 'tab_siteseo_home';
	}

	function setActiveTab(tabId){
		// Hide all first
		$('.siteseo-tab').hide();

		// remove classes
		$('.siteseo-nav-tab').removeClass('siteseo-nav-tab-active');
		$('.siteseo-tab').removeClass('active');

		if($('.siteseo-nav-tab[data-tab="' + tabId + '"]').length){
			$('[data-tab="' + tabId + '"]').addClass('siteseo-nav-tab-active');
		} else{
			$('#' + tabId + '-tab').addClass('siteseo-nav-tab-active');
		}
		$('#' + tabId).addClass('active').show(); // show active tab

		// Hide save button
		let exclude_tab = ['tab_siteseopro_robots_txt', 'tab_auto_schema', 'tab_siteseopro_htaccess'],
		is_pro_exits = (typeof siteseo_pro !== 'undefined' && siteseo_pro.schema) ? true : false,
		$save_btn = $('.siteseo-submit-button');

		if((is_pro_exits && exclude_tab.includes(tabId))){
			$save_btn.hide();
		} else {
			$save_btn.show();
		}
		// save ative tab 
		localStorage.setItem('siteseo_active_tab', tabId);
	}

	//get from localstorage
	var savedTab = localStorage.getItem('siteseo_active_tab');
	var defaultTab = getDefaultTab();

	// Check if the saved tab exists otherwise use the default tab
	if(savedTab && $('#' + savedTab).length){
		setActiveTab(savedTab);
	} else{
		setActiveTab(defaultTab);
	}

	// Click handler for navigation tabs
	$('.siteseo-nav-tab').on('click', function(e){
		e.preventDefault();
		var tabId = $(this).data('tab') || $(this).attr('id').replace('-tab', '');
		setActiveTab(tabId);
	});

	$('#siteseo-generate-api-key-btn').on('click', function(){
        var button = $(this);
        var inputField = $('#bing-api-key');
        
        // Disable button while processing
        button.prop('disabled', true);
        
        $.ajax({
            url: siteseoAdminAjax.url,
            type: 'POST',
            data: {
                action: 'siteseo_generate_bing_api_key',
                nonce: siteseoAdminAjax.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Insert the generated key into the input field
                    inputField.val(response.data.api_key);
                    
                    // Optional: Add a subtle highlight effect
                    inputField.css('background-color', '#f0f9ff')
                           .animate({'background-color': '#ffffff'}, 1500);
                } else {
                    alert('Error generating API key. Please try again.');
                }
            },
            error: function() {
                alert('Error generating API key. Please try again.');
            },
            complete: function() {
                // Re-enable the button
                button.prop('disabled', false);
            }
        });
    });
	
	$('#siteseo-submit-urls-button').on('click', function(e){
		e.preventDefault();
		var $button = $(this);
		var $spinner = $('.spinner');
		var $responseDiv = $('#url-submitter-response');
		
		$button.prop('disabled', true);
		$spinner.addClass('is-active');
		$responseDiv.empty();

		//bing responce
		function getBingResponseMessage(code){
			switch(code){
				case 200:
					return 'URLs submitted successfully';
				case 202:
					return 'URL received. IndexNow key validation pending.';
				case 400:
					return 'Bad request: Invalid format';
				case 403:
					return 'Forbidden: Key not valid';
				case 422:
					return 'Unprocessable Entity: URLs don\'t belong to the host';
				case 429:
					return 'Too Many Requests: Potential Spam';
				default:
					return 'Something went wrong';
			}
		}

		$.ajax({
			url: siteseoAdminAjax.url,
			method: 'POST',		
			data:{
				action: 'siteseo_url_submitter_submit',
				nonce: siteseoAdminAjax.nonce,
				search_engine: $('input[name="siteseo_options[search_engine_google]"]:checked').val() || $('input[name="siteseo_options[search_engine_bing]"]:checked').val(),
				urls: $('textarea[name="siteseo_options[instant_indexing_batch]"]').val()
			},

			success: function(response){
				if(response.success){
					
					let failed_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="20px" height="20px" fill="#EA3323"><path d="M340.67-284 480-423.33 619.33-284 676-340.67 536.67-480 676-619.33 619.33-676 480-536.67 340.67-676 284-619.33 423.33-480 284-340.67 340.67-284ZM479.79-50.67q-88.43 0-167.26-33.27-78.82-33.27-137.07-91.52-58.25-58.25-91.52-137.07-33.27-78.82-33.27-167.38 0-89.24 33.33-167.66Q117.33-726 175.86-784.5q58.53-58.49 136.96-91.99Q391.26-910 479.56-910q89.33 0 168.08 33.44 78.75 33.43 137.03 91.82 58.27 58.39 91.8 137.01Q910-569.12 910-479.61q0 88.79-33.51 167-33.5 78.21-91.99 136.75Q726-117.33 647.57-84T479.79-50.67Zm-.02-106q134.74 0 229.15-94.09 94.41-94.1 94.41-229.01 0-134.74-94.18-229.15T479.9-803.33q-134.41 0-228.82 94.18T156.67-479.9q0 134.41 94.09 228.82 94.1 94.41 229.01 94.41ZM480-480Z"/></svg>',
					success_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="20px" height="20px" fill="#48752C"><path d="M480.33-50.67q-89.64 0-169.13-32.93-79.49-32.94-137.08-90.52-57.58-57.59-90.52-137.08-32.93-79.49-32.93-169.13 0-89.9 32.84-168.49 32.83-78.6 90.84-136.61 58-58.02 137.23-91.29Q390.81-910 480-910q71.29 0 134.65 20.31 63.37 20.31 115.68 58.69l-78 78.33q-36.41-22.64-79.83-36.65-43.42-14.01-92.5-14.01-136.85 0-230.09 92.64-93.24 92.65-93.24 230.34 0 137.68 93.13 230.68 93.14 93 229.84 93 136.69 0 230.19-92.75 93.5-92.74 93.5-230.58 0-20.98-3-40.3-3-19.32-8-38.37l86.34-86q15 38.55 23.16 79.21 8.17 40.65 8.17 84.53 0 90.6-33.28 169.59-33.27 78.99-91.29 136.99-58.01 58.01-136.61 90.84-78.59 32.84-168.49 32.84Zm-60.66-228.66-180.34-181 72-73L419.67-425 837-843.33l73.67 73-491 491Z"/></svg>';

					var responseHtml = '';
			
					//Bing 
					if(response.data.details.bing){
						responseHtml += `
						<div class="wrap-bing-response">
							<table class="form-table">
								<tr>
									<td><h4>Bing Response</h4>`;
						
						if(response.data.details.bing.status_code == 200 || response.data.details.bing.status_code == 202){
							responseHtml += success_svg;	
						} else{
							responseHtml +=  failed_svg;
						}
			
						responseHtml += `</td>
									<td><code>${getBingResponseMessage(response.data.details.bing.status_code)}</code></td>
								</tr>
							</table>
						</div>`;
					}
			
					// Google Response
					if(response.data.details.google){
						responseHtml += `
						<div class="wrap-google-response">
							<table class="form-table">`;
							responseHtml += `
							<tr>
								<td><h4>Google Response</h4>`;
								
								if(response.data.details.google.status_code == 200){
									responseHtml += success_svg;	
								} else{
									responseHtml +=  failed_svg;
								}
									
								responseHtml += `</td>
								<td>
									<strong>Status: ${response.data.details.google.status_code}</strong>
									<p>URL:</p>
									<p>
										${response.data.details.google.urls.map(function(url){
											return `<code style="display:inline-block;">${url}</code>`;
										})}
									
									</p>
								</td>
							</tr>`;
			
						responseHtml += `</table>
						</div>`;
					}
			
					$responseDiv.html(responseHtml);
				} else{
					var errorMessage = response.data && response.data.message ? response.data.message : 'An error occurred.';
					$responseDiv.html('<div class="notice notice-error"><p>' + errorMessage + '</p></div>');
				}
			},
			error: function (xhr){
				console.error(xhr.responseText);
				$responseDiv.html('<div class="notice notice-error"><p>Request failed: ' + xhr.statusText + '</p></div>');
			},
			complete: function(){
				$button.prop('disabled', false);
				$spinner.removeClass('is-active');
			}
		});
	});
	
	/** scroll screen**/
	$('.siteseo-container a').on('click', function(e){
		e.preventDefault();
		
		var $container = $(this).closest('.siteseo-container');
		var $table = $(this).closest('table');
		
		$('.siteseo-container a').removeClass('active');
		
		var targetId = $(this).attr('href');
		targetId = targetId.replace(/^[#-]/, '');
		
		var $targetSection = $table.find('#' + targetId);
		if($targetSection.length){
			$('html, body').animate({
				scrollTop: $targetSection.offset().top - 100
			}, 500);
			
			$(this).addClass('active');
		}
    });

    function isElementInViewport(el){
		var rect = el[0].getBoundingClientRect();
		var windowHeight = $(window).height();

		var elementMiddle = rect.top + (rect.height / 2);
		return elementMiddle >= 0 && elementMiddle <= windowHeight;
    }

    function updateActiveSection(){
		$('table').each(function(){
			var $table = $(this);
			var activeFound = false;
			
			$table.find('.siteseo-container a').each(function(){
				var targetId = $(this).attr('href').replace(/^[#-]/, '');
				var $targetSection = $table.find('#' + targetId);
				
				if($targetSection.length && isElementInViewport($targetSection)){
					$('.siteseo-container a').removeClass('active');
					$(this).addClass('active');
					activeFound = true;
					return false;
				}
			});
		});
    }

    var scrollTimeout;
	$(window).on('scroll', function(){
		clearTimeout(scrollTimeout);
		scrollTimeout = setTimeout(function(){
			updateActiveSection();
		}, 100);
    });
	
	updateActiveSection();
	

	/*** reset setting***/
	$('.siteseo-container a').on('click', function(e){
		e.preventDefault();
		
		var $container = $(this).closest('.siteseo-container');
		var $table = $(this).closest('table');
		
		$('.siteseo-container a').removeClass('active');
		$(this).addClass('active');
		
		var targetId = $(this).attr('href').replace(/^[#-]/, '');
		var $targetSection = $table.find('#' + targetId);
		
		if($targetSection.length) {
			$('html, body').animate({
				scrollTop: $targetSection.offset().top - 100
			}, 500);
		}
	});

	function updateActiveSection(){
		$('table').each(function(){
			var $table = $(this);
			var windowTop = $(window).scrollTop();
			var windowBottom = windowTop + $(window).height();
			var windowCenter = windowTop + ($(window).height() / 2);

			var $sections = $table.find('[id]');
			var currentSection = null;
			
			$sections.each(function(){
				var $section = $(this);
				var sectionTop = $section.offset().top;
				var sectionBottom = sectionTop + $section.outerHeight();
				
				if(sectionTop <= windowCenter && sectionBottom >= windowCenter){
					currentSection = $section;
					return false;
				}
			});
			
			if(currentSection){
				var sectionId = currentSection.attr('id');
				var $links = $table.find('.siteseo-container a');
				
				$links.removeClass('active');
				$links.each(function(){
					var href = $(this).attr('href').replace(/^[#-]/, '');
					if(href === sectionId){
						$(this).addClass('active');
					}
				});
			}
		});
	}

	var scrollTimeout;
	$(window).on('scroll', function(){
		if(scrollTimeout){
			clearTimeout(scrollTimeout);
		}
		scrollTimeout = setTimeout(function(){
			updateActiveSection();
		}, 100);
	});

	updateActiveSection();
	$('#siteseo-reset-settings').on('click', function(e){
		e.preventDefault();
		
		if(confirm('Are you sure you want to reset all settings?')){
			$.ajax({
				url: siteseoAdminAjax.url,
				type: 'POST',
				data:{
					action: 'siteseo_reset_settings',
					nonce: siteseoAdminAjax.nonce
				},
				success: function(response){
					if(response.success){
						alert('Settings reset successfully.');
					} else{
						alert('Failed to reset settings: ' + response.data.message);
					}
				},
				error: function(xhr, status, error){
					alert('An error occurred: ' + error);
				}
			});
		} else{
			return false;
		}
	});

	
	/*** export settings***/
	$('#siteseo-export-btn').on('click', function(e){
		e.preventDefault();
		
		$.ajax({
			url: siteseoAdminAjax.url,
			type: 'POST',
			data:{
				action: 'siteseo_export_settings',
				nonce: siteseoAdminAjax.nonce
			},
			success: function(response){
				
				const blob = new Blob([JSON.stringify(response)], {type: 'application/json'});
				const url = window.URL.createObjectURL(blob);
				
				const a = document.createElement('a');
				a.style.display = 'none';
				a.href = url;
				a.download = 'siteseo-settings-export-' + new Date().toLocaleDateString('en-US').replace(/\//g, '-') + '.json';
				
				document.body.appendChild(a);
				a.click();
				
				window.URL.revokeObjectURL(url);
				document.body.removeChild(a);
			},
			error: function(xhr, status, error){
				console.error('Export failed:', error);
				alert('Export failed. Please try again.');
			}
		});
	});
	
	/** import settings**/
	$('#siteseo-import-btn').on('click', function (e){
		e.preventDefault();

		const fileInput = $('#siteseo-import-file')[0];

		if(!fileInput.files || !fileInput.files[0]){
			alert('Please select a file to import.');
			return;
		}

		const formData = new FormData();
		formData.append('action', 'siteseo_import_settings');
		formData.append('nonce', siteseoAdminAjax.nonce);
		formData.append('import_file', fileInput.files[0]);

		$('#siteseo-import-btn').prop('disabled', true);

		$.ajax({
			url: siteseoAdminAjax.url,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function (response){
				if(response.success){
					alert('Success: ' + response.data.message);
					fileInput.value = '';

					setTimeout(function(){
						location.reload();
					}, 1500);
				} else{
					alert('Error: ' + response.data.message);
				}
			},
			error: function(xhr, status, error){
				alert('Error: Import failed. Please try again.');
			},
			complete: function(){
				$('#siteseo-import-btn').prop('disabled', false);
			}
		});
	});

	//migration
	$(".siteseo-section-tool").hide();
    
    $("#siteseo-plugin-selector").on('change', function(){
        var selectedTool = $(this).val();
        $(".siteseo-section-tool").hide();
        if(selectedTool !== "none"){
            $("#" + selectedTool).show();
        }
    });

	// ajax migrate from others
	 $('button[id^="siteseo-"][id$="-migrate"]').on('click', function(){
        const button = $(this);
        const plugin = button.attr('id').replace('siteseo-', '').replace('-migrate', '');
        const spinner = button.next('.spinner');
        const log = button.siblings('.log');

        button.prop('disabled', true);
        spinner.addClass('is-active');
        log.empty();

        $.ajax({
            url: siteseoAdminAjax.url,
            type: 'POST',
            data: {
                action: 'siteseo_migrate_seo',
                plugin: plugin,
                nonce: siteseoAdminAjax.nonce
            },
            success: function(response){
                if(response.success){
                    log.html('<div class="notice notice-success">' + response.data.message + '</div>');
                } else{
                    log.html('<div class="notice notice-error">' + response.data.message + '</div>');
                }
            },
            error: function(){
                log.html('<div class="notice notice-error">Migration failed. Please try again.</div>');
            },
            complete: function(){
                button.prop('disabled', false);
                spinner.removeClass('is-active');
            }
        });
    });
    
	// Clean indexing history
	$('#siteseo-clear-history').on('click', function(e){
		e.preventDefault();
		$.ajax({
			url: siteseoAdminAjax.url,
			type: 'POST',
			data: {
				action: 'siteseo_clear_indexing_history',
				nonce: siteseoAdminAjax.nonce
			},
			success: function(response){
				location.reload();
			}
		});
	});
  
	// Response code table guilde
	$('.siteseo-show-details').next('.siteseo-response-code-table').hide();

	$('.siteseo-show-details').on('click', function(e){
		let description = $(this).next('.siteseo-response-code-table'),
		icon = $(this).find('.dash-icon'); 

		if(description.is(':visible')){
			description.hide();
			icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
		} else{
			description.show();
			icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
		}
	});
  
});