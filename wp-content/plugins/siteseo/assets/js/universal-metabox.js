jQuery(document).ready(function(){
	let saved_left = localStorage.getItem('siteseo_universal_icon_left') || '10px';
	let saved_top = localStorage.getItem('siteseo_universal_icon_top') ? localStorage.getItem('siteseo_universal_icon_top') : 'auto';
	let saved_bottom = localStorage.getItem('siteseo_universal_icon_top') ? 'auto' : '10px';
	
	let universal_html = `<style>#siteseo-universal-metabox-icon{display:inline-flex; justify-content:center; align-items:center; position: fixed;left: ${saved_left}; top: ${saved_top}; bottom: ${saved_bottom}; z-index: 100000; background-color: #003399; padding: 6px; border-radius: 50%; cursor:pointer; width:40px; height:40px;} #siteseo-universal-metabox-icon img{width:36px; height:36px;}	.siteseo-universal-metabox{position:fixed;left:0;right:0;bottom:0;z-index:100100;background-color:#fff;width:100%;max-width:100%;min-width:100%;height:400px;box-sizing:border-box;flex-shrink:0;border:none;max-height:calc(100% - 93px);text-transform:none;display:none;box-sizing:border-box}.siteseo-drag-icon{position:absolute;top:-6px;right:-6px;width:25px;height:25px;background-color:#163d89;color:#fff;border-radius:50%;font-size:16px;display:none;justify-content:center;align-items:center;border:1px solid #ccc;box-shadow:0 1px 3px rgba(0,0,0,.1)}#siteseo-universal-metabox-icon:hover .siteseo-drag-icon{display:flex}.siteseo-universal-metabox-header{display:flex;justify-content:space-between;border-bottom:1px solid #ddd;border-top:1px solid #ddd;padding:10px 24px;}.siteseo-universal-metabox-header h2{margin:0;font-size:16px !important;}.siteseo-universal-metabox-body{height:calc(100% - 40px)}
    .siteseo-universal-spinner{display:none;position:absolute; top: 30%; left: 50%; transform: translateX(-50%); border-radius:50%;animation: siteseo-universal-spinner 1s linear infinite;height: 2rem;width: 2rem;border: 4px solid #dddcdc80;border-left-color: #e3e3e3;} @keyframes siteseo-universal-spinner{ 0% { transform: rotate(0deg);} 100% {transform: rotate(360deg);}}
    </style>
    <div class="siteseo-universal-modal"><div class="siteseo-universal-metabox"><div class="siteseo-universal-metabox-header"><h2>SiteSEO</h2><span class="dashicons dashicons-no-alt" onclick="siteseo_close_universal()" style="cursor:pointer;"></span></div><div class="siteseo-universal-spinner"></div><div class="siteseo-universal-metabox-body"><iframe id="siteseo-iframe-universal-metabox" onload="siteseo_onload_universal_iframe(event)" data-src="${siteseo_universal.metabox_url}&post=${siteseo_universal.post_id}" style="width:100%;height:100%;border:0;display:none"/></iframe></div></div><div id="siteseo-universal-metabox-icon"><span class="dashicons dashicons-fullscreen-alt siteseo-drag-icon"></span><img src="${siteseo_universal.asset_url}/img/logo-24.svg"></div></div>`;
    
	jQuery('body').append(universal_html);

	let is_dragging = false,
	dragging_start = false,
	offset = {},
	click_start_time = 0,
	click_start_x = 0,
	click_start_y = 0;
    
	jQuery('#siteseo-universal-metabox-icon').on('mousedown', function(e){
		if(jQuery(e.target).hasClass('siteseo-drag-icon') || jQuery(e.target).parent().hasClass('siteseo-drag-icon')){
			is_dragging = true;
			dragging_start = false;
			offset.x = e.clientX - jQuery('#siteseo-universal-metabox-icon')[0].getBoundingClientRect().left;
			offset.y = e.clientY - jQuery('#siteseo-universal-metabox-icon')[0].getBoundingClientRect().top;
			e.preventDefault();
		} else{
			click_start_time = Date.now();
			click_start_x = e.clientX;
			click_start_y = e.clientY;
		}
	});
    
	jQuery(document).on('mousemove', function(e){
		if(is_dragging){
			dragging_start = true;
			let new_left = e.clientX - offset.x;
			let new_top = e.clientY - offset.y;

			let icon_width = jQuery('#siteseo-universal-metabox-icon').outerWidth();
			let icon_height = jQuery('#siteseo-universal-metabox-icon').outerHeight();

			new_left = Math.max(0, Math.min(new_left, window.innerWidth - icon_width));
			new_top = Math.max(0, Math.min(new_top, window.innerHeight - icon_height));

			jQuery('#siteseo-universal-metabox-icon').css({
				left: new_left + 'px',
				top: new_top + 'px'
			});
		}
	});
    
	jQuery(document).on('mouseup', function(e){
		if(is_dragging){
			is_dragging = false;
			if(dragging_start){
				let icon = jQuery('#siteseo-universal-metabox-icon');
				localStorage.setItem('siteseo_universal_icon_left', icon.css('left'));
				localStorage.setItem('siteseo_universal_icon_top', icon.css('top'));
			}
		} else {
			let click_duration = Date.now() - click_start_time;
			let move_distance = Math.sqrt(Math.pow(e.clientX - click_start_x, 2) + Math.pow(e.clientY - click_start_y, 2));

			if(click_duration < 300 && move_distance < 5){
				siteseo_toggle_universal_modal();
			}
		}
	});
});

function siteseo_toggle_universal_modal(){
	let modal = jQuery('.siteseo-universal-metabox'),
	iframe = modal.find('iframe'),
	src_val = iframe.data('src'),
	spinner = modal.find('.siteseo-universal-spinner'),
	src = iframe.attr('src');
	modal.show();
  
	let icon = jQuery('#siteseo-universal-metabox-icon');
	let icon_pos = icon.position();
    
	modal.css({
		'left': '0',
		'bottom': '0',
		'top': 'auto'
	}).show();

	if(src){
		return;
	}

	spinner.show();
	iframe.attr('src', src_val);
}

function siteseo_onload_universal_iframe(e){
	jQuery('.siteseo-universal-spinner').hide();
	jQuery(e.target).show();
}

function siteseo_close_universal(){
	jQuery('.siteseo-universal-metabox').hide();
	
}