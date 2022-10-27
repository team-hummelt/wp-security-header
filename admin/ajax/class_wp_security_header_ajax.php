<?php

namespace WPSecurity\Header;

use Throwable;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\TwigFilter;
use Wp_Security_Header;
use stdClass;

defined('ABSPATH') or die();

/**
 * Define the Admin AJAX functionality.
 *
 * Loads and defines the Admin Ajax files for this plugin
 *
 *
 * @link       https://wwdh.de/
 * @since      1.0.0
 */

/**
 * Define the AJAX functionality.
 *
 * Loads and defines the Admin Ajax files for this plugin
 *
 * @package    Wp_Security_Header
 * @subpackage Wp_Security_Header/includes
 * @author     Jens Wiecker <email@jenswiecker.de>
 */

class WP_Security_Header_Ajax
{
    /**
     * The plugin Slug Path.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $basename The ID of this plugin.
     */
    protected string $basename;

    /**
     * The AJAX METHOD
     *
     * @since    1.0.0
     * @access   private
     * @var      string $method The AJAX METHOD.
     */
    protected string $method;

    /**
     * The AJAX DATA
     *
     * @since    1.0.0
     * @access   private
     * @var      array|object $data The AJAX DATA.
     */
    private $data;

    /**
     * The trait for the default settings
     * of the plugin.
     */
    use WpSecurityHeaderTrait;

    /**
     * The Version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current Version of this plugin.
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

    public function __construct(string $basename, string $version, Wp_Security_Header $main, Environment $twig)
    {
        $this->basename = $basename;
        $this->version = $version;
        $this->main = $main;
        $this->twig = $twig;

        $this->method = '';
        if (isset($_POST['daten'])) {
            $this->data = $_POST['daten'];
            $this->method = filter_var($this->data['method'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
        }

        if (!$this->method) {
            $this->method = $_POST['method'];
        }
    }

    public function wp_security_header_admin_ajax_handle():object
    {
        $responseJson = new stdClass();
        $responseJson->type = $this->method;
        $record = new stdClass();
        $responseJson->status = false;
        $responseJson->msg = date('H:i:s', current_time('timestamp'));
        switch ($this->method) {
            case'add-header-config':
                $responseJson->type = $this->method;
                $handle = filter_input(INPUT_POST, 'handle', FILTER_SANITIZE_STRING);
                if (!$handle) {
                    $responseJson->title = __('Error', 'wp-security-header');
                    $responseJson->msg = __('Ajax transmission error', 'bootscore') . ' (Ajx - ' . __LINE__ . ')';
                    return $responseJson;
                }
                $data = [
                    'd' => [
                        'id' => $handle,
                        'table' => [
                            '0' => [
                                'name' => '',
                                'value' => '',
                                'aktiv' => 0,
                                'id' => $this->generateRandomId(6,0,6),
                                'help' => '',
                            ]
                        ]
                    ]
                ];
                try {
                    $template = $this->twig->render('@partials-loops/security-header-table.twig', $data);
                    $responseJson->template = $this->html_compress_template($template);
                } catch (LoaderError|SyntaxError|RuntimeError $e) {
                    $responseJson->msg = $e->getMessage();
                    return $responseJson;
                } catch (Throwable $e) {
                    $responseJson->msg = $e->getMessage();
                    return $responseJson;
                }
                $responseJson->handle = $handle;
                $responseJson->status = true;
                break;
            case'delete-security-header':
                $handle = filter_input(INPUT_POST, 'handle', FILTER_SANITIZE_STRING);
                $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
                if (!$handle) {
                    $responseJson->title = __('Error', 'wp-security-header');
                    $responseJson->msg = __('Ajax transmission error', 'wp-security-header') . ' (Ajx - ' . __LINE__ . ')';
                    return $responseJson;
                }

                $headers = get_option($this->basename.'-plugin_security_header');
                if(!isset($headers[$handle])){
                    $responseJson->title = __('Error', 'wp-security-header');
                    $responseJson->msg = __('Ajax transmission error', 'wp-security-header') . ' (Ajx - ' . __LINE__ . ')';
                    return $responseJson;
                }

                $arr = [];
                foreach ($headers[$handle] as $tmp) {
                    if($tmp['id'] == $id) {
                        continue;
                    }
                    $item = [
                        'name' => $tmp['name'],
                        'value' => $tmp['value'],
                        'aktiv' => $tmp['aktiv'],
                        'id' => $tmp['id'],
                        'help' => '',
                    ];
                    $arr[] = $item;
                }

                $headers[$handle] = $arr;
                update_option($this->basename.'-plugin_security_header', $headers);
                $responseJson->status = true;
                $responseJson->title = __('Saved', 'wp-security-header');
                $responseJson->msg = __('Changes saved successfully.', 'wp-security-header');
                $responseJson->id = $id;
                $responseJson->handle = $handle;
                break;
            case 'security-header-handle':
                $handle = filter_input(INPUT_POST, 'handle', FILTER_SANITIZE_STRING);
                if (!$handle) {
                    $responseJson->title = __('Error', 'wp-security-header');
                    $responseJson->msg = __('Ajax transmission error', 'wp-security-header') . ' (Ajx - ' . __LINE__ . ')';
                    return $responseJson;
                }

                $id = array($_POST['id']);
                if (!$id) {
                    $responseJson->title = __('Error', 'wp-security-header');
                    $responseJson->msg = __('Ajax transmission error', 'wp-security-header') . ' (Ajx - ' . __LINE__ . ')';
                    return $responseJson;
                }
                $id = array_map([$this, 'cleanWhitespace'], $id[0]);

                $wert = array($_POST['wert']);
                $wert = array_map([$this, 'cleanWhitespace'], $wert[0]);

                $value = array($_POST['value']);
                $value = array_map([$this, 'cleanWhitespace'], $value[0]);


                isset($_POST['aktiv']) ? $aktiv = array($_POST['aktiv']) : $aktiv = [];
                if ($aktiv) {
                    $aktiv = array_map([$this, 'cleanWhitespace'], $aktiv[0]);
                }

                $arr = [];
                for ($i = 0; $i < count($id); $i++) {
                    $w = filter_var($wert[$i], FILTER_SANITIZE_STRING);
                    $v = filter_var($value[$i], FILTER_SANITIZE_STRING);
                    if (!$w) {
                        continue;
                    }
                    $aktiv && isset($aktiv[$id[$i]]) ? $a = 1 : $a = 0;
                    $item = [
                        'name' => $w,
                        'value' =>  str_replace('&#39;',"'", $v),
                        'aktiv' => $a,
                        'id' => (int)$id[$i],
                        'help' => '',
                    ];
                    $arr[] = $item;

                }
                $headers = get_option($this->basename.'-plugin_security_header');

                if (!$headers[$handle] || !$arr) {
                    $responseJson->title = __('Error', 'wp-security-header');
                    $responseJson->msg = __('Ajax transmission error', 'wp-security-header') . ' (Ajx - ' . __LINE__ . ')';
                    return $responseJson;
                }

                $headers[$handle] = $arr;
                update_option($this->basename.'-plugin_security_header', $headers);
                $responseJson->status = true;
                $responseJson->title = __('Saved', 'wp-security-header');
                $responseJson->msg = __('Changes saved successfully.', 'wp-security-header');
                break;
            case'load-default-security-header':
                $headers = $this->wp_security_headers_default_settings('header', get_option($this->basename.'_csp_settings'));
                update_option($this->basename.'-plugin_security_header', $headers);
                $responseJson->status = true;
                $responseJson->msg = __('Settings reset!', 'wp-security-header');
                break;
            case'update_user_role':
                $user_role = filter_input(INPUT_POST, 'user_role', FILTER_SANITIZE_STRING);
                if(!$user_role) {
                    $responseJson->title = __('Error', 'wp-security-header');
                    $responseJson->msg = __('Ajax transmission error', 'wp-security-header') . ' (Ajx - ' . __LINE__ . ')';
                    return $responseJson;
                }

                update_option($this->basename.'_user_role', $user_role);
                filter_input(INPUT_POST, 'google_fonts', FILTER_SANITIZE_STRING) ? $google_fonts = 1 : $google_fonts = 0;
                filter_input(INPUT_POST, 'google_apis', FILTER_SANITIZE_STRING) ? $google_apis = 1 : $google_apis = 0;
                filter_input(INPUT_POST, 'adobe_fonts', FILTER_SANITIZE_STRING) ? $adobe_fonts = 1 : $adobe_fonts = 0;
                filter_input(INPUT_POST, 'csp_aktiv', FILTER_SANITIZE_STRING) ? $csp_aktiv = 1 : $csp_aktiv = 0;

                $s = [
                    'google_fonts' => $google_fonts,
                    'google_apis' => $google_apis,
                    'adobe_fonts' => $adobe_fonts,
                    'csp_aktiv' => $csp_aktiv,
                ];
                update_option($this->basename.'_csp_settings', $s);

                $responseJson->status = true;
                $responseJson->title = __('Saved', 'wp-security-header');
                $responseJson->msg = __('Changes saved successfully.', 'wp-security-header');
                break;

        }
        return $responseJson;
    }

    protected function cleanWhitespace($string): string
    {
        if (!$string) {
            return '';
        }
        $return = trim(preg_replace('/\s+/', ' ', $string));
        $return = html_entity_decode($return, ENT_QUOTES);
        return stripslashes_deep($return);
    }

    private function generateRandomId($passwordlength = 12, $numNonAlpha = 1, $numNumberChars = 4, $useCapitalLetter = true): string
    {
        $numberChars = '123456789';
        //$specialChars = '!$&?*-:.,+@_';
        $specialChars = '!$%&=?*-;.,+~@_';
        $secureChars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz';
        $stack = $secureChars;
        if ($useCapitalLetter) {
            $stack .= strtoupper($secureChars);
        }
        $count = $passwordlength - $numNonAlpha - $numNumberChars;
        $temp = str_shuffle($stack);
        $stack = substr($temp, 0, $count);
        if ($numNonAlpha > 0) {
            $temp = str_shuffle($specialChars);
            $stack .= substr($temp, 0, $numNonAlpha);
        }
        if ($numNumberChars > 0) {
            $temp = str_shuffle($numberChars);
            $stack .= substr($temp, 0, $numNumberChars);
        }

        return str_shuffle($stack);
    }

    private function html_compress_template(string $string): string
    {
        if (!$string) {
            return $string;
        }
        return preg_replace(['/<!--(.*)-->/Uis', "/[[:blank:]]+/"], ['', ' '], str_replace(["\n", "\r", "\t"], '', $string));
    }
}

