jQuery(document).ready(function($){
	var mediaUploader;
	
	init_uploaders();
	
	let debounce;
	function siteseo_debounce(func, timeout = 500){
		clearTimeout(debounce);
		debounce = setTimeout(() => {
			func.apply(this, arguments);
		}, timeout);
	}
	
	$(document).on('click', '.siteseo-x-toggle-switch input', function(){
		if($(this).is(':checked')){
			$('.siteseo-x-settings').hide();
			let fb_title = $('.siteseo-metabox-fb-title').text(),
			fb_img = $('.siteseo_social_fb_img_meta').val();
			
			$('.siteseo-metabox-x-title').text(fb_title);
			$('.siteseo-metabox-x-image img').attr('src', fb_img || siteseoAdminAjax.social_placeholder);
			$('.siteseo_social_twitter_title_meta').val('');
			$('.siteseo_social_twitter_desc_meta').val('');
			$('.siteseo_social_twitter_img_meta').val('');
		} else{
			
			$('.siteseo-metabox-x-title').text('');
			$('.siteseo_social_twitter_title_meta').val('');
			$('.siteseo_social_twitter_desc_meta').val('');
			$('.siteseo_social_twitter_img_meta').val('');
			$('.siteseo-metabox-x-image img').attr('src', siteseoAdminAjax.social_placeholder);
			$('.siteseo-x-settings').show();
			
		}
	});
	
	$(document).on('click', '.siteseo-metabox-tab-label', function(){
		let jEle = $(this),
		parent_tab = jEle.closest('.siteseo-metabox-tabs, .siteseo-metabox-subtabs'),
		active_tab = parent_tab.find('.siteseo-metabox-tab-label-active');

		if(active_tab.length){
			active_tab.removeClass('siteseo-metabox-tab-label-active');
		}

		jEle.addClass('siteseo-metabox-tab-label-active');
		let target = jEle.data('tab');

		parent_tab.siblings('.'+target).show();
		parent_tab.siblings('.'+target).siblings('.siteseo-metabox-tab').hide();
	});
	
	// Facebook title
	$(document).on('input paste', '.siteseo_social_fb_title_meta', function(){
		let jEle = $(this),
		fb_title = jEle.val();

		$('.siteseo_social_fb_title_meta').not(jEle)?.val(fb_title);
		
		// toogle x use
		if($('.siteseo-x-toggle-switch input').is(':checked')){
			$('.siteseo-metabox-x-title').text(fb_title);
		}
		
		if(fb_title.includes('%%')){
			siteseo_debounce(() => {
				fb_title = resolve_dynamic_variables(fb_title, 'title', 'fb');
			});
			
			return;
		}
		
		$('.siteseo-metabox-fb-title').text(fb_title);
	});
	
	// Facebook description updates
	$(document).on('input paste', '.siteseo_social_fb_desc_meta', function(){
		let jEle = $(this),
		fb_desc = jEle.val();

		$('.siteseo_social_fb_desc_meta').not(jEle)?.val(fb_desc);
		
		if(fb_desc.includes('%%')){
			siteseo_debounce(()	=> {
				fb_desc = resolve_dynamic_variables(fb_desc, 'desc', 'fb');
			});
			
			return;
		}
		
		$('.siteseo-metabox-fb-desc').text(fb_desc);
	});
	
	// X description title
	$(document).on('input paste', '.siteseo_social_twitter_title_meta', function(){
		let jEle = $(this),
		x_title = jEle.val();

		$('.siteseo_social_twitter_title_meta').not(jEle)?.val(x_title);
		
		if(x_title.includes('%%')){
			siteseo_debounce(() => {
				x_title = resolve_dynamic_variables(x_title, 'title' ,'x');
			});
			
			return;
		}
		
		$('.siteseo-metabox-x-title').text(x_title);
	});
		
	// X description updates
	$(document).on('input paste', '.siteseo_social_twitter_desc_meta', function(){
		let jEle = $(this),
		x_desc = jEle.val();
		
		$('.siteseo-metabox-x-desc').text(x_desc);		
		$('.siteseo_social_twitter_desc_meta').not(jEle)?.val(x_desc);
	});

	// We only need to do this sync if we are in gutenberg
	if(typeof siteseo_sidebar != 'undefined' && siteseo_sidebar){
		
		// Facebook image updates
		$(document).on('input paste', '.siteseo_social_fb_img_meta', function(){
			let jEle = $(this);
			$('.siteseo_social_fb_img_meta').not(jEle).val(jEle.val());
		});
		
		// X image updates
		$(document).on('input paste', '.siteseo_social_twitter_img_meta', function(){
			let jEle = $(this);
			$('.siteseo_social_twitter_img_meta').not(jEle)?.val(jEle.val());
		});
		
		// Input fields in Advanced tab
		$(document).on('input paste', '.siteseo-metabox-tab-advanced-settings input', function(){
			sync_inputs($(this), 'siteseo-metabox-tab-advanced-settings');
		})

		// Select field in advanced settings tab
		$(document).on('change', '.siteseo-metabox-tab-advanced-settings select', function(){
			sync_select($(this), 'siteseo-metabox-tab-advanced-settings');
		});
		
		// Input fields in Redirects tab
		$(document).on('input paste', '.siteseo-metabox-tab-redirect input', function(){
			sync_inputs($(this), 'siteseo-metabox-tab-redirect');
		});
		
		// Select field in advanced settings tab
		$(document).on('change', '.siteseo-metabox-tab-redirect select', function(){
			sync_select($(this), 'siteseo-metabox-tab-redirect');
		});
		
		// Input fields in structured data tab
		$(document).on('input paste', '.siteseo-metabox-tab-structured-data-types input', function(){
			sync_inputs($(this), 'siteseo-metabox-tab-structured-data-types');
		});
		
		// Select field in structured data tab
		$(document).on('change', '.siteseo-metabox-tab-structured-data-types select', function(){
			sync_select($(this), 'siteseo-metabox-tab-structured-data-types');
		});
		
		// textarea fields in structured data tab
		$(document).on('input paste', '.siteseo-metabox-tab-structured-data-types textarea', function(){
			let jEle = $(this),
			name = jEle.attr('name');
			$(`.siteseo-metabox-tab-structured-data-types textarea[name="${name}"]`).not(jEle)?.val(jEle.val());
		});
		
		// Input fields in Video sitemap tab
		$(document).on('input paste', '.siteseo-metabox-tab-video-sitemap input', function(){
			sync_inputs($(this), 'siteseo-metabox-tab-video-sitemap');
		});

		// Textarea fields in Video sitemap tab
		$(document).on('input paste', '.siteseo-metabox-tab-video-sitemap textarea', function(){
			let jEle = $(this),
			name = jEle.attr('name');
			$(`.siteseo-metabox-tab-video-sitemap textarea[name="${name}"]`).not(jEle)?.val(jEle.val());
		});
		
		// Input fields in Google news tab
		$(document).on('input paste', '.siteseo-metabox-tab-google-news input', function(){
			sync_inputs($(this), 'siteseo-metabox-tab-google-news');
		});
	}
	
	function sync_select(jEle, wrapperClass){
		let name = jEle.attr('name');
		$(`.${wrapperClass} select[name="${name}"]`).not(jEle)?.val(jEle.val());
	}

	function sync_inputs(jEle, wrapperClass){
		let name = jEle.attr('name'),
        type = jEle.attr('type');

        // Find all inputs with the same name, but not the one being edited
        let $targets = $(`.${wrapperClass} input[name="${name}"]`).not(jEle);
        
        // Handle checkboxes differently from text inputs
        if(type === 'checkbox'){
            let is_checked = jEle.prop('checked');
            $targets.prop('checked', is_checked);
        } else {
            let value = jEle.val();
            $targets.val(value);
        }
	}

	function init_media_uploader(buttonId, inputClass, previewClass, attachmentIdField, widthField, heightField){
        var mediaUploader;
        
		$(document).on('click', '#' + buttonId, function(e){
			e.preventDefault();

			mediaUploader = wp.media({
				title: 'Choose Image',
				button:{
					text: 'Use this image'
				},
				multiple: false,
				library: {
					type: 'image'
				}
			});

			mediaUploader.on('select', function(){
				var attachment = mediaUploader.state().get('selection').first().toJSON();

				var isValid = validateImageDimensions(
					attachment.width, 
					attachment.height, 
					buttonId.includes('facebook')
				);

				if(!isValid.valid){
					var errorSpan = $('.' + inputClass).siblings('span');
					if(errorSpan.length === 0){	
						$('.' + inputClass).after('<span class="error-message" style="color: red;"></span>');
						errorSpan = $('.' + inputClass).siblings('span');
					}
					errorSpan.text(isValid.message).show();
					return;
        }

				var isValidImageFormat = validateImageFormat(attachment.url, buttonId.includes('facebook'));
				if(!isValidImageFormat.valid){
					var errorSpan = $('.' + inputClass).siblings('span');
					if(errorSpan.length === 0){
						$('.' + inputClass).after('<span class="error-message" style="color: yellow;"></span>');
						errorSpan = $('.' + inputClass).siblings('span');
					}
					errorSpan.text(isValidImageFormat.message).show();
					return;
        }

				$('.' + inputClass).siblings('span').hide();
				$('.' + inputClass).val(attachment.url);
				$('#' + attachmentIdField).val(attachment.id);
				$('#' + widthField).val(attachment.width);
				$('#' + heightField).val(attachment.height);

				// Update preview
				$('.' + previewClass + ' img').attr('src', attachment.url);

				// If FB upload and toggle is checked, update X image too
				if($('.siteseo-x-toggle-switch input').is(':checked')){
					$('.siteseo-metabox-x-image img').attr('src', attachment.url);
				}
			});

			mediaUploader.open();
		});
    }
	
	function init_uploaders(){
		init_media_uploader(
			'siteseo_social_fb_img_upload',
			'siteseo_social_fb_img_meta',
			'siteseo-metabox-fb-image',
			'siteseo_social_fb_img_attachment_id',
			'siteseo_social_fb_img_width',
			'siteseo_social_fb_img_height'
		);

		init_media_uploader(
			'siteseo_social_twitter_img_upload',
			'siteseo_social_twitter_img_meta',
			'siteseo-metabox-x-image',
			'siteseo_social_twitter_img_attachment_id',
			'siteseo_social_twitter_img_width',
			'siteseo_social_twitter_img_height'
		);
	}

	function validateImageDimensions(width, height, isFacebook){
		if(isFacebook){
			if(width < 200 || height < 200){
				return {
					valid: false,
					message: 'Image must be at least 200x200 pixels for Facebook'
				};
			}

			if((width * height * 4) / (1024 * 1024) > 8){
				return {
					valid: false,
					message: 'Image size exceeds Facebook 8MB limit'
				};
			}

			return { valid: true };
		}

		if(width < 144 || height < 144){
			return {
				valid: false,
				message: 'Image must be at least 144x144 pixels for X'
			};
		}

		if((width * height * 4) / (1024 * 1024) > 5){
			return {
				valid: false,
				message: 'Image size exceeds X 5MB limit'
			};
		}
		return { valid: true };
    }

  function validateImageFormat(url, isFacebook){

		var allowedExtensions = (isFacebook) ? ['jpeg', 'jpg', 'png', 'gif'] : ['jpeg', 'jpg', 'png', 'gif', 'webp'];
		var imageExtension = url.split('.').pop().toLowerCase();

		if(!allowedExtensions.includes(imageExtension)){
      return {
				valid : false,
				message : 'Only ' + allowedExtensions.join(', ').toUpperCase() + ' images are allowed.'
			};
    }

    return {valid : true};
	}

	$(document).on('input paste', '#siteseo_social_fb_img_meta, #siteseo_social_twitter_img_meta', function(){
    var errorSpan = $(this).siblings('span');
    var imageUrl = $(this).val().trim();
    errorSpan.text('').hide();

    if(imageUrl.trim() !== ''){
      var isFacebook = $(this).attr('id') === 'siteseo_social_fb_img_meta';
      var isValidImageFormat = validateImageFormat(imageUrl, isFacebook);

      if(!isValidImageFormat.valid){
        errorSpan.text(isValidImageFormat.message).show();
      }
    }
	});

	$(document).on('widget-added widget-updated', init_uploaders);
	
	// facebook title 
	$(document).on('click', '.siteseo-facebook-title', function(){
		let tag = $(this).data('tag'),
		$wrapper = $(this).closest('.siteseo-metabox-input-wrap'),
		$input = $wrapper.find('#siteseo_social_fb_title_meta, textarea');
		
		let currentValue = $input.val();
		newValue = currentValue + " " + tag;

		$input.val(newValue);
    
		$input.trigger('input');
	});
	
	// facebook description
	$(document).on('click', '.siteseo-facebook-desc', function(){
		let tag = $(this).data('tag'),
		$wrapper = $(this).closest('.siteseo-metabox-input-wrap'),
		$input = $wrapper.find('#siteseo_social_fb_desc_meta, textarea');
		
		let currentValue = $input.val();
		newValue = currentValue + " " + tag;

		$input.val(newValue);
		
		$input.trigger('input');
	});
	
	// x title
	$(document).on('click', '.siteseo-x-title', function(){
		
		let tag = $(this).data('tag'),
		$wrapper = $(this).closest('.siteseo-metabox-input-wrap'),
		$input = $wrapper.find('#siteseo_social_twitter_title_meta, textarea');
		
		let currentValue = $input.val();
		newValue = currentValue + " " + tag;

		$input.val(newValue);
		
		$input.trigger('input');
		
	});
	
	// x desc
	$(document).on('click', '.siteseo-x-desc', function(){
		
		let tag = $(this).data('tag'),
		$wrapper = $(this).closest('.siteseo-metabox-input-wrap'),
		$input = $wrapper.find('#siteseo_social_twitter_desc_meta, textarea');
		
		let currentValue = $input.val();
		newValue = currentValue + " " + tag;

		$input.val(newValue);
		
		$input.trigger('input');
	});
	
	$(document).on('click', '.siteseo-metabox-tag', function(){
		let tag = $(this).data('tag'),
		$wrapper = $(this).closest('.siteseo-metabox-input-wrap'),
		$input = $wrapper.find('#siteseo_titles_title_meta, textarea'),

		currentValue = $input.val(),
		newValue = currentValue + " " + tag;

		$input.val(newValue);
		update_char_counter($input);

		$input.trigger('input');
	});

    $(document).on('input paste', '.siteseo_titles_title_meta, .siteseo_titles_desc_meta', function(e){
		update_char_counter($(e.target));
	});

	function update_char_counter($input){
		let max_chars = $input.hasClass('siteseo_titles_title_meta') ? 60 : 160;

		if(max_chars == 60){
			var jEle = $('.siteseo_titles_title_meta');
		} else {
			var jEle = $('.siteseo_titles_desc_meta');
		}

		let current_length = $input.val().length,
		percentage = Math.min((current_length/max_chars) * 100, 100),
		$wrapper = jEle.closest('.siteseo-metabox-input-wrap'),
		$meter = $wrapper.find('.siteseo-metabox-limits-meter span'),
		$counter = $wrapper.find('.siteseo-metabox-limits-numbers em');

		if(max_chars == 60){
			update_title_placeholder($input.val());
			if($input.hasClass('siteseo_titles_title_meta')){
				$('.siteseo_titles_title_meta').not($input)?.val($input.val()); // Syncing inputs
			}
		} else {
			update_desc_placeholder($input.val());
			if($input.hasClass('siteseo_titles_desc_meta')){
				$('.siteseo_titles_desc_meta').not($input)?.val($input.val()); // Syncing inputs
			}
		}

		$meter.css('width', percentage + '%');
		$counter.text(current_length);
	}

	function update_title_placeholder(title){
		if(title.length > 60){
			title = title.substring(0, 60) + '...';
		}

		if(title.includes('%%')){
			siteseo_debounce(() => resolve_dynamic_variables(title, 'title'));
			return;
		}

		$('.siteseo-metabox-search-preview h3').text(title);
	}

	function update_desc_placeholder(desc){
		if(desc.length > 160){
			desc = desc.substring(0, 160) + '...';
		}

		if(desc.includes('%%')){
			siteseo_debounce(() => resolve_dynamic_variables(desc, 'desc'));
			return;
		}

		$('.siteseo-search-preview-description').text(desc);
	}

	// seo analysis and readiblity toggle 
	function loadTabs(selector){
		$(selector).load(" #siteseo-analysis-tabs-1", "", initializeToggle);
	}

	loadTabs("#siteseo-analysis-tabs");
	loadTabs("#siteseo-metabox-wrapper #siteseo-analysis-tabs");

	function initializeToggle(){
		let preventClick = false;

		$(document).off('click', '.siteseo-analysis-block-title').on('click', '.siteseo-analysis-block-title', function (event){
			if(preventClick){
				event.stopImmediatePropagation();
				event.preventDefault();
				preventClick = false;
				return;
			}

			let $title = $(this),
			$content = $title.next(".siteseo-analysis-block-content");

			$title.toggleClass("open");
			let isExpanded = $title.attr('aria-expanded') === "true",
			isHidden = $content.attr('aria-hidden') === "true";

			$title.attr('aria-expanded', !isExpanded);
			$content.toggle();
			$content.attr('aria-hidden', !isHidden);
		});

		$(document).on('click', '#expand-all', function (event){
			event.preventDefault();
			$(".siteseo-analysis-block-content").show();
			$(".siteseo-analysis-block-title").attr('aria-expanded', true);
			$(".siteseo-analysis-block-content").attr('aria-hidden', false);
		});

		$(document).on('click', '#close-all', function (event){
			event.preventDefault();
			$(".siteseo-analysis-block-content").hide();
			$(".siteseo-analysis-block-title").attr('aria-expanded', false);
			$(".siteseo-analysis-block-content").attr('aria-hidden', true);
		});
	}

	/**suggestion btn **/
	$('.siteseo-suggetion').hide();

	$(document).on('click', '.siteseo-tag-select-btn', function(e){
		e.preventDefault();
		e.stopPropagation();

		var $suggestion = $(this).next('.siteseo-suggestions-wrapper').find('.siteseo-suggetion');
		if($suggestion.length){
			$('.siteseo-suggetion').not($suggestion).hide();
			$suggestion.toggle();
		}
	});

	$(document).on('click', '.siteseo-suggestions-container .section', function(e){
		e.preventDefault();
		e.stopPropagation();

		let tag = $(this).find('.tag').text(),
		$container = $(this).closest('.siteseo-metabox-input-wrap, .siteseo-sidebar-input-wrap'),
		$targetField;

		// Check for both metabox and sidebar fields
		if($container.find('#siteseo_titles_title_meta, .siteseo-sidebar-title').length){
			$targetField = $container.find('#siteseo_titles_title_meta, .siteseo-sidebar-title');
		} else if($container.find('#siteseo_titles_desc_meta, .siteseo-sidebar-desc').length){
			$targetField = $container.find('#siteseo_titles_desc_meta, .siteseo-sidebar-desc');
		} else if($container.find('#siteseo_social_fb_title_meta').length){ 
      $targetField = $container.find('#siteseo_social_fb_title_meta'); 
		} else if($container.find('#siteseo_social_fb_desc_meta').length){ 
			$targetField = $container.find('#siteseo_social_fb_desc_meta'); 
		} else if($container.find('#siteseo_social_twitter_title_meta').length){ 
			$targetField = $container.find('#siteseo_social_twitter_title_meta'); 
		} else if($container.find('#siteseo_social_twitter_desc_meta').length){ 
			$targetField = $container.find('#siteseo_social_twitter_desc_meta'); 
		} 
		
		if($targetField && $targetField.length){
			append_suggestion_tag($targetField, tag);
			$(this).closest('.siteseo-suggetion').hide();
		}
	});

	// Close when click outside
	$(document).on('click', function(e){
		if(!$(e.target).closest('.siteseo-metabox-input-wrap, .siteseo-sidebar-input-wrap').length){
			$('.siteseo-suggetion').hide();
		}
	});
	
	//search
	$(document).on('input', '.search-box', function(){
		var searchText = $(this).val().toLowerCase().trim();
		var $sections = $(this).closest('.siteseo-suggetion').find('.section');

		$sections.each(function(){
			var sectionText = $(this).text().toLowerCase();
			var tagText = $(this).find('.tag').text().toLowerCase();
			
			$(this).toggle(
				sectionText.indexOf(searchText) > -1 || 
				tagText.indexOf(searchText) > -1
			);
		});
	});

	function append_suggestion_tag($field, text){
		let field = $field[0],
		currentValue = field.value,
		newValue = currentValue + " " + text;

		field.value = newValue;
		field.focus();
		$field.trigger('input');
	}

	// Refresh SEO analysis
	$(document).on('click', '#siteseo_refresh_seo_analysis', function(e){
		e.preventDefault();

		var button = $(this);
		var post_id = button.attr('data_id');
		var post_type = button.attr('data_post_type');

		var target_keywords;
		if(button.closest('.widget-content').length){
			target_keywords = button.closest('.widget-content').find('.siteseo_analysis_target_kw').val();
		}else{
			target_keywords = $('#siteseo_tags_hidden').val();
		}

		button.prop('disabled', true);
		button.text('Analyzing...');

		$.ajax({
			url: siteseoAdminAjax.url,
			type: 'POST',
			data: {
				action: 'siteseo_refresh_analysis',
				nonce: siteseoAdminAjax.nonce,
				post_id: post_id,
				post_type: post_type,
				target_keywords: target_keywords
			},
			success: function(response){
				if(response.success){

					var container;
					if(button.closest('.widget-content').length){
						container = button.closest('.widget-content').find('.siteseo-widget-seo-analysis');
					}else{
						container = $('#siteseo-metabox-content-analysis .siteseo-metabox-seo-analysis-tab');
					}

					container.html(response.data.html);

					if(!button.closest('.widget-content').length){
						let activeTab = $('#siteseo-metabox-content-analysis .siteseo-metabox-tab-label-active').data('tab');
						$('#siteseo-metabox-content-analysis .' + activeTab).show();
					}
				}else{
					alert('Analysis failed: ' + (response.data.message || 'Unknown error'));
				}
			},
			error: function(xhr, status, error){
				alert('Error performing analysis. Please try again.');
			},
			complete: function(){
				button.prop('disabled', false);
				button.text('Refresh analysis');
			}
		});
	});

	function initializeTabs(){
		$('#siteseo-metabox-content-analysis .siteseo-metabox-tab-label').off('click');

		$(document).on('click','#siteseo-metabox-content-analysis .siteseo-metabox-tab-label',function(){
			var tabId = $(this).data('tab');
			var $tabsContainer = $(this).closest('#siteseo-metabox-content-analysis');

			$tabsContainer.find('.siteseo-metabox-tab-label').removeClass('siteseo-metabox-tab-label-active');
			$(this).addClass('siteseo-metabox-tab-label-active');

			$tabsContainer.find('.siteseo-metabox-tab').hide();
			$tabsContainer.find('.' + tabId).show();
		});
	}

	initializeTabs();

	// Toggle Mobile and Desktop view of Google SERP
	$(document).on('click', '#siteseo-metabox-search-mobile', function(){
		$(this).hide();
		$(this).prev().show();
		$('.siteseo-search-preview-desktop').css('max-width', '414px');
	});

	$(document).on('click', '#siteseo-metabox-search-pc', function(){
		$(this).hide();
		$(this).next().show();
		$('.siteseo-search-preview-desktop').css('max-width', '');
	});

	// Tags
	let $tagsValue = $('#siteseo_tags_hidden'),
	tags = [];

	if($tagsValue.val()){
		tags = $tagsValue.val().split(',');
	}

	function createTag(tag){
		if(!tag || tags.includes(tag)){
			return;
		}

		let $input = $('.siteseo_analysis_target_kw_meta'),
		$tag = $('<span>').addClass('siteseo-tag').text(tag),
		$removeBtn = $('<span>').addClass('siteseo-remove-tag').text('×');

		$tag.append($removeBtn);
		$tag.insertBefore($input);
		tags.push(tag);
		updateHiddenInput();
	}

	$(document).on('click', '.siteseo-remove-tag', function(e) {
		e.preventDefault();
		e.stopImmediatePropagation();

		const tag = $(this).parent(),
		tagText = tag.text().slice(0, -1);

		tags = tags.filter(item => item !== tagText);
		tag.remove();
		updateHiddenInput();

		return;
	});

	function updateHiddenInput(){
		$('input[name="siteseo_analysis_target_kw"').val(tags.join(','));
	}

	$(document).on('blur keypress', '.siteseo_analysis_target_kw_meta', function(e){
		if(e.type === 'blur' || (e.type === 'keypress' && e.key === 'Enter')){
			const text = $(this).val().trim();
			if(text){
				createTag(text);
				$(this).val('');
			}
			e.preventDefault();
		}
	});

	$(document).on('click', '#siteseo-sidebar-wrapper .siteseo-sidebar-tabs', function(){
		$(this).toggleClass('siteseo-sidebar-tabs-opened');
		$(this).next().slideToggle('fast');
	});

	function resolve_dynamic_variables(content, type, platform){

		let post_id = jQuery('.siteseo-metabox-tabs').attr('data_id');

		jQuery.ajax({
			url : siteseoAdminAjax.url,
			type : 'POST',
			data : {
				content : content,
				action : 'siteseo_resolve_variables',
				post_id : post_id,
				nonce: siteseoAdminAjax.nonce,
			}, success : function(res) {
				if(!res.success){
					return;
				}

				if(type == 'title'){
					if(platform == 'fb'){
						$('.siteseo-metabox-fb-title').text(res.data);
						if($('.siteseo-x-toggle-switch input').is(':checked')){
							$('.siteseo-metabox-x-title').text(res.data);
						}
					} else if(platform == 'x'){
						$('.siteseo-metabox-x-title').text(res.data);					
					} else{
						update_title_placeholder(res.data);
					}
					
					return;
				}

				if(type == 'desc'){
					if(platform == 'fb'){
						$('.siteseo-metabox-fb-desc').text(res.data);
					} else if(platform == 'x'){
						$('.siteseo-metabox-x-desc').text(res.data);
					} else{
						update_desc_placeholder(res.data);
					}
				}
			}
		});
	}
});
