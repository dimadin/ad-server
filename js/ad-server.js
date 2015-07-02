/**
 * Display ad from Ad Server via AJAX request.
 */
function adServerPushAJAXAds( data ) {
	// Only proceed if everything is OK
	if ( 200 == data.status ) {
		// https://stackoverflow.com/a/684692
		var adServerAdZones = data.page_data;

		// Page data is an array with zone_id => zone_data
		for ( var adServerAdZoneID in adServerAdZones ) {
			if ( adServerAdZones.hasOwnProperty(adServerAdZoneID) ) {
				var adServerAdZone = adServerAdZones[adServerAdZoneID];
				var adServerZoneHTML = adServerFormatAd( adServerAdZone );
				jQuery( '#ad-server-' + adServerAdZoneID ).html( adServerZoneHTML );
			}
		}
	}
};

/**
 * Format ad with data passed.
 */
function adServerFormatAd( adData ) {
	var adServerAdHTML;

	if ( adData.image_html ) {
		adServerAdHTML = adData.image_html;
	}

	if ( adData.tracking_url ) {
		if ( adData.image_html ) {
			adServerAdHTML = '<a href="' + adData.tracking_url + '">' + adData.image_html + '</a>';
		}
	}

	return adServerAdHTML;
}

jQuery( document ).ready( function( $ ) {
	$.getScript( adServer.ajaxURL + '?action=ad_server_jsonp_page_data&callback=adServerPushAJAXAds&page_id=' +  adServer.pageID );
} );
