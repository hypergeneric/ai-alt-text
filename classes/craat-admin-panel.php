<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class CRAAT_AdminPanel {
	
	/**
	 * __construct
	 * 
	 * @param   void
	 * @return  void
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'wp_ajax_craat_save_admin_page', [ $this, 'save_admin_page' ] );
			add_action( 'wp_ajax_craat_logs_get_page', [ $this, 'logs_get_page' ] );
			add_action( 'wp_ajax_craat_logs_clear', [ $this, 'logs_clear' ] );
			add_action( 'wp_ajax_craat_get_stats_data', [ $this, 'get_stats_data' ] );
		}
	}

	/**
	 * get_stats_data
	 *
	 * Get the stats data to power the graph
	 *
	 * @param   void
	 * @return  void
	 */
	public function get_stats_data() {

		// set vars based on requested format
		$timespan   = filter_input( INPUT_POST, 'timespan', FILTER_SANITIZE_STRING );
		switch ( $timespan ) {
			case 'last-30d':
				$date_format       = 'Y-m-d';
				$interval_period   = '1 day';
				$date_start_period = '-30 day';
				$date_end_period   = '+1 day';
				break;
			case 'last-60d':
				$date_format       = 'Y-m-d';
				$interval_period   = '1 day';
				$date_start_period = '-60 day';
				$date_end_period   = '+1 day';
				break;
			case 'last-1y':
				$date_format       = 'Y-m-d';
				$interval_period   = '1 day';
				$date_start_period = 'first day of January this year';
				$date_end_period   = '+1 day';
				break;
		}
		$stats      = craat()->options()->get( 'stats' );
		$date_array = [];
		$chart_data = [];
		$date_start = new DateTime();
		$date_end   = new DateTime();
		$interval   = DateInterval::createFromDateString( $interval_period );
		
		// create our data group
		foreach ( $stats as $key => $value ) {
			$date_key = $key;
			if ( $interval_period != '1 hour' ) {
				$date_key = explode( '-', $key );
				array_pop( $date_key );
				$date_key = implode( '-', $date_key );
			}
			if ( ! isset( $date_array[$date_key] ) ) {
				$date_array[$date_key] = 0;
			}
			$date_array[$date_key] += $value;
		}

		// create our chart data
		$date_start->modify( $date_start_period );
		$date_end->modify( $date_end_period );
		foreach ( new DatePeriod( $date_start, $interval, $date_end ) as $dt ) {
			$key   = $dt->format( $date_format );
			$value = 0;
			if ( isset( $date_array[$key] ) ) {
				$value = $date_array[$key];
			}
			$chart_data[$key] = $value;
		}

		// send response
		wp_send_json_success( $chart_data );
		
	}

	/**
	 * logs_clear
	 *
	 * clear the log file.
	 *
	 * @param   void
	 * @return  void
	 */
	public function logs_clear() {

		wp_send_json_success( craat()->logs()->clear() );
		
	}

	/**
	 * logs_get_next
	 *
	 * get the next page of blocked logs.
	 *
	 * @param   void
	 * @return  void
	 */
	public function logs_get_page() {

		$page = filter_input( INPUT_POST, 'page', FILTER_VALIDATE_INT );
		$rows = craat()->logs()->get_logs( $page );
		
		wp_send_json_success( $rows );
		
	}
	
	/**
	 * save_admin_page
	 *
	 * Update Form Data when submitted
	 *
	 * @param   void
	 * @return  void
	 */
	public function save_admin_page() {
		
		$literals = [ 'api_key', 'keyword_seeding', 'language', 'cron_timeout' ];
		$bools = [ 'generate_on_save', 'cron_enabled', 'enable_logging' ];
		
		$post_clean = filter_input_array( INPUT_POST, [
			'api_key'           => FILTER_SANITIZE_STRING,
			'keyword_seeding'   => FILTER_SANITIZE_STRING,
			'language'          => FILTER_SANITIZE_STRING,
			'cron_timeout'      => FILTER_SANITIZE_NUMBER_INT,
			'generate_on_save'  => FILTER_VALIDATE_BOOLEAN,
			'cron_enabled'      => FILTER_VALIDATE_BOOLEAN,
			'enable_logging'    => FILTER_VALIDATE_BOOLEAN,
		] );
		
		foreach ( $literals as $key ) {
			if ( isset( $post_clean[ $key ] ) ) {
				craat()->options()->set( $key, $post_clean[ $key ] );
			}
		}
		
		foreach ( $bools as $key ) {
			if ( isset( $post_clean[ $key ] ) ) {
				craat()->options()->set( $key, $post_clean[ $key ] == 'true' );
			}
		}
		
	}

}
