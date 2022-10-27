<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Security_Header
 * @subpackage Wp_Security_Header/admin
 */

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use WPSecurity\Header\WP_Security_Header_Ajax;
use WPSecurity\Header\WpSecurityHeaderTrait;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Security_Header
 * @subpackage Wp_Security_Header/admin
 * @author     Jens Wiecker <email@jenswiecker.de>
 */
class Wp_Security_Header_Admin {

    use WpSecurityHeaderTrait;
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $basename    The ID of this plugin.
	 */
	private string $basename;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private string $version;

    /**
     * Store plugin main class to allow public access.
     *
     * @since    1.0.0
     * @access   private
     * @var Wp_Security_Header $main The main class.
     */
    private Wp_Security_Header $main;

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
	 * Initialize the class and set its properties.
	 *
	 * @param      string    $plugin_name The name of this plugin.
	 * @param string $version    The version of this plugin.
	 *@since    1.0.0
	 */
	public function __construct(string $plugin_name, string $version, Wp_Security_Header $main, Environment $twig ) {

		$this->basename = $plugin_name;
		$this->version = $version;
        $this->main = $main;
        $this->twig = $twig;
	}

    public function register_security_header_menu()
    {
        $hook_suffix = add_menu_page(
            __('Security Header', 'wp-security-header'),
            __('Security Header', 'wp-security-header'),
            get_option($this->basename.'_user_role'),
            'wp-security-header-start',
            array($this, 'admin_wp_security_header_startpage'),
            self::get_svg_icons('shield'), 116
        );

        add_action('load-' . $hook_suffix, array($this, 'wp_security_header_load_ajax_admin_options_script'));

        //Options Page
        $hook_suffix = add_options_page(
            __('Security Header', 'wp-security-header'),
            __('Security Header', 'wp-security-header'),
            get_option($this->basename.'_user_role'),
            'wp-security-header-options',
            array($this, 'admin_wp_security_header_options_page')
        );

        add_action('load-' . $hook_suffix, array($this, 'wp_security_header_load_ajax_admin_options_script'));
    }

    /**
     * ============================================
     * =========== PLUGIN SETTINGS LINK ===========
     * ============================================
     */
    public static function wp_security_header_plugin_add_action_link($data): array
    {
        if (!current_user_can(get_option('wp-security-header_user_role'))) {
            return $data;
        }
        return array_merge(
            $data,
            array(
                sprintf(
                    '<a href="%s">%s</a>',
                    add_query_arg(
                        array(
                            'page' => 'wp-security-header-options'
                        ),
                        admin_url('/options-general.php')
                    ),
                    __("Settings", "wp-security-header")
                )
            )
        );
    }

    public function admin_wp_security_header_startpage() :void
    {
        if(!get_option($this->basename.'-plugin_security_header')) {
            $header = $this->wp_security_headers_default_settings('header');
            update_option($this->basename.'-plugin_security_header', $header);
        }
        $headers = get_option($this->basename.'-plugin_security_header');
        $items = [
            '0' => [
                'bezeichnung' => 'Header',
                'id' => 'ah',
                'table' => $headers['ah']
            ],
            '1' => [
                'bezeichnung' => 'Content-Security-Policy (CSP)',
                'id' => 'csp',
                'table' => $headers['csp']
            ],
            '2' => [
                'bezeichnung' => 'Permissions-Policy',
                'id' => 'pr',
                'table' => $headers['pr']
            ],
        ];

        $twigData = [
            'version' => $this->version,
            'title' => __('Security Header', 'wp-security-header'),
            'db' => $this->main->get_db_version(),
            'second_title' => __('Settings', 'wp-security-header'),
            'data' => $items
        ];
        try {
            echo $this->main->get_twig()->render('@partials-templates/security-header-template.twig', $twigData);
        } catch (LoaderError|SyntaxError|RuntimeError $e) {
            echo $e->getMessage();
        } catch (Throwable $e) {
            echo $e->getMessage();
        }
    }

    public function admin_wp_security_header_options_page():void
    {
        $twigData = [
            'version' => $this->version,
            'title' => __('Security Header', 'wp-security-header'),
            'db' => $this->main->get_db_version(),
            'second_title' => __('Settings', 'wp-security-header'),
            'select_role' =>  $this->wp_security_headers_default_settings('select_user_role'),
            'set_role' => get_option($this->basename.'_user_role'),
            'ds' => get_option($this->basename.'_csp_settings')
        ];
        try {
            echo $this->main->get_twig()->render('@partials-page/wp-security-header-options.twig', $twigData);
        } catch (LoaderError|SyntaxError|RuntimeError $e) {
            echo $e->getMessage();
        } catch (Throwable $e) {
            echo $e->getMessage();
        }
    }

    public function wp_security_header_load_ajax_admin_options_script ():void
    {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        $title_nonce = wp_create_nonce('wp_security_admin_handle');

        wp_register_script('wp-security-header-admin-ajax-script', '', [], '', true);
        wp_enqueue_script('wp-security-header-admin-ajax-script');
        wp_localize_script('wp-security-header-admin-ajax-script', 'wp_security_obj', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => $title_nonce,
            'rest_url' => get_rest_url(),
            'language' => $this->wp_security_headers_default_settings('ajaxLanguage')
        ));
    }

    /**
     * Register AJAX Prefix  admin area.
     *
     * @throws LoaderError
     * @since    1.0.0
     */
    public function prefix_ajax_SecurityHeaderHandle(): void
    {
        check_ajax_referer('wp_security_admin_handle');
        require_once 'ajax/class_wp_security_header_ajax.php';
        $adminAjaxHandle = new WP_Security_Header_Ajax($this->basename, $this->version, $this->main, $this->twig);
        wp_send_json($adminAjaxHandle->wp_security_header_admin_ajax_handle());
    }

    /**
     * Register the Update-Checker for the Plugin.
     *
     * @since    1.0.0
     */
    public function set_security_header_update_checker()
    {

        if (get_option("{$this->basename}_update_config") && get_option($this->basename . '_update_config')->update->update_aktiv) {
            $securityHeaderUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
                get_option("{$this->basename}_update_config")->update->update_url_git,
                WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $this->basename . DIRECTORY_SEPARATOR . $this->basename . '.php',
                $this->basename
            );

            switch (get_option("{$this->basename}_update_config")->update->update_type) {
                case '1':
                    $securityHeaderUpdateChecker->getVcsApi()->enableReleaseAssets();
                    break;
                case '2':
                    $securityHeaderUpdateChecker->setBranch(get_option("{$this->basename}_update_config")->update->branch_name);
                    break;
            }
        }
    }

    /**
     * add plugin upgrade notification
     */

    public function security_header_show_upgrade_notification($current_plugin_metadata, $new_plugin_metadata)
    {

        if (isset($new_plugin_metadata->upgrade_notice) && strlen(trim($new_plugin_metadata->upgrade_notice)) > 0) {
            // Display "upgrade_notice".
            echo sprintf('<span style="background-color:#d54e21;padding:10px;color:#f9f9f9;margin-top:10px;display:block;"><strong>%1$s: </strong>%2$s</span>', esc_attr('Important Upgrade Notice', 'wp-security-header'), esc_html(rtrim($new_plugin_metadata->upgrade_notice)));

        }
    }

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Security_Header_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Security_Header_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->basename, plugin_dir_url( __FILE__ ) . 'css/wp-security-header-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Security_Header_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Security_Header_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */
        wp_enqueue_style($this->basename . '-bootstrap-icons', plugin_dir_url(__FILE__) . 'bs/bs-icons/bootstrap-icons.css', array(), $this->version, 'all');
        wp_enqueue_style($this->basename . '-sweetalert2', plugin_dir_url(__FILE__) . 'tools/sweetalert2/sweetalert2.min.css', array(), $this->version, 'all');
        wp_enqueue_style($this->basename . '-animate', plugin_dir_url(__FILE__) . 'css/animate.min.css', array(), $this->version, 'all');
        wp_enqueue_style($this->basename . '-bootstrap', plugin_dir_url(__FILE__) . 'bs/bootstrap.min.css', array(), $this->version, 'all');
        wp_enqueue_style($this->basename . '-dashboard', plugin_dir_url(__FILE__) . 'css/admin-dashboard.css', array(), $this->version, 'all');

        wp_enqueue_script($this->basename . '-bootstrap-bundle', plugin_dir_url(__FILE__) . 'bs/bootstrap.bundle.min.js', array(), $this->version, true);
        wp_enqueue_script($this->basename . '-sweetalert2', plugin_dir_url(__FILE__) . 'tools/sweetalert2/sweetalert2.all.min.js', array(), $this->version, true);

        wp_enqueue_script( $this->basename.'-main', plugin_dir_url( __FILE__ ) . 'js/main.js', array( 'jquery' ), $this->version, false );
        wp_enqueue_script( $this->basename, plugin_dir_url( __FILE__ ) . 'js/wp-security-header-admin.js', array( 'jquery' ), $this->version, false );

	}
    /**
     * @param $name
     *
     * @return string
     */
    protected static function get_svg_icons($name): string
    {
        $icon = '';
        switch ($name) {
            case'signpost-2':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="black" class="bi bi-signpost-2" viewBox="0 0 16 16">
                         <path d="M7 1.414V2H2a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h5v1H2.5a1 1 0 0 0-.8.4L.725 8.7a.5.5 0 0 0 0 .6l.975 1.3a1 1 0 0 0 .8.4H7v5h2v-5h5a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1H9V6h4.5a1 1 0 0 0 .8-.4l.975-1.3a.5.5 0 0 0 0-.6L14.3 2.4a1 1 0 0 0-.8-.4H9v-.586a1 1 0 0 0-2 0zM13.5 3l.75 1-.75 1H2V3h11.5zm.5 5v2H2.5l-.75-1 .75-1H14z"/>
                         </svg>';
                break;
            case'xls':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-filetype-xlsx" viewBox="0 0 16 16">
                         <path fill-rule="evenodd" d="M14 4.5V11h-1V4.5h-2A1.5 1.5 0 0 1 9.5 3V1H4a1 1 0 0 0-1 1v9H2V2a2 2 0 0 1 2-2h5.5L14 4.5ZM7.86 14.841a1.13 1.13 0 0 0 .401.823c.13.108.29.192.479.252.19.061.411.091.665.091.338 0 .624-.053.858-.158.237-.105.416-.252.54-.44a1.17 1.17 0 0 0 .187-.656c0-.224-.045-.41-.135-.56a1.002 1.002 0 0 0-.375-.357 2.028 2.028 0 0 0-.565-.21l-.621-.144a.97.97 0 0 1-.405-.176.37.37 0 0 1-.143-.299c0-.156.061-.284.184-.384.125-.101.296-.152.513-.152.143 0 .266.023.37.068a.624.624 0 0 1 .245.181.56.56 0 0 1 .12.258h.75a1.093 1.093 0 0 0-.199-.566 1.21 1.21 0 0 0-.5-.41 1.813 1.813 0 0 0-.78-.152c-.293 0-.552.05-.777.15-.224.099-.4.24-.527.421-.127.182-.19.395-.19.639 0 .201.04.376.123.524.082.149.199.27.351.367.153.095.332.167.54.213l.618.144c.207.049.36.113.462.193a.387.387 0 0 1 .153.326.512.512 0 0 1-.085.29.558.558 0 0 1-.255.193c-.111.047-.25.07-.413.07-.117 0-.224-.013-.32-.04a.837.837 0 0 1-.249-.115.578.578 0 0 1-.255-.384h-.764Zm-3.726-2.909h.893l-1.274 2.007 1.254 1.992h-.908l-.85-1.415h-.035l-.853 1.415H1.5l1.24-2.016-1.228-1.983h.931l.832 1.438h.036l.823-1.438Zm1.923 3.325h1.697v.674H5.266v-3.999h.791v3.325Zm7.636-3.325h.893l-1.274 2.007 1.254 1.992h-.908l-.85-1.415h-.035l-.853 1.415h-.861l1.24-2.016-1.228-1.983h.931l.832 1.438h.036l.823-1.438Z"/>
                         </svg>';
                break;
            case'shield':
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-shield-check" viewBox="0 0 16 16">
                        <path d="M5.338 1.59a61.44 61.44 0 0 0-2.837.856.481.481 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.725 10.725 0 0 0 2.287 2.233c.346.244.652.42.893.533.12.057.218.095.293.118a.55.55 0 0 0 .101.025.615.615 0 0 0 .1-.025c.076-.023.174-.061.294-.118.24-.113.547-.29.893-.533a10.726 10.726 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.775 11.775 0 0 1-2.517 2.453 7.159 7.159 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7.158 7.158 0 0 1-1.048-.625 11.777 11.777 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 62.456 62.456 0 0 1 5.072.56z"/>
                        <path d="M10.854 5.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 7.793l2.646-2.647a.5.5 0 0 1 .708 0z"/>
                        </svg>';
                break;
            default:
        }
        return 'data:image/svg+xml;base64,' . base64_encode($icon);

    }
}
