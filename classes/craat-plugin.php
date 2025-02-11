<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class CRAAT_Plugin {

	/**
	 * install
	 *
	 * Run installation functions.
	 *
	 * @param   void
	 * @return  void
	 */
	public static function install() {

		craat()->options()->save_defaults();

	}

	/**
	 * uninstall
	 *
	 * Run installation functions.
	 *
	 * @param   void
	 * @return  void
	 */
	public static function uninstall() {
		
		craat()->options()->delete_defaults();
		craat()->logs()->delete();
		wp_clear_scheduled_hook( 'craat_cron_generate' );

	}
	
	/**
	 * __construct
	 * 
	 * @param   void
	 * @return  void
	 */
	public function __construct() {
		
		register_uninstall_hook( CRAAT_FILE, [ __CLASS__, 'uninstall' ] );
		register_deactivation_hook( CRAAT_FILE, [ __CLASS__, 'uninstall' ] );
		register_activation_hook( CRAAT_FILE, [ __CLASS__, 'install' ] );
		
		if ( is_admin() ) {
			add_filter( 'plugin_action_links_' . CRAAT_BASENAME . '/ai-alt-text.php', [ $this, 'add_settings_link' ] );
			add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
			add_action( 'admin_menu', [ $this, 'admin_page' ] );
		}
		
	}
	
	/**
	 * add_settings_link
	 *
	 * Add settings link on plugin page
	 *
	 * @param   array $links The links array.
	 * @return  array The links array.
	 */
	public function add_settings_link( $links ) {
		$links[] = '<a href="' . $this->get_admin_url() . '">' . __( 'Settings' ) . '</a>';
		return $links;
	}
	
	/**
	 * admin_enqueue_scripts
	 *
	 * Register and enqueue admin stylesheet & scripts
	 *
	 * @param   void
	 * @return  void
	 */
	public function admin_enqueue_scripts( $hook ) {
		// Always enqueue on the plugin settings page
		if ( $this->get_current_admin_url() == $this->get_admin_url() ) {
			wp_register_style( 'craat_plugin_stylesheet', CRAAT_PLUGIN_DIR . 'admin/css/admin.css', [], CRAAT_VERSION );
			wp_enqueue_style( 'craat_plugin_stylesheet' );
			wp_register_script( 'craat_script', CRAAT_PLUGIN_DIR . 'admin/js/admin.js', [ 'jquery' ], CRAAT_VERSION, false );
			wp_localize_script( 'craat_script', 'craat_obj', [ 'ajax_url' => admin_url( 'admin-ajax.php' ) ] );
			wp_enqueue_script( 'craat_script' );
			wp_enqueue_script( 'craat_apexcharts', CRAAT_PLUGIN_DIR . 'admin/js/apexcharts.3.53.0.js', [], CRAAT_VERSION, false );
		}
		// Enqueue script on Media Library (Grid View & Modal)
		if ( $hook === 'upload.php' ) {
			wp_enqueue_script( 'craat-media-refresh', CRAAT_PLUGIN_DIR . 'admin/js/craat-media-refresh.js', [ 'jquery', 'media-views' ], CRAAT_VERSION, false );
		}
	}
	
	/**
	 * admin_page
	 *
	 * Register admin page and menu.
	 *
	 * @param   void
	 * @return  void
	 */
	public function admin_page() {
		add_submenu_page(
			'options-general.php',
			__( 'AI Alt Text', 'ai-alt-text' ),
			__( 'AI Alt Text', 'ai-alt-text' ),
			'administrator',
			CRAAT_DIRNAME,
			[ $this, 'admin_page_settings' ],
			100
		);
	}
	
	/**
	 * admin_page_settings
	 *
	 * Render admin view
	 *
	 * @param   void
	 * @return  void
	 */
	public function admin_page_settings() {
		require_once CRAAT_DIRNAME . '/admin/view.php';
	}
	
	/**
	 * get_current_admin_url
	 *
	 * Get the current admin url.  Thanks WC!
	 *
	 * @param   void
	 * @return  void
	 */
	function get_current_admin_url() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$uri = preg_replace( '|^.*/wp-admin/|i', '', $uri );
		if ( ! $uri ) {
			return '';
		}
		return remove_query_arg( [ '_wpnonce' ], admin_url( $uri ) );
	}
	
	/**
	 * get_admin_url
	 *
	 * Add settings link on plugin page
	 *
	 * @param   void
	 * @return  string the admin url
	 */
	public function get_admin_url() {
		return admin_url( 'options-general.php?page=' . CRAAT_BASENAME );
	}

}
