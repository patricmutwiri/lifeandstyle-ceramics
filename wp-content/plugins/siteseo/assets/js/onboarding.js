jQuery(document).ready(function($){
	
	if(window.location.href){
		const urlObj = new URL(window.location.href);
		const params = new URLSearchParams(urlObj.search);
		let step = params.get('step');
		
		if(step){
			let step_milestone = $('[data-step-slug="'+step+'"]');
			step_milestone = step_milestone.closest('.step');

			step_milestone.addClass('active');

			let prev_all_milestones = step_milestone.prevAll();
			if(prev_all_milestones){
				prev_all_milestones.removeClass('active').addClass('done');
			}
		}
	}

	// Listen for URL changes due to history navigation (back/forward)
	window.addEventListener('popstate', function() {
		$('.siteseo-step-page').removeClass('siteseo-step-active');
		
		const urlObj = new URL(window.location.href);
		const params = new URLSearchParams(urlObj.search);
		let step = params.get('step');
		
		if(step){
			$(`[data-step="${step}"]`).addClass('siteseo-step-active');
			let step_milestone = $('[data-step-slug="'+step+'"]');
			step_milestone = step_milestone.closest('.step');
			step_milestone.addClass('active').removeClass('done');

			let prev_all_milestones = step_milestone.prevAll(),
			next_milestone = step_milestone.next();
			
			if(prev_all_milestones.length){
				prev_all_milestones.removeClass('active').addClass('done');
			}

			if(next_milestone.length){
				next_milestone.removeClass('active');
			}

			return;
		}
		
		$('[data-step="welcome"]').addClass('siteseo-step-active');

	});

	$('#siteseo-upload-org-img, #siteseo-onboarding-img-holder').click(function(e){
		e.preventDefault();

		let mediaUploader;
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
			let attachment = mediaUploader.state().get('selection').first().toJSON();
			$('[name="organization_logo"]').val(attachment.url);
			
			let img_holder = $('#siteseo-onboarding-img-holder'),
			img_tag = img_holder.find('img');			
			img_holder.find('svg').hide();

			if(img_tag.length){
				img_tag.attr('src', attachment.url);
				return;
			}
			
			let new_img = document.createElement('img');
			new_img.src = attachment.url;
			img_holder.append(new_img);
		});

		mediaUploader.open();
	})
	
	// Import button to import SEO settings from other plugins
	$('#siteseo-do-import').click(function(e){
		e.preventDefault();
		
		let form = $(e.target).closest('form'),
		form_data = form.serializeArray(),
		plugin_name = form_data[0]['value'],
		result_p = $('.siteseo-onboarding-msg');

		plugin_name = plugin_name.split('/')[0]
		
		$.ajax({
			method : "POST",
			url : siteseo_onboarding.ajax_url,
			data : {
				nonce : siteseo_onboarding.nonce,
				plugin : plugin_name,
				action : 'siteseo_migrate_seo'
			},
			success:function(res){
				if(res.success){
					result_p.text(res.data.message);
					result_p.addClass('siteseo-onboarding-msg-success');
					result_p.show();
					return;
				}
				
				result_p.addClass('siteseo-onboarding-msg-error');
				if(res.data.message){
					result_p.text(res.data.message);
					
				} else{
					result_p.text('Something went wrong while importing!');
				}
			}
		})
	});

	$('input[name="plugin_name"]').change(function () {
		$('.siteseo-onboarding-import-info').css('display', 'flex');
	});
	
	$('input[name="site_status"]').change(function (e) {
		if(e.target.value == 'live'){
			$('.siteseo-live-site-options').show();
			return;
		}
		
		$('.siteseo-live-site-options').hide();
	
	});
	
	$('#siteseo-onboarding-begin, .siteseo-save-n-continue, .siteseo-skip-step').click(function(e){
		e.preventDefault();
		
		if(e.target.classList.contains('siteseo-save-n-continue')){
			save_step_settings(e);
			return;
		}
		
		jump_to_next_step(e);
	});
	
	let jump_to_next_step = (e) => {
		let step_root = $(e.target).closest('.siteseo-step-page');
		step_root.removeClass('siteseo-step-active');
		let nextele = step_root.next()
		nextele.addClass('siteseo-step-active')
		step_name = nextele.data('step');
		
		let step_milestone = $('[data-step-slug="'+step_name+'"]');
		step_milestone = step_milestone.closest('.step');
		step_milestone.addClass('active');
		
		// Updating the free milestones
		let prev_milestone = step_milestone.prev()	
		if(prev_milestone.length){
			prev_milestone.addClass('done');
			prev_milestone.removeClass('active');
		}

		window.history.pushState('', '', '?page=siteseo-onboarding&step='+step_name);
	}
	
	let save_step_settings = (ele) => {
		let btn = $(ele.target),
		form = btn.closest('form'),
		spinner = btn.find('.siteseo-spinner');

		if(!form.length){
			return;
		}

		spinner.addClass('siteseo-spinner-active');
		btn.prop('disabled', true);
		

		form_data = form.serializeArray();
		step_name = form.closest('.siteseo-step-page').data('step');
		
		let data = {};
		form_data.forEach(function(item){
			if(data[item['name']]){
				if(!Array.isArray(data[item['name']])){
					data[item['name']] = [data[item['name']]]; // Convert to an array if not already
				}
				data[item['name']].push(item['value']);
			} else {
				data[item['name']] = item['value'];
			}
		});

		$.ajax({
			method : 'POST',
			url : siteseo_onboarding.ajax_url,
			data : {
				nonce : siteseo_onboarding.nonce,
				data : data,
				step : step_name,
				action : 'siteseo_save_onboarding_settings'
			},
			success: function(res){
				if(res.success){
					jump_to_next_step(ele);
					return;
				}
				
				if(res.data){
					alert(res.data.message);
				}
			}
		}).always(function(){
			spinner.removeClass('siteseo-spinner-active');
			btn.prop('disabled', false);
		})
	}
	
});