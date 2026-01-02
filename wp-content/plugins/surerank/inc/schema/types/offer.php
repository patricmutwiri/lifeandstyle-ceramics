<?php
/**
 * Offer Schema
 *
 * This file handles the functionality for the Offer schema type.
 *
 * @package SureRank
 * @since 1.0.0
 */

namespace SureRank\Inc\Schema\Types;

use SureRank\Inc\Schema\Base;
use SureRank\Inc\Traits\Get_Instance;

/**
 * Offer
 * This class handles the functionality for the Offer schema type.
 *
 * @since 1.0.0
 */
class Offer extends Base {

	use Get_Instance;

	/**
	 * Get Schema Data
	 *
	 * @return array<string, mixed>|array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	public function schema_data() {
		return [
			'title'   => 'Offer',
			'type'    => 'Offer',
			'show_on' => [
				'rules'        => [],
				'specific'     => [],
				'specificText' => [],
			],
			'fields'  => $this->parse_fields( $this->get() ),
		];
	}

	/**
	 * Get Offer Schema
	 *
	 * @return array<int, array<string, mixed>>
	 * @since 1.0.0
	 */
	public function get() {
		return apply_filters(
			'surerank_default_schema_type_offer',
			[
				[
					'id'      => 'schema_name',
					'label'   => __( 'Schema Title', 'surerank' ),
					'tooltip' => __( 'Give your schema a name to help you identify it later. This title is for internal reference only and won’t be included in your site’s structured data.', 'surerank' ),
					'show'    => true,
					'type'    => 'Title',
					'std'     => 'Offer',
				],
				$this->add_helper_property(
					'name',
					[
						'required' => true,
						'std'      => '%post.title%',
						'tooltip'  => __( 'The name of the offer.', 'surerank' ),
					]
				),
				[
					'id'       => 'acceptedPaymentMethod',
					'label'    => __( 'Accepted payment method', 'surerank' ),
					'tooltip'  => __( 'The payment method(s) accepted by seller for this offer.', 'surerank' ),
					'type'     => 'Select',
					'std'      => 'http://purl.org/goodrelations/v1#Cash',
					'required' => true,
					'options'  => [
						'http://purl.org/goodrelations/v1#ByBankTransferInAdvance' => __( 'By bank transfer in advance', 'surerank' ),
						'http://purl.org/goodrelations/v1#ByInvoice'   => __( 'By invoice', 'surerank' ),
						'http://purl.org/goodrelations/v1#Cash'        => __( 'Cash', 'surerank' ),
						'http://purl.org/goodrelations/v1#CheckInAdvance' => __( 'Check in advance', 'surerank' ),
						'http://purl.org/goodrelations/v1#COD'         => __( 'COD', 'surerank' ),
						'http://purl.org/goodrelations/v1#DirectDebit' => __( 'Direct debit', 'surerank' ),
						'http://purl.org/goodrelations/v1#GoogleCheckout' => __( 'Google checkout', 'surerank' ),
						'http://purl.org/goodrelations/v1#PayPal'      => __( 'PayPal', 'surerank' ),
						'http://purl.org/goodrelations/v1#PaySwarm'    => __( 'PaySwarm', 'surerank' ),
					],
				],
				[
					'id'          => 'addOn',
					'label'       => __( 'Add on', 'surerank' ),
					'tooltip'     => __( 'An additional offer that can only be obtained in combination with the first base offer (e.g. supplements and extensions that are available for a surcharge).', 'surerank' ),
					'description' => __( 'Please create another Offer schema and link to this property via a dynamic variable', 'surerank' ),
				],
				$this->add_helper_property(
					'duration',
					[
						'id'      => 'advanceBookingRequirement',
						'label'   => __( 'Advance booking requirement', 'surerank' ),
						'type'    => 'Group',
						'tooltip' => __( 'The amount of time that is required between accepting the offer and the actual usage of the resource or service.', 'surerank' ),
					]
				),
				$this->add_helper_property(
					'aggregateRating',
					[
						'tooltip' => __( 'The overall rating, based on a collection of reviews or ratings, of the offer.', 'surerank' ),
					]
				),
				[
					'id'      => 'areaServed',
					'label'   => __( 'Area served', 'surerank' ),
					'tooltip' => __( 'The geographic area where a service or offered item is provided.', 'surerank' ),
				],
				[
					'id'      => 'asin',
					'label'   => __( 'Asin', 'surerank' ),
					'tooltip' => __( 'An Amazon Standard Identification Number (ASIN) is a 10-character alphanumeric unique identifier assigned by Amazon.com and its partners for product identification within the Amazon organization. See documentation from Amazon for authoritative details', 'surerank' ),
				],
				[
					'id'       => 'availability',
					'label'    => __( 'Availability', 'surerank' ),
					'type'     => 'Select',
					'std'      => 'https://schema.org/InStock',
					'required' => true,
					'options'  => [
						'https://schema.org/InStock'  => __( 'In stock', 'surerank' ),
						'https://schema.org/SoldOut'  => __( 'Sold out', 'surerank' ),
						'https://schema.org/PreOrder' => __( 'Pre order', 'surerank' ),
					],
				],
				[
					'id'      => 'availabilityStarts',
					'label'   => __( 'Availability starts', 'surerank' ),
					'tooltip' => __( 'The beginning of the availability of the product or service included in the offer.', 'surerank' ),
					'type'    => 'Date',
				],
				[
					'id'      => 'availabilityEnds',
					'label'   => __( 'Availability ends', 'surerank' ),
					'tooltip' => __( 'The end of the availability of the product or service included in the offer.', 'surerank' ),
					'type'    => 'Date',
				],
				[
					'id'               => 'availableAtOrFrom',
					'label'            => __( 'Available at or from', 'surerank' ),
					'type'             => 'Group',
					'tooltip'          => __( 'The place(s) from which the offer can be obtained (e.g. store locations).', 'surerank' ),
					'cloneable'        => true,
					'cloneItemHeading' => __( 'Place', 'surerank' ),
					'fields'           => [
						[
							'id'       => '@type',
							'std'      => 'Place',
							'type'     => 'Hidden',
							'required' => true,
						],
						$this->add_helper_property(
							'name',
							[
								'label'    => __( 'Name', 'surerank' ),
								'tooltip'  => __( 'The name of the item.', 'surerank' ),
								'required' => true,
							]
						),
						$this->add_helper_property(
							'address',
							[
								'label'    => '',
								'required' => true,
								'tooltip'  => __( 'The physical address where students go to take the program.', 'surerank' ),
							]
						),
						[
							'id'      => 'url',
							'label'   => __( 'URL', 'surerank' ),
							'tooltip' => __( 'URL of the item.', 'surerank' ),
						],
					],
				],
				[
					'id'      => 'availableDeliveryMethod',
					'label'   => __( 'Available delivery method', 'surerank' ),
					'type'    => 'Select',
					'options' => [
						'http://purl.org/goodrelations/v1#DeliveryModeDirectDownload' => __( 'Direct download', 'surerank' ),
						'http://purl.org/goodrelations/v1#DeliveryModeFreight' => __( 'Freight', 'surerank' ),
						'http://purl.org/goodrelations/v1#DeliveryModeMail' => __( 'Mail', 'surerank' ),
						'http://purl.org/goodrelations/v1#DeliveryModeOwnFleet' => __( 'Own fleet', 'surerank' ),
						'http://purl.org/goodrelations/v1#DeliveryModePickUp' => __( 'Pick up', 'surerank' ),
						'http://purl.org/goodrelations/v1#DHL' => __( 'DHL', 'surerank' ),
						'http://purl.org/goodrelations/v1#FederalExpress' => __( 'Federal express', 'surerank' ),
						'http://purl.org/goodrelations/v1#UPS' => __( 'UPS', 'surerank' ),
					],
				],
				[
					'id'      => 'businessFunction',
					'label'   => __( 'Business function', 'surerank' ),
					'tooltip' => __( 'The business function specifies the type of activity or access (i.e., the bundle of rights) offered by the organization or business person through the offer.', 'surerank' ),
					'type'    => 'Select',
					'std'     => 'http://purl.org/goodrelations/v1#Sell',
					'options' => [
						'http://purl.org/goodrelations/v1#ConstructionInstallation' => __( 'Construction installation', 'surerank' ),
						'http://purl.org/goodrelations/v1#Dispose'  => __( 'Dispose', 'surerank' ),
						'http://purl.org/goodrelations/v1#LeaseOut' => __( 'Lease out', 'surerank' ),
						'http://purl.org/goodrelations/v1#Maintain' => __( 'Maintain', 'surerank' ),
						'http://purl.org/goodrelations/v1#ProvideService' => __( 'Provide service', 'surerank' ),
						'http://purl.org/goodrelations/v1#Repair'   => __( 'Repair', 'surerank' ),
						'http://purl.org/goodrelations/v1#Sell'     => __( 'Sell', 'surerank' ),
						'http://purl.org/goodrelations/v1#Buy'      => __( 'Buy', 'surerank' ),
					],
				],
				[
					'label'   => __( 'Category', 'surerank' ),
					'id'      => 'category',
					'tooltip' => __( 'A category for the item. Greater signs or slashes can be used to informally indicate a category hierarchy.', 'surerank' ),
				],
				[
					'label'   => __( 'Checkout page URL template', 'surerank' ),
					'id'      => 'checkoutPageURLTemplate',
					'tooltip' => __( 'A URL template (RFC 6570) for a checkout page for an offer.', 'surerank' ),
				],
				$this->add_helper_property(
					'duration',
					[
						'id'      => 'deliveryLeadTime',
						'label'   => __( 'Delivery lead time', 'surerank' ),
						'type'    => 'Group',
						'tooltip' => __( 'The typical delay between the receipt of the order and the goods either leaving the warehouse or being prepared for pickup, in case the delivery method is on site pickup.', 'surerank' ),
					]
				),
				$this->add_helper_property(
					'description',
					[
						'tooltip' => __( 'A description of the offer.', 'surerank' ),
					]
				),
				[
					'label'   => __( 'Disambiguating description', 'surerank' ),
					'id'      => 'disambiguatingDescription',
					'tooltip' => __( 'A sub property of description. A short description of the item used to disambiguate from other.', 'surerank' ),
				],
				[
					'id'      => 'eligibleCustomerType',
					'label'   => __( 'Eligible customer type', 'surerank' ),
					'tooltip' => __( 'The type(s) of customers for which the given offer is valid.', 'surerank' ),
					'type'    => 'Select',
					'std'     => 'http://purl.org/goodrelations/v1#Business',
					'options' => [
						'http://purl.org/goodrelations/v1#Business' => __( 'Business', 'surerank' ),
						'http://purl.org/goodrelations/v1#Enduser'  => __( 'End-user', 'surerank' ),
						'http://purl.org/goodrelations/v1#PublicInstitution' => __( 'Public institution', 'surerank' ),
						'http://purl.org/goodrelations/v1#Reseller' => __( 'Reseller', 'surerank' ),
					],
				],
				$this->add_helper_property(
					'duration',
					[
						'id'      => 'eligibleDuration',
						'label'   => __( 'Eligible duration', 'surerank' ),
						'type'    => 'Group',
						'tooltip' => __( 'The duration for which the given offer is valid.', 'surerank' ),
					]
				),
				$this->add_helper_property(
					'QuantitativeValue',
					[
						'id'      => 'eligibleQuantity',
						'label'   => __( 'Eligible quantity', 'surerank' ),
						'type'    => 'Group',
						'tooltip' => __( 'The interval and unit of measurement of ordering quantities for which the offer or price specification is valid. This allows e.g. specifying that a certain freight charge is valid only for a certain quantity.', 'surerank' ),
					]
				),
				[
					'id'        => 'eligibleRegion',
					'label'     => __( 'Eligible region', 'surerank' ),
					'tooltip'   => __( 'The ISO 3166-1 (ISO 3166-1 alpha-2) or ISO 3166-2 code, the place, or the GeoShape for the geo-political region(s) for which the offer or delivery charge specification is valid.', 'surerank' ),
					'cloneable' => true,
				],
				[
					'id'      => 'eligibleTransactionVolume',
					'label'   => __( 'Eligible transaction volume', 'surerank' ),
					'tooltip' => __( 'The transaction volume, in a monetary unit, for which the offer or price specification is valid.', 'surerank' ),
					'type'    => 'Group',
					'fields'  => [
						[
							'id'       => '@type',
							'std'      => 'PriceSpecification',
							'type'     => 'Hidden',
							'required' => true,
						],
						[
							'id'       => 'price',
							'label'    => __( 'Price', 'surerank' ),
							'tooltip'  => __( 'The price amount for the specified offer.', 'surerank' ),
							'required' => true,
						],
						[
							'id'       => 'priceCurrency',
							'label'    => __( 'Price currency', 'surerank' ),
							'tooltip'  => __( 'The currency of the price for the specified offer.', 'surerank' ),
							'required' => true,
						],
					],
				],
				[
					'label'   => __( 'GTIN', 'surerank' ),
					'id'      => 'gtin',
					'tooltip' => __( 'A Global Trade Item Number (GTIN). GTINs identify trade items, including products and services, using numeric identification codes.', 'surerank' ),
				],
				[
					'label'   => __( 'GTIN-8', 'surerank' ),
					'id'      => 'gtin8',
					'tooltip' => __( 'The GTIN-8 code of the product, or the product to which the offer refers. This code is also known as EAN/UCC-8 or 8-digit EAN.', 'surerank' ),
				],
				[
					'label'   => __( 'GTIN-12', 'surerank' ),
					'id'      => 'gtin12',
					'tooltip' => __( 'The GTIN-12 code of the product, or the product to which the offer refers. The GTIN-12 is the 12-digit GS1 Identification Key composed of a U.P.C. Company Prefix, Item Reference, and Check Digit used to identify trade items.', 'surerank' ),
				],
				[
					'label'   => __( 'GTIN-13', 'surerank' ),
					'id'      => 'gtin13',
					'tooltip' => __( 'The GTIN-13 code of the product, or the product to which the offer refers. This is equivalent to 13-digit ISBN codes and EAN UCC-13. Former 12-digit UPC codes can be converted into a GTIN-13 code by simply adding a preceding zero.', 'surerank' ),
				],
				[
					'label'   => __( 'GTIN-14', 'surerank' ),
					'id'      => 'gtin14',
					'tooltip' => __( 'The GTIN-14 code of the product, or the product to which the offer refers.', 'surerank' ),
				],
				[
					'id'      => 'hasAdultConsideration',
					'label'   => __( 'Has adult consideration', 'surerank' ),
					'tooltip' => __( 'Used to tag an item to be intended or suitable for consumption or use by adults only.', 'surerank' ),
					'type'    => 'Select',
					'options' => [
						'https://schema/org/AlcoholConsideration'    => 'Alcohol',
						'https://schema/org/DangerousGoodConsideration' => 'Dangerous good',
						'https://schema/org/HealthcareConsideration' => 'Healthcare',
						'https://schema/org/NarcoticConsideration'   => 'Narcotic',
						'https://schema/org/ReducedRelevanceForChildrenConsideration' => 'Reduced relevance for children',
						'https://schema/org/SexualContentConsideration' => 'Sexual content',
						'https://schema/org/TobaccoNicotineConsideration' => 'Tobacco nicotine',
						'https://schema/org/UnclassifiedAdultConsideration' => 'Unclassified adult',
						'https://schema/org/ViolenceConsideration'   => 'Violence',
						'https://schema/org/WeaponConsideration'     => 'Weapon',

					],
				],
				$this->add_helper_property(
					'QuantitativeValue',
					[
						'id'      => 'hasMeasurement',
						'label'   => __( 'Has measurement', 'surerank' ),
						'type'    => 'Group',
						'tooltip' => __( 'A product measurement, for example the inseam of pants, the wheel size of a bicycle, or the gauge of a screw. Usually an exact measurement, but can also be a range of measurements for adjustable products, for exp belts and ski bindings.', 'surerank' ),
					]
				),
				[
					'id'               => 'includesObject',
					'label'            => __( 'Includes object', 'surerank' ),
					'type'             => 'Group',
					'tooltip'          => __( 'This links to a node or nodes indicating the exact quantity of the products included in an Offer or ProductCollection.', 'surerank' ),
					'cloneable'        => true,
					'cloneItemHeading' => __( 'Object', 'surerank' ),
					'fields'           => [
						[
							'id'       => '@type',
							'std'      => 'TypeAndQuantityNode',
							'type'     => 'Hidden',
							'required' => true,
						],
						[
							'id'      => 'amountOfThisGood',
							'label'   => __( 'Amount of this good', 'surerank' ),
							'tooltip' => __( 'The quantity of the goods included in the offer.', 'surerank' ),
							'show'    => true,
						],
						[
							'id'          => 'typeOfGood',
							'label'       => __( 'Type of good', 'surerank' ),
							'tooltip'     => __( 'The product or service that this is referring to.', 'surerank' ),
							'description' => __( 'Please create a Product or Service and link to this property via a dynamic variable.', 'surerank' ),
							'show'        => true,
						],
						[
							'id'      => 'businessFunction',
							'label'   => __( 'Business function', 'surerank' ),
							'tooltip' => __( 'The business function specifies the type of activity or access (i.e., the bundle of rights) offered by the organization or business person through the offer.', 'surerank' ),
							'type'    => 'Select',
							'std'     => 'http://purl.org/goodrelations/v1#Sell',
							'options' => [
								'http://purl.org/goodrelations/v1#ConstructionInstallation' => __( 'Construction installation', 'surerank' ),

								'http://purl.org/goodrelations/v1#Dispose' => __( 'Dispose', 'surerank' ),
								'http://purl.org/goodrelations/v1#LeaseOut' => __( 'Lease out', 'surerank' ),
								'http://purl.org/goodrelations/v1#Maintain' => __( 'Maintain', 'surerank' ),
								'http://purl.org/goodrelations/v1#ProvideService' => __( 'Provide service', 'surerank' ),
								'http://purl.org/goodrelations/v1#Repair' => __( 'Repair', 'surerank' ),
								'http://purl.org/goodrelations/v1#Sell' => __( 'Sell', 'surerank' ),
								'http://purl.org/goodrelations/v1#Buy' => __( 'Buy', 'surerank' ),
							],
						],
						[
							'id'      => 'unitCode',
							'label'   => __( 'Unit ode', 'surerank' ),
							'tooltip' => __( 'The unit of measurement given using the UN/CEFACT Common Code (3 characters) or a URL. Other codes than the UN/CEFACT Common Code may be used with a prefix followed by a colon.', 'surerank' ),
						],
						[
							'id'      => 'unitText',
							'label'   => __( 'Unit text', 'surerank' ),
							'tooltip' => __( 'A string or text indicating the unit of measurement. Useful if you cannot provide a standard unit code for unitCode.', 'surerank' ),
						],
					],
				],
				$this->add_helper_property(
					'image',
					[
						'tooltip' => __( 'An image of the offer.', 'surerank' ),
					]
				),
				[
					'id'        => 'ineligibleRegion',
					'label'     => __( 'Ineligible region', 'surerank' ),
					'tooltip'   => __( 'The ISO 3166-1 (ISO 3166-1 alpha-2) or ISO 3166-2 code, the place, or the GeoShape for the geo-political region(s) for which the offer or delivery charge specification is not valid, e.g. a region where the transaction is not allowed.', 'surerank' ),
					'cloneable' => true,
				],
				$this->add_helper_property(
					'QuantitativeValue',
					[
						'id'      => 'inventoryLevel',
						'label'   => __( 'Inventory level', 'surerank' ),
						'type'    => 'Group',
						'tooltip' => __( 'The current approximate inventory level for the item or items.', 'surerank' ),
					]
				),
				[
					'id'      => 'isFamilyFriendly',
					'label'   => __( 'Is family friendly', 'surerank' ),
					'tooltip' => __( 'Indicates whether this content is family friendly.', 'surerank' ),
					'type'    => 'Select',
					'std'     => 'True',
					'options' => [
						'True'  => __( 'True', 'surerank' ),
						'False' => __( 'False', 'surerank' ),
					],
				],
				$this->add_helper_property(
					'duration',
					[
						'id'      => 'leaseLength',
						'label'   => __( 'Lease length', 'surerank' ),
						'type'    => 'Group',
						'tooltip' => __( 'Length of the lease for some Accommodation, either particular to some Offer or in some cases intrinsic to the property.', 'surerank' ),
					]
				),
				[
					'label'   => __( 'Mobile URL', 'surerank' ),
					'id'      => 'mobileUrl',
					'tooltip' => __( 'Provided for specific situations in which data consumers need to determine whether one of several provided URLs is a dedicated \'mobile site\'. The property is expected only on Product and Offer, rather than Thing.', 'surerank' ),
				],
				[
					'label'   => __( 'MPN', 'surerank' ),
					'id'      => 'mpn',
					'tooltip' => __( 'The Manufacturer Part Number (MPN) of the product, or the product to which the offer refers', 'surerank' ),
				],
				[
					'id'          => 'offeredBy',
					'label'       => __( 'Offered by', 'surerank' ),
					'tooltip'     => __( 'A pointer to the organization or person making the offer.', 'surerank' ),
					'description' => __( 'Please create a Person or an Organization schema and link to this property via a dynamic variable', 'surerank' ),
				],
				[
					'id'       => 'price',
					'label'    => __( 'Price', 'surerank' ),
					'tooltip'  => __( 'The offer price of a product, or of a price component when attached to PriceSpecification and its subtypes. Use the priceCurrency property (with standard formats: ISO 4217 currency format, e.g. "USD"; Ticker symbol for cryptocurrencies, e.g. "BTC"; well known names for Local Exchange Trading Systems (LETS) and other currency types, e.g. "Ithaca HOUR") instead of including ambiguous symbols such as \'$\' in the value.', 'surerank' ),
					'required' => true,
				],
				[
					'id'       => 'priceCurrency',
					'label'    => __( 'Price currency', 'surerank' ),
					'tooltip'  => __( 'The currency of the price, or a price component when attached to PriceSpecification and its subtypes. Use standard formats: ISO 4217 currency format, e.g. "USD"; Ticker symbol for cryptocurrencies, e.g. "BTC"; well known names for Local Exchange Trading Systems (LETS) and other currency types, e.g. "Ithaca HOUR". ', 'surerank' ),
					'required' => true,
					'std'      => 'USD',
				],
				[
					'id'      => 'priceValidUntil',
					'label'   => __( 'Price valid until', 'surerank' ),
					'type'    => 'Date',
					'tooltip' => __( 'The date (in ISO 8601 date format) after which the price is no longer available.', 'surerank' ),
				],
				$this->add_helper_property( 'Review' ),
				[
					'id'          => 'seller',
					'label'       => __( 'Seller', 'surerank' ),
					'tooltip'     => __( 'An entity which offers (sells / leases / lends / loans) the services / goods. A seller may also be a provider.', 'surerank' ),
					'description' => __( 'Please create a Person or an Organization schema and link to this property via a dynamic variable', 'surerank' ),
				],
				[
					'label'   => __( 'Serial number', 'surerank' ),
					'id'      => 'serialNumber',
					'tooltip' => __( 'The serial number or any alphanumeric identifier of a particular product. When attached to an offer, it is a shortcut for the serial number of the product included in the offer.', 'surerank' ),
				],
				[
					'label'   => __( 'SKU', 'surerank' ),
					'id'      => 'sku',
					'tooltip' => __( 'The Stock Keeping Unit (SKU), i.e. a merchant-specific identifier for a product or service, or the product to which the offer refers.', 'surerank' ),
				],
				[
					'id'      => 'validFrom',
					'label'   => __( 'Valid from', 'surerank' ),
					'type'    => 'Date',
					'tooltip' => __( 'The date when the item becomes valid in ISO-8601 format', 'surerank' ),
				],
				[
					'id'      => 'validThrough',
					'label'   => __( 'Valid through', 'surerank' ),
					'tooltip' => __( 'The date after when the item is not valid. For example the end of an offer, salary period, or a period of opening hours. ', 'surerank' ),
				],
			]
		);
	}
}
