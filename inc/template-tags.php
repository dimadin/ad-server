<?php
/**
 * Ad Server Template Functions.
 *
 * @package Ad_Server
 * @subpackage Template_Tags
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get an array of zones from a page.
 *
 * @param int $page ID of the page.
 * @return array $page_data An array of zones of the page.
 */
function get_ad_server_page_data( $page ) {
	global $ad_server;
	return $ad_server->get_page_data( $page );
}

/**
 * Get data for single ad from a zone.
 *
 * @param int $zone ID of the zone.
 * @return array $ad_data An array of elements of the ad.
 */
function get_ad_server_zone_data( $zone ) {
	global $ad_server;
	return $ad_server->get_ad_server_zone_data( $zone );
}

/**
 * Get single ad from a zone.
 *
 * @param int $zone ID of the zone.
 * @return string $ad_html HTML code of the ad.
 */
function get_ad_server_zone( $zone ) {
	global $ad_server;
	return $ad_server->get_ad_server_zone( $zone );
}

/**
 * Display single ad from a zone.
 *
 * @param int $zone ID of the zone.
 */
function ad_server_zone( $zone ) {
	echo get_ad_server_zone( $zone );
}
