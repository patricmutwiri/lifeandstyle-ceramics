import React, { useEffect } from 'react';
import {
	ArrowRightIcon,
	ArrowRightStartOnRectangleIcon,
	RectangleStackIcon,
} from '@heroicons/react/24/outline';
import { __ } from '@wordpress/i18n';
import { removeQueryArgs } from '@wordpress/url';
import { Button, DefaultStep, PreviousStepLink } from '../../components/index';
import { useStateValue } from '../../store/store';
import LimitExceedModal from '../../components/limit-exceeded-modal';
import { WandIcon } from '../ui/icons';
import './style.scss';
import {
	classNames,
	getStepIndex,
	removeLocalStorageItem,
	saveGutenbergAsDefaultBuilder,
	trackOnboardingStep,
} from '../../utils/functions';
const { showClassicTemplates, showAiBuilder, customTemplateData } =
	astraSitesVars;
const { isBeaverBuilderDisabled, isElementorDisabled } = starterTemplates;

const SiteType = () => {
	const [
		{ builder, currentIndex, limitExceedModal, pageBuilderCache },
		dispatch,
	] = useStateValue();

	const zipPlans = astraSitesVars?.zip_plans;
	const sitesRemaining = zipPlans?.plan_data?.remaining;
	const aiSitesRemainingCount = sitesRemaining?.ai_sites_count;

	useEffect( () => {
		const startTime = localStorage.getItem( 'st-import-start' );
		const endTime = localStorage.getItem( 'st-import-end' );

		if ( startTime || endTime ) {
			localStorage.removeItem( 'st-import-start' );
			localStorage.removeItem( 'st-import-end' );
		}

		// Track welcome step when component mounts
		trackOnboardingStep( 'welcome' );
	}, [] );

	useEffect( () => {
		async function setBuilder() {
			if ( showAiBuilder || builder !== 'ai-builder' ) {
				return;
			}

			// Check cache - if same builder was set recently (within 5 minutes), skip API call.
			const now = Date.now();
			const cacheExpiry = 5 * 60 * 1000; // 5 minutes.

			if (
				pageBuilderCache.timestamp &&
				now - pageBuilderCache.timestamp > cacheExpiry
			) {
				await saveGutenbergAsDefaultBuilder( 'gutenberg' );
			}

			dispatch( {
				type: 'set',
				builder: 'gutenberg',
				pageBuilderCache: {
					timestamp: now,
				},
			} );
		}

		setBuilder();
	}, [ showAiBuilder, isBeaverBuilderDisabled, isElementorDisabled ] );

	const handleKeyPress = ( e, navigate ) => {
		e = e || window.event;

		if ( e.keyCode === 37 ) {
			//Left Arrow
			if ( e.target.previousSibling ) {
				e.target.previousSibling.focus();
			}
		} else if ( e.keyCode === 39 ) {
			//Right Arrow
			if ( e.target.nextSibling ) {
				e.target.nextSibling.focus();
			}
		} else if ( e.key === 'Enter' ) {
			//Enter
			navigate();
		}
	};

	useEffect( () => {
		const urlParams = new URLSearchParams( window.location.search );

		const token = urlParams.get( 'token' );
		if ( token ) {
			if (
				typeof aiSitesRemainingCount === 'number' &&
				aiSitesRemainingCount <= 0
			) {
				dispatch( {
					type: 'set',
					limitExceedModal: {
						...limitExceedModal,
						open: true,
					},
				} );
			} else {
				dispatch( {
					type: 'set',
					currentIndex: 1,
				} );
			}
		}
	}, [] );

	const handleBuildWithAIPress = () => {
		if (
			typeof aiSitesRemainingCount === 'number' &&
			aiSitesRemainingCount <= 0
		) {
			dispatch( {
				type: 'set',
				limitExceedModal: {
					...limitExceedModal,
					open: true,
				},
			} );
			return;
		}
		const content = new FormData();
		content.append( 'action', 'astra-sites-change-page-builder' );
		content.append( '_ajax_nonce', astraSitesVars?._ajax_nonce );
		content.append( 'page_builder', 'ai-builder' );
		fetch( ajaxurl, {
			method: 'post',
			body: content,
		} );

		window.location.href =
			astraSitesVars?.adminURL + 'themes.php?page=ai-builder';
	};

	useEffect( () => {
		if (
			currentIndex === getStepIndex( 'page-builder' ) &&
			builder !== 'fse' &&
			showAiBuilder
		) {
			dispatch( {
				type: 'set',
				builder: 'ai-builder',
			} );
		}
	}, [] );

	const builders = [
		{
			id: 'custom-templates',
			show:
				customTemplateData &&
				Object.keys( customTemplateData ).length > 0,
			icon: customTemplateData?.icon ? (
				<span
					className="w-12 h-12 text-accent-st-secondary stroke-1"
					dangerouslySetInnerHTML={ {
						__html: customTemplateData?.icon,
					} }
				/>
			) : (
				<WandIcon className="w-12 h-12 text-accent-st-secondary stroke-1" />
			),
			onKeyDown: ( event ) =>
				handleKeyPress( event, () => {
					dispatch( {
						type: 'set',
						builder: 'custom-templates',
						currentIndex: 2,
					} );
				} ),
			heading: {
				text:
					customTemplateData?.heading ||
					__( 'Custom Templates', 'astra-sites' ),
			},
			description: {
				text:
					customTemplateData?.description ||
					__(
						'Experience the future of website building. We offer AI features powered by ZipWP to help you build your website 10x faster.',
						'astra-sites'
					),
			},
			button: {
				text:
					customTemplateData?.button_text ||
					__( 'Go to Templates', 'astra-sites' ),
				type: 'secondary',
				onClick: () => {
					dispatch( {
						type: 'set',
						builder: 'custom-templates',
						currentIndex: 2,
					} );
					removeLocalStorageItem( 'st-scroll-position' );
				},
			},
		},
		{
			id: 'ai-builder',
			show: showAiBuilder,
			icon: (
				<WandIcon className="w-12 h-12 text-accent-st-secondary stroke-1" />
			),
			className:
				'shadow-card gradient-border-cover gradient-border-cover-button',
			onKeyDown: ( event ) =>
				handleKeyPress( event, handleBuildWithAIPress ),
			heading: {
				text: __( 'AI Website Builder', 'astra-sites' ),
			},
			description: {
				text: __(
					'Experience the future of website building. We offer AI features powered by ZipWP to help you build your website 10x faster.',
					'astra-sites'
				),
			},
			button: {
				text: __( 'Build with AI', 'astra-sites' ),
				onClick: handleBuildWithAIPress,
			},
		},
		{
			id: 'classic-templates',
			show: showClassicTemplates,
			icon: (
				<RectangleStackIcon className="w-12 h-12 text-accent-st-secondary stroke-1" />
			),
			onKeyDown: ( event ) =>
				handleKeyPress( event, () => {
					dispatch( {
						type: 'set',
						currentIndex: 2,
					} );
				} ),
			heading: {
				text: __( 'Classic Starter Templates', 'astra-sites' ),
				icon: 'rectangle-stack',
			},
			description: {
				text: __(
					'Begin the website-building process with our extensive library of professionally designed templates tailored to meet your requirements.',
					'astra-sites'
				),
			},
			button: {
				text: __( 'Build with Templates', 'astra-sites' ),
				type: 'secondary',
				onClick: () => {
					const newIndex =
						builder === 'fse' ||
						( isBeaverBuilderDisabled && isElementorDisabled )
							? 2
							: 1;

					dispatch( {
						type: 'set',
						builder:
							builder === 'ai-builder' ? 'gutenberg' : builder,
						currentIndex: newIndex,
					} );
					removeLocalStorageItem( 'st-scroll-position' );
				},
			},
		},
	];

	// Determine grid columns based on what should be shown
	let colClass = '';
	switch ( builders.filter( ( _builder ) => _builder?.show ).length ) {
		case 2:
			colClass = 'md:grid-cols-2';
			break;
		case 3:
			colClass = 'md:grid-cols-3';
			break;
	}

	return (
		<DefaultStep
			content={
				<div className="flex-1 flex flex-col justify-center items-center pb-14 sm:pb-10 lg:pb-0">
					<div className="w-full flex justify-center">
						<h1 className="w-[390px]">
							{ __(
								'How would you like to build your website?',
								'astra-sites'
							) }
						</h1>
					</div>
					<p className="screen-description" />
					<div
						className={ `max-w-full lg:max-w-[1200px] grid grid-cols-1 ${ colClass } place-content-center gap-6 ist-fadeinUp` }
					>
						{ builders.map(
							( {
								id,
								show,
								icon,
								className,
								onKeyDown,
								heading,
								description,
								button,
							} ) =>
								show && (
									<div
										key={ id }
										className={ classNames(
											'flex-col flex bg-white pt-10 pb-8 px-8 text-left relative rounded-xl max-w-[356px]',
											className
										) }
										tabIndex="0"
										onKeyDown={ onKeyDown }
									>
										{ icon }
										<div className="mt-6 text-xl font-semibold leading-7 mb-2.5 text-heading-text">
											{ heading?.text }
										</div>
										<div className="zw-sm-normal text-body-text">
											{ description?.text }
										</div>
										<div className="pt-10 mt-auto">
											<Button
												className="w-full h-10"
												type={ button?.type }
												onClick={ button?.onClick }
											>
												{ button?.text }{ ' ' }
												<ArrowRightIcon className="w-5 h-5 ml-2" />
											</Button>
										</div>
									</div>
								)
						) }
					</div>
					<LimitExceedModal
						onOpenChange={ () => {
							// remove params
							const urlParams = new URLSearchParams(
								window.location.search
							);
							const token = urlParams.get( 'token' );
							if ( token ) {
								const url = removeQueryArgs(
									window.location.href,
									'token',
									'email',
									'action',
									'credit_token'
								);

								window.location = url;
							}
						} }
					/>
					{ /* Back to the wordpress dashboard button */ }
					<button
						className="mx-auto flex items-center justify-center gap-2 mt-10 border-0 bg-transparent focus:outline-none text-zip-body-text text-sm font-normal cursor-pointer"
						onClick={ () =>
							window.open( starterTemplates.adminUrl, '_self' )
						}
					>
						<ArrowRightStartOnRectangleIcon className="w-5 h-5" />
						<span>
							{ __( 'Exit to Dashboard', 'astra-sites' ) }
						</span>
					</button>
				</div>
			}
			actions={
				<>
					<PreviousStepLink
						before
						customizeStep={ true }
						onClick={ () => {
							window.location.href = starterTemplates.adminUrl;
						} }
					>
						{ __( 'Back', 'astra-sites' ) }
					</PreviousStepLink>
				</>
			}
		/>
	);
};

export default SiteType;
