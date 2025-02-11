<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class CRAAT_Generator {

	/**
	 * __construct
	 * 
	 * @param   void
	 * @return  void
	 */
	public function __construct() {

		// hook into existing revision filters
		add_action( 'add_attachment', [ $this, 'save_generate' ], CRAAT_ACTION_PRIORITY );
		add_action( 'attachment_updated', [ $this, 'save_generate_on_update' ], CRAAT_ACTION_PRIORITY, 2 );

		// setup CRON
		$hours_for_cron = 1;
		$cron_enabled   = craat()->options()->get( 'cron_enabled' );
		if ( $cron_enabled ) {
			add_action( 'craat_cron_generate', [ $this, 'cron_generate' ] );
			if ( ! wp_next_scheduled( 'craat_cron_generate' ) ) {
				wp_schedule_single_event( time() + ( $hours_for_cron * 60 * 60 ), 'craat_cron_generate' );
			}
		}

	}

	/**
	 * cron_generate
	 *
	 * The function to run the generate via CRON.  We're doing maxrows, because on a site with
	 * thousands of posts, it's time-intensive.  You can't really get through more than 
	 * one post per second on deletion.  But the settings are available if you have a more performant
	 * server.  We're setting the default timeout to 30s for CRON since it should be more performant 
	 * than the page save event.
	 *
	 * @return  void
	 */
	function cron_generate () {
		$cron_timeout      = craat()->options()->get( 'cron_timeout' );
		$cron_enabled      = craat()->options()->get( 'cron_enabled' );
		$media_to_skip     = craat()->options()->get( 'media_to_skip' );
		if ( ! is_array( $media_to_skip ) ) {
			$media_to_skip = [];
		}
		if ( $cron_timeout < 10 ) {
			$cron_timeout = 10;
		}
		if ( $cron_enabled ) {
			craat()->log( "CRON Generate Started" );
			$args = [
				'post_type'      => 'attachment',
				'post_status'    => ['inherit', 'publish'],
				'numberposts'    => 100,
				'exclude'        => $media_to_skip,
				'meta_query'     => [
					[
						'key'     => '_wp_attachment_image_alt',
						'compare' => 'NOT EXISTS',
					],
				],
				'post_mime_type' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
				'fields'         => 'ids',
			];
			$media = get_posts( $args );
			craat()->log( "Found Media: " . count( $media ) );
			if ( count( $media ) == 0 ) {
				craat()->log( "No media found without Alt text!" );
			} else {
				craat()->generator()->generate_text( $media, $cron_timeout );
			}
		}
	}

	/**
	 * save_generate
	 *
	 * Perform the on-save generate function from WP's add_attachment hook.
	 *
	 * @param   array $attachment_id The attachment ID coming from the original event.
	 * @return  void
	 */
	function save_generate ( $attachment_id ) {
		if ( ! craat()->options()->get( 'generate_on_save' ) ) {
			craat()->log( "Skipping Generate for Attachment ID " . $attachment_id );
			return [];
		}
		craat()->log( "Save Generate Started for Attachment ID " . $attachment_id );
		craat()->generator()->generate_text( [ $attachment_id ] );
	}

	/**
	 * save_generate_on_update
	 *
	 * Runs when an existing media item is updated in the media library.
	 *
	 * @param int   $attachment_id  The ID of the updated attachment.
	 * @param array $updated_data   The updated post data array.
	 * @return void
	 */
	function save_generate_on_update( $attachment_id, $updated_data ) {
		if ( empty( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) ) {
			$this->save_generate( $attachment_id );
		}
	}

	/**
	 * generate_text
	 *
	 * The main function for generating alt text.
	 *
	 * @param   array $media The array of media library items to modify.
	 * @param   array $limit The maximum time for this function to run
	 * @return  void
	 */
	function generate_text ( $media, $limit=15 ) {
		$count             = 0;
		$generate          = [];
		$skip              = [];
		$start             = microtime( true );
		// loop through each media item to determine if we're going to generate it, or skip
		foreach( $media as $attachment_id ) {
			$mime_type    = get_post_mime_type( $attachment_id );
			$ignore_media = strpos( $mime_type, 'image' ) === false;
			$ignore_media = apply_filters( 'craat_ignore_media', false, $attachment_id );
			// let's add it to the skip array so we don't query it in the future
			if ( $ignore_media ) {
				$skip[] = $attachment_id;
			} else {
				$generate[] = $attachment_id;
			}
		}
		// if we have posts to skip, save the ID's so we don't query them in the future via the CRON
		if ( count( $skip ) > 0 ) {
			$media_to_skip = craat()->options()->get( 'media_to_skip' );
			if ( ! is_array( $media_to_skip ) ) {
				$media_to_skip = [];
			}
			for ( $i=0; $i < count( $skip ); $i++ ) {
				if ( ! in_array( $skip[ $i ], $media_to_skip ) ) {
					craat()->log( "Saving " . $skip[ $i ] . " to media skip array" );
					$media_to_skip[] = $skip[ $i ];
				}
			}
			$media_to_skip = array_unique( $media_to_skip );
			craat()->options()->set( 'media_to_skip', $media_to_skip );
		}
		// ok, run though as many updates as possible before we hit our time limit ( minus 5 seconds to allow for the api call )
		$generated = $this->fetch_ai_generated_alt_text( $generate, $limit - 5 );
		foreach ( $generated as $attachment_id => $ai_generated_alt ) {
			// Check if an alt tag already exists
			$alt_text = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
			if ( ! empty( $alt_text ) ) {
				continue;
			}
			// Save the generated alt tag
			craat()->log( "Updating Attachment ID:" . $attachment_id );
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $ai_generated_alt );
			// update loop
			craat()->generator()->update_stats();
			$count += 1;
			$elapsed = microtime( true ) - $start;
			if ( $elapsed >= $limit ) {
				craat()->log( "Updating stopped at " . $elapsed );
				break;
			}
		}
		craat()->log( "Total updated this run: " . $count );
	}

	/**
	 * fetch_ai_generated_alt_text
	 *
	 * The main function for generating alt text.
	 *
	 * @param   array $attachment_ids The array of media library items to modify.
	 * @param   array $timeout The timeout for the external call.
	 * @return  array Returns any array of name value pairs of server-generated Attchment ID's and their corresponding Alt text.
	 */
	function fetch_ai_generated_alt_text( $attachment_ids, $timeout = 5 ) {
		craat()->log( "Starting API request ..." );
		// Prepare the items array with ID -> URL pairs
		$items = [];
		foreach ( $attachment_ids as $id ) {
			$image_url = wp_get_attachment_url( $id );
			if ( $image_url ) {
				$items[ $id ] = $image_url;
			}
		}
		// If no valid items, return an empty array early
		if ( empty( $items ) ) {
			craat()->log( "Nothing to Fetch!" );
			return [];
		}
		// Limit the number of items to 10
		$items = array_slice( $items, 0, 10, true );
		// Prepare the payload
		$payload = [
			'api_key' => craat()->options()->get( 'api_key' ),
			'keyword_seeding' => craat()->options()->get( 'keyword_seeding' ),
			'language' => craat()->options()->get( 'language' ),
			'items'   => $items,
		];
		// Make the POST request using wp_remote_post
		$response = wp_remote_post( 'https://hypergeneric.com/ai-alt-text/', [
			'body'    => json_encode( $payload ),
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'timeout' => $timeout,
		] );
		// Check for errors in request
		if ( is_wp_error( $response ) ) {
			craat()->log( "Invalid / error from API." );
			return [];
		}
		// Get the response body
		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			craat()->log( "API returned an empty response." );
			return [];
		}
		// Decode JSON response into a PHP array
		$data = json_decode( $body, true );
		// Ensure response is valid and contains generated items
		if ( ! is_array( $data ) || empty( $data ) ) {
			craat()->log( "No text generated on this API request." );
			return [];
		}
		// Return only the valid name-value pairs from the response
		craat()->log( "Total generated this run: " . count( $data ) );
		return $data;
	}


	/**
	 * update_stats
	 *
	 * Get all the stats.
	 *
	 * @param   void
	 * @return  void
	 */
	function update_stats () {
		$craat_stats = craat()->options()->get( 'stats' );
		$date_key = gmdate( "Y-m-d-H" );
		if ( ! isset( $craat_stats[$date_key] ) ) {
			$craat_stats[$date_key] = 0;
		}
		$craat_stats[$date_key] += 1;
		craat()->options()->set( 'stats', $craat_stats );
	}

}
