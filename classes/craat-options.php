<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class CRAAT_Options {

	/** @var array Default option values. */
	public $defaults = [
		'api_key'            => '',
		'keyword_seeding'    => '',
		'language'           => 'en_US',
		'generate_on_save'   => false,
		'cron_enabled'       => false,
		'enable_logging'     => false,
		'cron_timeout'       => 30,
		'media_to_skip'      => [],
		'stats'              => [],
	];
	
	/** @var string Local array to store lookups. */
	var $lookup = [];

	/**
	 * __construct
	 * 
	 * @param   void
	 * @return  void
	 */
	public function __construct() {
	}

	/**
	 * save_defaults
	 *
	 * Save the option defaults to the DB
	 *
	 * @param   void
	 * @return  void
	 */
	public function save_defaults() {
		foreach ( $this->defaults as $key => $value ) {
			$this->set( $key, $value );
		}
	}

	/**
	 * delete_defaults
	 *
	 * Delete the option defaults from the DB
	 *
	 * @param   void
	 * @return  void
	 */
	public function delete_defaults() {
		foreach ( $this->defaults as $key => $value ) {
			delete_option( 'craat_' . $key );
		}
	}
	
	/**
	 * get
	 *
	 * Sugar function to save options meta.
	 *
	 * @param   string $name The meta name.
	 * @return  mixed The meta value
	 */
	public function get( $name, $default=null ) {
		if ( isset( $this->lookup[$name] ) ) {
			return $this->lookup[$name];
		}
		$default = isset( $this->defaults[$name] ) ? $this->defaults[$name] : $default;
		$value = get_option( 'craat_' . $name );
		return $value ? $value : $default;
	}
	
	/**
	 * set
	 *
	 * Sugar function to save post meta.
	 *
	 * @param   string $name The meta name.
	 * @param   mixed $value The meta value.
	 * @param   boolean $autoload Autoload setting.
	 * @return  void
	 */
	public function set( $name, $value, $autoload=false ) {
		if ( isset( $this->lookup[$name] ) ) {
			unset( $this->lookup[$name] );
		}
		update_option( 'craat_' . $name, $value, $autoload );
	}

}
