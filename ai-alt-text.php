<?php
/**
 * Plugin Name:  AI Alt Text Generator
 * Plugin URI:   https://compiledrogue.com/
 * Description:  Enhance your website’s accessibility and SEO with AI-powered alt text generation, automatically adding descriptive alt text to images in WordPress.
 * Version:      1.0.0
 * Author:       Compiled Rogue
 * Author URI:   https://compiledrogue.com
 * License:      GPL2 or later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  ai-alt-text
 *
 * @package     AiAltTags
 * @author      Compiled Rogue
 * @copyright   Copyright (c) 2024, Compiled Rogue LLC
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once __DIR__ . '/classes/craat-plugin.php';
require_once __DIR__ . '/classes/craat-admin-panel.php';
require_once __DIR__ . '/classes/craat-options.php';
require_once __DIR__ . '/classes/craat-logs.php';
require_once __DIR__ . '/classes/craat-generator.php';

if ( ! class_exists( 'AiAltTags' ) ) :

	class AiAltTags {
		
		/** @var string The plugin version number. */
		var $version = '1.0.0';
		
		/** @var string Shortcuts. */
		var $plugin;
		var $options;
		var $logs;
		var $generator;
		
		/**
		 * __construct
		 *
		 * A dummy constructor to ensure AiAltTags is only setup once.
		 * 
		 * @param   void
		 * @return  void
		 */
		function __construct() {
			// Do nothing.
		}
		
		/**
		 * initialize
		 *
		 * Sets up the AiAltTags plugin.
		 *
		 * @param   void
		 * @return  void
		 */
		function initialize() {

			// Define constants.
			$this->define( 'CRAAT', true );
			$this->define( 'CRAAT_DEBUG', false );
			$this->define( 'CRAAT_ACTION_PRIORITY', 99999 );
			$this->define( 'CRAAT_FILE', __FILE__ );
			$this->define( 'CRAAT_DIRNAME', dirname( __FILE__ ) );
			$this->define( 'CRAAT_PLUGIN_DIR', plugin_dir_url( __FILE__ ) );
			$this->define( 'CRAAT_BASENAME', basename( dirname( __FILE__ ) ) );
			$this->define( 'CRAAT_VERSION', $this->version );
			
			// Do all the plugin stuff.
			$this->options   = new CRAAT_Options();
			$this->logs      = new CRAAT_Logs();
			$this->plugin    = new CRAAT_Plugin();
			$this->generator = new CRAAT_Generator();

			if ( is_admin() ) {
				// load up our admin classes
				$admin = new CRAAT_AdminPanel();
			} else {
				// no front-end specific code
			}
			
		}
		
		/**
		 * __call
		 *
		 * Sugar function to access class properties
		 *
		 * @param   string $name The property name.
		 * @return  void
		 */
		public function __call( $name, $arguments ) {
			return $this->{$name};
		}
		
		/**
		 * define
		 *
		 * Defines a constant if doesnt already exist.
		 *
		 * @param   string $name The constant name.
		 * @param   mixed  $value The constant value.
		 * @return  void
		 */
		function define( $name, $value = true ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}
		
		/**
		 * log
		 *
		 * Output logging to the debug.
		 *
		 * @param   mixed  $log The value.
		 * @return  void
		 */
		function log( $log ) {
			if ( is_array( $log ) || is_object( $log ) ) {
				$log = print_r( $log, true );
			}
			if ( defined( 'CRAAT_DEBUG' ) && CRAAT_DEBUG && WP_DEBUG ) {
				error_log( $log );
			}
			craat()->logs()->log( $log );
		}
		
	}

	/*
	* craat
	*
	* The main function responsible for returning the one true AiAltTags Instance to functions everywhere.
	* Use this function like you would a global variable, except without needing to declare the global.
	*
	* @param   void
	* @return  AiAltTags
	*/
	function craat() {
		global $craat;
		// Instantiate only once.
		if ( ! isset( $craat ) ) {
			$craat = new AiAltTags();
			$craat->initialize();
		}
		return $craat;
	}

	// Instantiate.
	craat();

endif; // class_exists check
