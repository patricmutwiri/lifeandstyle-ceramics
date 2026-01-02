import { useState, memo, useEffect, useMemo } from '@wordpress/element';
import { Badge, Select } from '@bsf/force-ui';
import { __, sprintf } from '@wordpress/i18n';
import {
	formatDate,
	parseHeaders,
	getStatusLabel,
	getStatusVariant,
	ShadowDOM,
	stringToHtml,
	containsHtmlTag,
	convertUTCConnection,
	get_connection_message,
} from '@utils/utils';
import CollapsibleSection from '@components/collapsible-section';
import ContentGuardChecks from './content-guard-checks';
import { AttachmentList } from '@components/attachments/attachments';
import Title from '@components/title/title';

const DrawerLogBody = ( { log } ) => {
	const [ selectedRetry, setSelectedRetry ] = useState( null );
	const [ groupedResponses, setGroupedResponses ] = useState( {} );

	const attachments = Array.isArray( log.attachments ) ? log.attachments : [];

	// Parse serialized JSON or comma-separated values in email fields
	const parseEmailField = ( field ) => {
		if ( ! field ) {
			return [];
		}
		try {
			// Check if serialized JSON and parse
			return typeof field === 'string' && field.startsWith( '[' )
				? JSON.parse( field )
				: field.split( ',' ).map( ( email ) => email.trim() );
		} catch {
			return field.split( ',' ).map( ( email ) => email.trim() );
		}
	};

	// Function to extract charset from Content-Type
	const extractCharset = ( contentType ) => {
		if ( ! contentType ) {
			return '';
		}
		const match = contentType.match( /charset=([\w-]+)/i );
		return match ? match[ 1 ] : '';
	};

	// Use the utility function to parse headers
	const headers = log.headers ? parseHeaders( log.headers ) : {};

	// Initialize headerFields with default empty arrays or values
	const headerFields = {
		From: headers.From ? headers.From[ 0 ] : '',
		'Reply-To': headers[ 'Reply-To' ] || [],
		CC: headers.Cc || [],
		BCC: headers.Bcc || [],
		'Content-Type': headers[ 'Content-Type' ]
			? headers[ 'Content-Type' ][ 0 ]
			: '',
		Charset: extractCharset(
			headers[ 'Content-Type' ] ? headers[ 'Content-Type' ][ 0 ] : ''
		),
		'X-Mailer': headers[ 'X-Mailer' ] ? headers[ 'X-Mailer' ][ 0 ] : '',
	};

	useEffect( () => {
		const response = log.response || [];
		const grouped = response.reduce( ( acc, res ) => {
			const retryNumber = Number( res.retry );
			if ( ! acc[ retryNumber ] ) {
				acc[ retryNumber ] = [];
			}
			acc[ retryNumber ].push( res );
			return acc;
		}, {} );

		setGroupedResponses( grouped );
	}, [ log.response ] );

	const sortedRetryKeys = useMemo( () => {
		return Object.keys( groupedResponses )
			.map( Number )
			.sort( ( a, b ) => a - b );
	}, [ groupedResponses ] );

	const retries = useMemo( () => {
		return sortedRetryKeys.map( ( retry ) => ( {
			label: `Response ${ retry + 1 }`,
			value: retry,
		} ) );
	}, [ sortedRetryKeys ] );

	const getRetryLabel = ( value ) => {
		const retry = retries.find( ( r ) => r.value === value );
		return retry ? retry.label : '';
	};

	useEffect( () => {
		if ( retries.length > 0 ) {
			const isValidRetry = retries.some(
				( retry ) => retry.value === selectedRetry
			);
			if ( ! isValidRetry ) {
				setSelectedRetry( retries[ retries.length - 1 ].value );
			}
		} else {
			setSelectedRetry( null );
		}
	}, [ retries, selectedRetry ] );

	const handleRetryChange = ( selectedValue ) => {
		setSelectedRetry( selectedValue );
	};

	const createAndAttachEmailBody = ( content ) => ( node ) => {
		if ( ! node || ! content ) {
			return;
		}
		const emailBodyShadow = new ShadowDOM( node );
		if ( emailBodyShadow.hasChildNodes() ) {
			return;
		}
		// Wrapper for adding 8px padding to the email body
		const wrapper = document.createElement( 'div' );
		wrapper.style.padding = containsHtmlTag( content ) ? '0' : '0.5rem';
		// Escape HTML tags and convert plain text to HTML
		wrapper.innerHTML = stringToHtml( content );
		// Append the wrapper to the email body shadow
		emailBodyShadow.appendChild( wrapper );
	};

	return (
		<div className="rounded-lg bg-background-secondary">
			{ /* Email Information Section */ }
			<CollapsibleSection alwaysOpen>
				<CollapsibleSection.Content>
					<div className="space-y-2">
						<div className="flex items-center justify-between">
							<p>
								<strong className="text-sm font-normal text-text-tertiary">
									{ __( 'Sent by:', 'suremails' ) }
								</strong>{ ' ' }
								<strong className="text-sm font-normal text-text-primary">
									{ log.email_from }
								</strong>
							</p>
							<div className="flex items-center space-x-2">
								<Badge
									className="py-0.5"
									label={ log.connection }
									variant="blue"
									disableHover
								/>
								<Badge
									className="py-0.5"
									label={ getStatusLabel(
										log.status,
										log?.response
									) }
									variant={ getStatusVariant(
										log.status,
										log?.response
									) }
									disableHover
								/>
							</div>
						</div>

						{ /* Second Row: Sent to and Date */ }
						<div className="flex items-center justify-between">
							<p>
								<strong className="text-sm font-normal text-text-tertiary">
									{ __( 'Sent to:', 'suremails' ) }
								</strong>{ ' ' }
								<strong className="text-sm font-normal text-text-primary">
									{ parseEmailField( log.email_to ).join(
										', '
									) }
								</strong>
							</p>
							<p className="text-sm font-normal text-text-secondary">
								{ formatDate( log.updated_at, {
									day: true,
									month: true,
									year: true,
									hour: true,
									minute: true,
									hour12: true,
								} ) }
							</p>
						</div>

						{ /* Third Row: Subject */ }
						<p>
							<strong className="text-sm font-normal text-text-tertiary">
								{ __( 'Subject:', 'suremails' ) }
							</strong>{ ' ' }
							<strong className="text-sm font-normal text-text-primary">
								{ log.subject }
							</strong>
						</p>
						<div className="flex items-start justify-start gap-1">
							<p>
								<strong className="text-sm font-normal text-text-tertiary">
									{ __( 'Resent:', 'suremails' ) }
								</strong>{ ' ' }
								<strong className="text-sm font-normal text-text-primary">
									{ log.meta?.resend }
								</strong>
							</p>
							<p>
								<strong className="text-sm font-normal text-text-tertiary">
									{ __( 'Retries:', 'suremails' ) }
								</strong>{ ' ' }
								<strong className="text-sm font-normal text-text-primary">
									{ log.meta?.retry }
								</strong>
							</p>
						</div>
					</div>
				</CollapsibleSection.Content>
			</CollapsibleSection>

			{ /* Email Body Section */ }
			<CollapsibleSection defaultOpen>
				<CollapsibleSection.Trigger>
					<Title tag="h4" title={ __( 'Email Body', 'suremails' ) } />
				</CollapsibleSection.Trigger>
				<CollapsibleSection.Content className="bg-background-secondary rounded overflow-hidden">
					<div ref={ createAndAttachEmailBody( log.body ) }>
						{ log.body
							? false
							: __( 'No email body available.', 'suremails' ) }
					</div>
				</CollapsibleSection.Content>
			</CollapsibleSection>

			{ /* Content guard checks */ }
			<ContentGuardChecks log={ log } />

			{ /* Server Response Section */ }
			<CollapsibleSection defaultOpen>
				<CollapsibleSection.Trigger>
					<Title
						tag="h4"
						title={ __( 'Server Response', 'suremails' ) }
					/>
				</CollapsibleSection.Trigger>
				<CollapsibleSection.Content>
					<div className="flex flex-col gap-2 mt-2">
						{ ' ' }
						<Select
							value={ getRetryLabel( selectedRetry ) }
							onChange={ ( value ) => handleRetryChange( value ) }
							className="w-full"
							by={ selectedRetry }
						>
							<Select.Button />
							<Select.Options>
								{ retries.map( ( retry ) => (
									<Select.Option
										key={ retry.value }
										value={ retry.value }
									>
										{ retry.label }
									</Select.Option>
								) ) }
							</Select.Options>
						</Select>
						<div className="flex items-start justify-between gap-2 mt-2 rounded-md bg-background-primary">
							<div className="flex-1">
								{ groupedResponses[ selectedRetry ]?.length >
								0 ? (
									<div className="flex flex-col gap-2">
										{ ' ' }
										{ groupedResponses[ selectedRetry ].map(
											( res, index ) => (
												<div
													key={ index }
													className="py-2 px-2 gap-3 rounded border bg-background-secondary"
												>
													{ /* Message and Connection inline */ }
													<div className="flex flex-col gap-1">
														{ ' ' }
														<p className="font-medium text-sm text-text-primary">
															{ __(
																'Message:',
																'suremails'
															) }{ ' ' }
															<span className="font-normal text-sm text-text-secondary">
																{ res.Message }
															</span>
														</p>
														<p className="font-medium text-sm text-text-primary">
															{ __(
																'Connection:',
																'suremails'
															) }{ ' ' }
															<span className="font-normal text-text-secondary">
																{ res.timestamp
																	? get_connection_message(
																			res.Connection,
																			res.timestamp
																	  )
																	: convertUTCConnection(
																			res.Connection
																	  ) }
															</span>
														</p>
													</div>
												</div>
											)
										) }
									</div>
								) : (
									<p className="text-sm text-text-secondary">
										{ __(
											'No server response available.',
											'suremails'
										) }
									</p>
								) }
							</div>
						</div>
					</div>
				</CollapsibleSection.Content>
			</CollapsibleSection>

			{ /* Email Headers Section */ }
			<CollapsibleSection defaultOpen>
				<CollapsibleSection.Trigger>
					<Title
						tag="h4"
						title={ __( 'Email Headers', 'suremails' ) }
					/>
				</CollapsibleSection.Trigger>
				<CollapsibleSection.Content>
					<div className="mt-2 space-y-2">
						<div>
							<strong className="text-sm font-normal text-text-tertiary">
								{ __( 'From:', 'suremails' ) }
							</strong>{ ' ' }
							<strong className="text-sm font-normal text-text-primary">
								{ headerFields.From &&
								headerFields.From.length > 0 ? (
									<span>{ headerFields.From }</span>
								) : (
									''
								) }
							</strong>
						</div>
						<div>
							<strong className="text-sm font-normal text-text-tertiary">
								{ __( 'Reply-To:', 'suremails' ) }
							</strong>{ ' ' }
							<strong className="text-sm font-normal text-text-primary">
								{ headerFields[ 'Reply-To' ] &&
								headerFields[ 'Reply-To' ].length > 0 ? (
									<span>
										{ headerFields[ 'Reply-To' ].join(
											', '
										) }
									</span>
								) : (
									''
								) }
							</strong>
						</div>
						<div>
							<strong className="text-sm font-normal text-text-tertiary">
								{ __( 'CC:', 'suremails' ) }
							</strong>{ ' ' }
							<strong className="text-sm font-normal text-text-primary">
								{ headerFields.CC &&
								headerFields.CC.length > 0 ? (
									<span>
										{ headerFields.CC.join( ', ' ) }
									</span>
								) : (
									''
								) }
							</strong>
						</div>
						<div>
							<strong className="text-sm font-normal text-text-tertiary">
								{ __( 'BCC:', 'suremails' ) }
							</strong>{ ' ' }
							<strong className="text-sm font-normal text-text-primary">
								{ headerFields.BCC &&
								headerFields.BCC.length > 0 ? (
									<span>
										{ headerFields.BCC.join( ', ' ) }
									</span>
								) : (
									''
								) }
							</strong>
						</div>
						<div>
							<strong className="text-sm font-normal text-text-tertiary">
								{ __( 'Content-Type:', 'suremails' ) }
							</strong>{ ' ' }
							<strong className="text-sm font-normal text-text-primary">
								{ headerFields[ 'Content-Type' ] || '' }
							</strong>
						</div>
						<div>
							<strong className="text-sm font-normal text-text-tertiary">
								{ __( 'X-Mailer:', 'suremails' ) }
							</strong>{ ' ' }
							<strong className="text-sm font-normal text-text-primary">
								{ headerFields[ 'X-Mailer' ] || '' }
							</strong>
						</div>
					</div>
				</CollapsibleSection.Content>
			</CollapsibleSection>

			{ /* Email Attachments Section */ }
			<CollapsibleSection defaultOpen={ attachments.length > 0 }>
				<CollapsibleSection.Trigger>
					<Title
						tag="h4"
						title={ sprintf(
							/* translators: %d: Number of attachments. */
							__( 'Attachments (%d)', 'suremails' ),
							attachments.length
						) }
					/>
				</CollapsibleSection.Trigger>
				<CollapsibleSection.Content>
					<AttachmentList attachments={ attachments } />
				</CollapsibleSection.Content>
			</CollapsibleSection>
		</div>
	);
};

export default memo( DrawerLogBody );
