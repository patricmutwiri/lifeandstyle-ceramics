jQuery(document).ready(function($){
    const cookieBar = $('#siteseo-cookie-bar');
    const backdrop = $('#siteseo-cookie-bar-backdrop');
    const acceptBtn = $('#siteseo-cookie-bar-accept');
    const closeBtn = $('#siteseo-cookie-bar-close');
    const manageBtn = $('#siteseo-cookie-bar-manage-btn');
    const cookieName = 'siteseo-user-consent-accept';
    const cookieRejectName = 'siteseo-user-consent-close';
    const autoAcceptable = cookieBar.data('half-disable');
    const cookieDuration = 30 * 24 * 60 * 60; // 30 days in seconds

    function setCookie(name, value, duration){
        const date = new Date();
        date.setTime(date.getTime() + duration * 1000);
        document.cookie = `${name}=${value}; path=/; expires=${date.toUTCString()}`;
    }

    function getCookie(name){
        const cookies = document.cookie.split(';');
        for(let i = 0; i < cookies.length; i++){
            const cookie = cookies[i].trim();
            if(cookie.indexOf(name + '=') === 0){
                return cookie.substring(name.length + 1);
            }
        }
        return null;
    }

    //backdrop
    function hideCookieBar(){
        cookieBar.hide();
        backdrop.hide();
        manageBtn.show();
    }

    //show cookie bar 
    function showCookieBar(){
        cookieBar.show();
        if(cookieBar.hasClass('siteseo-cookie-bar-middle')){
            backdrop.show();
        }
    }
	
    function loadDeferredScripts(){
      if(getCookie('siteseo-user-consent-accept') === 'true'){
      
      	document.querySelectorAll('script[data-src-siteseo]').forEach(script => {
      		const newScript = document.createElement('script');
				
      		Array.from(script.attributes).forEach(attr => {
      			if(attr.name !== 'data-src-siteseo'){
      				newScript.setAttribute(attr.name, attr.value);
      			}
      		});
				
      		newScript.src = script.getAttribute('data-src-siteseo');
				
      		if(script.innerHTML){
      			newScript.innerHTML = script.innerHTML;
      		}
				
      		script.parentNode.replaceChild(newScript, script);
      	});
      }
	}
	
	if(autoAcceptable && !getCookie(cookieName) && !getCookie(cookieRejectName)){
		setTimeout(autoAcceptCookies,10000);		
	}
	
	function autoAcceptCookies(){
		setCookie(cookieName, 'true', cookieDuration);
		setCookie(cookieRejectName, '', -1);
		loadDeferredScripts(); 
		hideCookieBar();
	}
	
	function updateSrcTag(){
		if(getCookie('siteseo-user-consent-close') === 'true'){
			const analyticsScripts = [
        'googletagmanager.com',
				'google-analytics.com',
				'clarity.ms',
				'stats.g.doubleclick.net',
				'gtag/js'
			];
			
			document.querySelectorAll('script[src]').forEach(script => {
				if(script.hasAttribute('src')){
					const src = script.getAttribute('src');
					
					if(analyticsScripts.some(tracker => src.includes(tracker))){
						script.removeAttribute('src');
						script.setAttribute('data-src-siteseo', src);
					}
				}
			});
		}
	}
	
	function removeCookies(){
		var cookies = document.cookie.split(';');
		for(var i = 0; i < cookies.length; i++){
			var cookie = cookies[i];
			var eqPos = cookie.indexOf('=');
			var name = eqPos > -1 ? cookie.substr(0, eqPos).trim() : cookie.trim();
			
			if(name.startsWith('_ga') || name.startsWith('_gid') || name.startsWith('_gat') || name.startsWith('_gac') || name === 'AMP_TOKEN' || name.startsWith('_gcl_au') || // Google Analytics cookies
			
			// Google Tag Manager
			name.startsWith('_dc_gtm_') ||
			
			// google adds
			name.startsWith('_gads') || name.startsWith('_gac_') || name.startsWith('IDE') || name.startsWith('DSID') ||
			
			// Microsoft Clarity
			name.startsWith('_clsk') || name.startsWith('_clck') || name.startsWith('MR') || name.startsWith('SM') ||
			name.startsWith('MUID') || name.startsWith('ANONCHK') || name.startsWith('CLID') ||
			
			// Google Gtag
			name.startsWith('_gtag_') ||
			
			// Matomo cloud
			name.startsWith('_pk') || name.startsWith('mtm') || name.startsWith('matomo')
			
			){
					document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; domain=' + window.location.hostname;
					
			}
		}
	}
	
    if(!getCookie(cookieName) && !getCookie(cookieRejectName)){
        showCookieBar();
    }

    // Accept btn
    acceptBtn.on('click', function(e){
        e.preventDefault();
        setCookie(cookieName, 'true', cookieDuration);
        setCookie(cookieRejectName, '', -1);
        loadDeferredScripts();
        hideCookieBar();
    });

    // Close btn
    closeBtn.on('click', function(e){
        e.preventDefault();
        setCookie(cookieRejectName, 'true', cookieDuration);
        setCookie(cookieName, '', -1);
        updateSrcTag();
        removeCookies();
        hideCookieBar();
    });

    // Manage btn
    manageBtn.on('click', function (e){
        e.preventDefault();
        showCookieBar();
    });
});