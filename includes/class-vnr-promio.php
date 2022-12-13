<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://extensionforge.com
 * @since      1.0.0
 *
 * @package    Vnr_Promio
 * @subpackage Vnr_Promio/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Vnr_Promio
 * @subpackage Vnr_Promio/includes
 * @author     Steve Kraft & Peter Mertzlin <direct@extensionforge.com>
 */



add_action('set_logged_in_cookie', 'custom_get_logged_in_cookie_vnrpromio', 10, 6);
function custom_get_logged_in_cookie_vnrpromio($logged_in_cookie, $expire, $expiration, $user_id, $logged_in_text, $token)
{
		global $wpdb;
	    $user = get_user_by('id', $user_id);

		NssApi::set_api_endpoint(NssApi::API_ENDPOINT_COMPUTERWISSEN);
		
		$interessen = xprofile_get_field_data( "Interessen", $user_id, 'comma' );
		$anredex     = xprofile_get_field_data( "Anrede", $user_id, '' );
		$vorname     = xprofile_get_field_data( "Vorname", $user_id, '' );
		$nachname     = xprofile_get_field_data( "Nachname", $user_id, '' );
		$emailuser     = $user->user_email;
		$triggered = false;
		$triggered = get_user_meta($user_id,"promio_nl_send");

		if ($anredex=='Frau') { $anrede = "F";} else {$anrede = "H";}
		
		//xprofile_get_field_data
		//var_dump($interessen);
		//echo $anrede." ".$vorname." ".$nachname." ".$email;
		//var_dump($interessen);
		if($interessen){
		$interessen = "CWC, ".$interessen;

		$abos = array_map('trim', explode(",",$interessen));
		$triggered=true;
		if($triggered==false){
			// do submit
			 try {
				NssApi::subscribe(
				$emailuser,
				$abos,
				'114',
				'SEO_CW_CWC_WEB_OA_computerwissen-club',
				false,
				false,
				'',
				null,
				json_decode(json_encode([
					'attributeKey[0]' => 'FIRST_NAME',
					'attributeValue[0]' => $vorname,
					'attributeKey[1]' => 'LAST_NAME',
					'attributeValue[1]' => $nachname,
					'attributeKey[2]' => 'ANREDE',
					'attributeValue[2]' => $anrede,
					'immediateConfirmation' => 'PCemupZnsudHNWDeHd3CU2TbPVQWHpF3'
				]))
					);
				} catch (Exception $exception) {
					//echo 'Fehler: API!<br />';
					//print($exception->getMessage());
				}          

		}

		}

		update_user_meta( get_current_user_id(), 'promio_nl_send', true );	   
}


class Vnr_Promio {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Vnr_Promio_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'VNR_PROMIO_VERSION' ) ) {
			$this->version = VNR_PROMIO_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'vnr-promio';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Vnr_Promio_Loader. Orchestrates the hooks of the plugin.
	 * - Vnr_Promio_i18n. Defines internationalization functionality.
	 * - Vnr_Promio_Admin. Defines all hooks for the admin area.
	 * - Vnr_Promio_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-vnr-promio-loader.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/NssApi.class.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-vnr-promio-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-vnr-promio-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-vnr-promio-public.php';

		$this->loader = new Vnr_Promio_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Vnr_Promio_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Vnr_Promio_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Vnr_Promio_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Vnr_Promio_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Vnr_Promio_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
