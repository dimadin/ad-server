<?php

/**
 * The Ad Server Plugin
 *
 * Simple ad manager.
 *
 * @package Ad_Server
 * @subpackage Ad_Server_IP_Resolver
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Ad_Server_IP_Resolver' ) ) :
/**
 * Load Ad Server component for resolving IP address.
 */
class Ad_Server_IP_Resolver {
	/**
	 * Initialize Ad_Server_Meta_Box object.
	 *
	 * Set class properties and add methods to appropriate hooks.
	 *
	 * @access public
	 *
	 * @param Ad_Server $ad_server  Object of Ad_Server class.
	 * @param string    $ip_address IP address.
	 */
	public function __construct( Ad_Server $ad_server, $ip_address = '' ) {
		// Add Ad_Server class
		$this->ad_server = $ad_server;

		// If no IP address provided, use current
		if ( '' == $ip_address ) {
			// Does header from CloudFlare exist http://stackoverflow.com/a/14985633
			if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) && ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
				$this->ad_server->ip_address = $_SERVER['HTTP_CF_CONNECTING_IP'];
			} else {
				$this->ad_server->ip_address = $_SERVER['REMOTE_ADDR'];
			}
		} else {
			$this->ad_server->ip_address = $ip_address;
			// TODO: get continent
			// https://github.com/rummik/express-cf-geoip/blob/master/lib/express-cf-geoip.js#L32
			// http://dev.maxmind.com/geoip/legacy/codes/country_continent/
			// http://dev.maxmind.com/geoip/legacy/codes/iso3166/
			// https://github.com/UsabilityEtc/country-geolocation-data/blob/master/CountryContinents.php
		}

		// Parse IP address with CloudFlare if it exists, otherwise GeoLite2
		if ( isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) && ! empty( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
			$this->ad_server->country = $_SERVER['HTTP_CF_IPCOUNTRY'];
		} else {
			$this->parse_geolite2_country();
		}
	}

	/**
	 * Get data from IP address by GeoLite2 Country database.
	 *
	 * @access protected
	 */
	protected function parse_geolite2_country() {
		try {
			$reader = new GeoIp2\Database\Reader( $this->ad_server->path . '/lib/GeoLite2/GeoLite2-Country.mmdb' );
			$record = $reader->country( $this->ad_server->ip_address );

			$this->ad_server->country   = $record->country->isoCode;
			$this->ad_server->continent = $record->continent->code;
		} catch ( \GeoIp2\Exception\AddressNotFoundException $e ) {
		} catch ( \MaxMind\Db\Reader\InvalidDatabaseException $e ) {
		}
	}
}
endif;
