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

/**
	 * @var string Holds the http url targeting the api endpoint
	 */

function subscribe($email_address, $newsletter_abbreviations, $opt_in_process_id, $affiliate,$trader_fox, $bullvestor, $coreg, $adref,
									 $additional_properties) {

		$customer_remote_address = null;
		if (isset($_SERVER['HTTP_X_REAL_IP']) && strlen(trim($_SERVER['HTTP_X_REAL_IP'])) > 0) {
			$customer_remote_address = $_SERVER['HTTP_X_REAL_IP'];
		} elseif (isset($_SERVER['REMOTE_ADDR']) && strlen(trim($_SERVER['REMOTE_ADDR'])) > 0) {
			$customer_remote_address = $_SERVER['REMOTE_ADDR'];
		}

		$calling_service = sprintf(
			'%s://%s%s',
			isset($_SERVER['HTTPS']) ? 'https' : 'http',
			isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '',
			isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''
		);

		$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

		$visitor_id = isset($_COOKIE['ePPxlID']) ? $_COOKIE['ePPxlID'] : null;

		$session_id = isset($_COOKIE['ePxlID']) ? $_COOKIE['ePxlID'] : null;

		$response = remote_call(
			'Subscribe',
			[
				$email_address, $newsletter_abbreviations, $opt_in_process_id, $customer_remote_address,
				$calling_service, $affiliate, $visitor_id, $session_id, $trader_fox, $bullvestor, $coreg, $user_agent,
				$adref, $additional_properties
			]
		);

		if (!is_array($response)) {
			throw new Exception('invalid response format');
		}

		// relocate to soi page if provided
		if (isset($response[0])) {
			if ($callback !== null && is_callable($callback)) {
				$callback();
			}
			relocate($response[0]);
		}

	}



	function relocate($uri){
		header(sprintf('Location: %s', $uri));
		exit();
	}

	

	
	/**
	 * @param array $parameters
	 * @return mixed
	 * @throws Exception
	 */
	function remote_call($method, $parameters = []) {

		 $API_ENDPOINT_COMPUTERWISSEN = 'https://nss-computerwissen.de/api';
		 $API_PASSWORD = 'iiJnaOixQqVnyqGwHXKG';

		$request = new stdClass();
		$request->Method = $method;
		$request->Parameters = $parameters;

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $API_ENDPOINT_COMPUTERWISSEN);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$raw_response = curl_exec($ch);
		if (curl_errno($ch) > 0) {
			throw new Exception(curl_error($ch));
		}
		curl_close($ch);

		$response = json_decode($raw_response);
		if ($response === null) {
			file_put_contents(
				__DIR__ . DIRECTORY_SEPARATOR . 'nss_api_errors.txt',
				sprintf("======== %s ========\r\n%s\r\n================", strftime('%Y-%m-%d %H:%M:%S'), $raw_response),
				FILE_APPEND
			);
			throw new Exception('response json cannot be decoded');
		}

		if (count($response->Errors) > 0) {
			$error_message = '';
			foreach($response->Errors as $error) {
				if (!is_object($error)) {
					$error_message .= 'invalid error format';
				} else {
					if (!isset($error->Message) || strlen(trim($error->Message)) === 0) {
						$error_message .= 'unknown error';
					} else {
						$error_message .= $error->Message;
					}
				}
				$error_message .= ', ';
			}
			throw new Exception(trim($error_message, ' ,'));
		}

		if (!isset($response->Result)) {
			throw new Exception('invalid response format');
		}

		return $response->Result;

	}



add_action('set_logged_in_cookie', 'custom_get_logged_in_cookie_vnrpromio', 10, 6);
function custom_get_logged_in_cookie_vnrpromio($logged_in_cookie, $expire, $expiration, $user_id, $logged_in_text, $token)
{
		global $wpdb;
	    $current_user = get_user_by( 'id', $user_id ); 
    	$emailuser = $current_user->user_email;

		$anredex     = "H";
		$vorname     = "unbekannt";
		$nachname     = "unbekannt";
		
		if(null !== xprofile_get_field_data( "Interessen", $user_id, 'comma' )){
			$interessenx = xprofile_get_field_data( "Interessen", $user_id, 'comma' );	
		}
		if(null !== xprofile_get_field_data( "Anrede", $user_id, '' )){$anredex     = xprofile_get_field_data( "Anrede", $user_id, '' );}
		if(null !== xprofile_get_field_data( "Vorname", $user_id, '' )){$vorname     = xprofile_get_field_data( "Vorname", $user_id, '' );}
		if(null !== xprofile_get_field_data( "Nachname", $user_id, '' )){$nachname     = xprofile_get_field_data( "Nachname", $user_id, '' );}
		
		
		$triggered = false;
		$triggered = get_user_meta($user_id,"promio_nl_send");

		if ($anredex=='Frau') { $anrede = "F";} else {$anrede = "H";}
		
		//xprofile_get_field_data
		//var_dump($interessen);
		//echo $anrede." ".$vorname." ".$nachname." ".$email;
		//var_dump($interessen);
		$interessen = "CWC, ".$interessenx;
		if($interessen){
		
		$abos = array_map('trim', explode(",",$interessen));

		//delete to activate
		//$triggered=false;
		//$testo = $wpdb->insert("tester", array('text' => json_encode($triggered)), array('%s') );
		//$testo = $wpdb->insert("tester", array('text' => json_encode($emailuser)), array('%s') );
		//$testo = $wpdb->insert("tester", array('text' => json_encode($interessen)), array('%s') );
		//$testo = $wpdb->insert("tester", array('text' => json_encode($vorname)), array('%s') );
		//$testo = $wpdb->insert("tester", array('text' => json_encode($nachname)), array('%s') );
		//$testo = $wpdb->insert("tester", array('text' => json_encode($anrede)), array('%s') );
		
		if($triggered==false){
			// do submit
			 try {
				subscribe(
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
	setcookie('NSS_API_ERROR', 'OK!', time() + (86400 * 30), "/"); 
				} catch (Exception $exception) {
					//echo 'Fehler: API!<br />';
					//$testo = $wpdb->insert("tester", array('text' => json_encode($exception->getMessage())), array('%s') );
					//print($exception->getMessage());
			
			setcookie('NSS_API_ERROR', $exception->getMessage(), time() + (86400 * 30), "/"); 
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
