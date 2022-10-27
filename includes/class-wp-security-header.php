<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Security_Header
 * @subpackage Wp_Security_Header/includes
 */

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use WPSecurity\Header\WP_Security_Header_SCP;
use WPSecurity\SrvApi\Endpoint\Make_Remote_Exec;

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
 * @package    Wp_Security_Header
 * @subpackage Wp_Security_Header/includes
 * @author     Jens Wiecker <email@jenswiecker.de>
 */
class Wp_Security_Header {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wp_Security_Header_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected Wp_Security_Header_Loader $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected string $plugin_name;

    /**
     * The Public API ID_RSA.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $id_rsa plugin API ID_RSA.
     */
    private string $id_rsa;

    /**
     * The PLUGIN API ID_RSA.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $id_plugin_rsa plugin API ID_RSA.
     */
    private string $id_plugin_rsa;

    /**
     * The PLUGIN API ID_RSA.
     *
     * @since    1.0.0
     * @access   private
     * @var      object $plugin_api_config plugin API ID_RSA.
     */
    private object $plugin_api_config;


    /**
     * The Public API DIR.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $api_dir plugin API DIR.
     */
    private string $api_dir;

    /**
     * The plugin Slug Path.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $srv_api_dir plugin Slug Path.
     */
    private string $srv_api_dir;

    /**
     * Store plugin main class to allow public access.
     *
     * @since    1.0.0
     * @var object The main class.
     */
    public object $main;

    /**
     * The plugin Slug Path.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_slug plugin Slug Path.
     */
    private string $plugin_slug;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected string $version = '';

    /**
     * The current database version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $db_version The current database version of the plugin.
     */
    protected string $db_version;

    /**
     * TWIG autoload for PHP-Template-Engine
     * the plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      Environment $twig TWIG autoload for PHP-Template-Engine
     */
    private Environment $twig;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @throws LoaderError
     * @since    1.0.0
     */
	public function __construct() {


		$this->plugin_name = WP_SECURITY_HEADER_BASENAME;
        $this->plugin_slug = WP_SECURITY_HEADER_SLUG_PATH;
        $this->main = $this;

        /**
         * Currently plugin version.
         * Start at version 1.0.0 and use SemVer - https://semver.org
         * Rename this for your plugin and update it as you release new versions.
         */
        $plugin = get_file_data(plugin_dir_path(dirname(__FILE__)) . $this->plugin_name . '.php', array('Version' => 'Version'), false);
        if (!$this->version) {
            $this->version = $plugin['Version'];
        }

        if (defined('WP_SECURITY_HEADER_DB_VERSION')) {
            $this->db_version = WP_SECURITY_HEADER_DB_VERSION;
        } else {
            $this->db_version = '1.0.0';
        }

        $this->check_dependencies();
        $this->load_dependencies();

        $twigAdminDir = plugin_dir_path(dirname(__FILE__)) . 'admin' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR;
        $twig_loader = new FilesystemLoader($twigAdminDir);
        $twig_loader->addPath($twigAdminDir . 'layout', 'partials-layout');
        $twig_loader->addPath($twigAdminDir . 'loops', 'partials-loops');
        $twig_loader->addPath($twigAdminDir . 'Templates', 'partials-templates');
        $twig_loader->addPath($twigAdminDir . 'pages', 'partials-page');
        $this->twig = new Environment($twig_loader);
        //JOB Twig-Filter
        $language = new TwigFilter('__', function ($value) {
            return __($value, 'wp-security-header');
        });
        $md5Hash = new TwigFilter('md5Hash', function ($value) {
            if($value){
                return '';
            }
            return md5($value);
        });

        $this->twig->addFilter($language);
        $this->twig->addFilter($md5Hash);

        //JOB SRV API
        $this->srv_api_dir = plugin_dir_path(dirname(__FILE__)) . 'admin' . DIRECTORY_SEPARATOR .'srv-api' . DIRECTORY_SEPARATOR;

        if (is_file($this->srv_api_dir  . 'id_rsa' . DIRECTORY_SEPARATOR . $this->plugin_name.'_id_rsa')) {
            $this->id_plugin_rsa = base64_encode($this->srv_api_dir . DIRECTORY_SEPARATOR . 'id_rsa' . $this->plugin_name.'_id_rsa');
        } else {
            $this->id_plugin_rsa = '';
        }
        if (is_file($this->srv_api_dir  . 'config' . DIRECTORY_SEPARATOR . 'config.json')) {
            $this->plugin_api_config = json_decode( file_get_contents( $this->srv_api_dir  . 'config' . DIRECTORY_SEPARATOR . 'config.json'));
        } else {
            $this->plugin_api_config = (object) [];
        }

		$this->set_locale();
        $this->register_wp_remote_exec();
        $this->register_wp_security_header_csp_header();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wp_Security_Header_Loader. Orchestrates the hooks of the plugin.
	 * - Wp_Security_Header_i18n. Defines internationalization functionality.
	 * - Wp_Security_Header_Admin. Defines all hooks for the admin area.
	 * - Wp_Security_Header_Public. Defines all hooks for the public side of the site.
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
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-security-header-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-security-header-i18n.php';

        /**
         * Composer-Autoload
         * Composer Vendor for Theme|Plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/traits/WpSecurityHeaderTrait.php';

        /**
         * Composer-Autoload
         * Composer Vendor for Theme|Plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/vendor/autoload.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-security-header-admin.php';

        //JOB SRV API Endpoint
        /**
         * SRV WP-Remote Exec
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/srv-api/config/class_make_remote_exec.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wp-security-header-public.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class_wp_security_header_scp.php';

		$this->loader = new Wp_Security_Header_Loader();

	}

    /**
     * Check PHP and WordPress Version
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function check_dependencies(): void
    {
        global $wp_version;
        if (version_compare(PHP_VERSION, WP_SECURITY_HEADER_MIN_PHP_VERSION, '<') || $wp_version < WP_SECURITY_HEADER_MIN_WP_VERSION) {
            $this->maybe_self_deactivate();
        }
    }

    /**
     * Self-Deactivate
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function maybe_self_deactivate(): void
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        deactivate_plugins($this->plugin_slug);
        add_action('admin_notices', array($this, 'self_deactivate_notice'));
    }

    /**
     * Self-Deactivate Admin Notiz
     * of the plugin.
     *
     * @since    1.0.0
     * @access   public
     */
    public function self_deactivate_notice(): void
    {
        echo sprintf('<div class="notice notice-error is-dismissible" style="margin-top:5rem"><p>' . __('This plugin has been disabled because it requires a PHP version greater than %s and a WordPress version greater than %s. Your PHP version can be updated by your hosting provider.', 'hupa-teams') . '</p></div>', WP_SECURITY_HEADER_MIN_PHP_VERSION, WP_SECURITY_HEADER_MIN_WP_VERSION);
        exit();
    }

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wp_Security_Header_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wp_Security_Header_i18n();

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

        if (!get_option($this->plugin_name.'_user_role')) {
            update_option($this->plugin_name.'_user_role', 'manage_options');
        }

        if (!get_option($this->plugin_name.'_csp_settings')) {

            $s = [
                'google_fonts' => 1,
                'google_apis' => 1,
                'adobe_fonts' => 1,
                'csp_aktiv' => 0
            ];
            update_option($this->plugin_name.'_csp_settings', $s);
        }

		$plugin_admin = new Wp_Security_Header_Admin( $this->get_plugin_name(), $this->get_version(), $this->main, $this->twig );

        //Admin Menu | AJAX
        $this->loader->add_action('admin_menu', $plugin_admin, 'register_security_header_menu');
        $this->loader->add_action('wp_ajax_SecurityHeaderHandle', $plugin_admin, 'prefix_ajax_SecurityHeaderHandle');
        //Plugin Settings Link
        $this->loader->add_filter('plugin_action_links_' . $this->plugin_name . '/' . $this->plugin_name . '.php', $plugin_admin, 'wp_security_header_plugin_add_action_link');

        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );

        //JOB UPDATE CHECKER
        $this->loader->add_action('init', $plugin_admin, 'set_security_header_update_checker');
        $this->loader->add_action('in_plugin_update_message-' . WP_SECURITY_HEADER_SLUG_PATH . '/' . WP_SECURITY_HEADER_SLUG_PATH . '.php', $plugin_admin, 'security_header_show_upgrade_notification', 10, 2);

}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Wp_Security_Header_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

    /**
     * Register API SRV Rest-Api Endpoints
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function register_wp_remote_exec()
    {
        global $wpRemoteExec;
        $wpRemoteExec = Make_Remote_Exec::instance($this->plugin_name, $this->get_version(), $this->main);
    }

    /**
     * Register all the hooks related to the CSP-Header Plugins functionality
     * of the plugin.
     *
     * @since    2.0.0
     * @access   private
     */
    private function register_wp_security_header_csp_header() {
        global $cspPluginHeader;
        $cspPluginHeader = WP_Security_Header_SCP::init( $this->plugin_name, $this->get_version(), $this->main);
        $this->loader->add_filter('wp_loaded', $cspPluginHeader, 'wp_security_header_get_footer_data');
        $this->loader->add_action('template_redirect', $cspPluginHeader, 'set_security_header_template_redirect');
        $this->loader->add_filter('style_loader_tag', $cspPluginHeader, 'wp_security_header_style_tag_nonce', 10,3);

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
	public function get_plugin_name(): string
    {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wp_Security_Header_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader(): Wp_Security_Header_Loader
    {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version(): string
    {
		return $this->version;
	}

    /**
     * @return Environment
     */
    public function get_twig(): Environment
    {
        return $this->twig;
    }

    /**
     * Retrieve the database version number of the plugin.
     *
     * @return    string    The database version number of the plugin.
     * @since     1.0.0
     */
    public function get_db_version(): string
    {
        return $this->db_version;
    }

    public function get_plugin_api_config():object {
        return $this->plugin_api_config;
    }

}
