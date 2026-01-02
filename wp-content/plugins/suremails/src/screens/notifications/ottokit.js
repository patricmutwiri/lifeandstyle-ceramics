import { useEffect, useState } from 'react';
import { Loader } from '@bsf/force-ui';

const Ottokit = () => {
	const [ isLoaded, setIsLoaded ] = useState( false );

	const initOttokit = () => {
		if ( window?.SureTriggers?.init ) {
			window.SureTriggers.init( {
				st_embed_url: 'https://app.ottokit.com/embed-login',
				client_id: 'SureMail',
				embedded_identifier: 'suremail-ottokit-itegration',
				target: 'ottokit-iframe-wrapper',
				integration: 'suremail',
				integration_display_name: 'SureMail',
				event: {},
				summary: 'Create new workflow',
				configure_trigger: true,
				show_recipes: false,
				style: {
					button: { background: '#0D7EE8' },
					icon: { color: '#111827' },
				},
			} );

			setTimeout( () => {
				setIsLoaded( true );
			}, 1200 ); // Give time for iframe to load
			return true;
		}
		return false;
	};

	useEffect( () => {
		let isMounted = true;
		let attempts = 0;
		const maxAttempts = 10;

		if ( initOttokit() ) {
			return;
		}

		const interval = setInterval( () => {
			if ( attempts >= maxAttempts ) {
				clearInterval( interval );
				return;
			}

			if ( initOttokit() && isMounted ) {
				clearInterval( interval );
			}

			attempts++;
		}, 300 );

		return () => {
			isMounted = false;
			clearInterval( interval );
		};
	}, [] );

	return (
		<div className="relative w-full h-[70vh] min-h-[300px]">
			{ ! isLoaded && (
				<div
					role="status"
					aria-busy="true"
					className="absolute inset-0 flex items-center justify-center z-10 bg-background-secondary bg-opacity-30"
				>
					<Loader className="text-background-primary" />
				</div>
			) }
			<div
				id="ottokit-iframe-wrapper"
				style={ {
					height: '70vh',
					width: '100%',
				} }
			/>
		</div>
	);
};

export default Ottokit;
