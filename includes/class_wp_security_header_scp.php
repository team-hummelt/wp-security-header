<?php


namespace WPSecurity\Header;

use Wp_Security_Header;
use stdClass;
use WP_Scripts;

defined('ABSPATH') or die();

/**
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Security_Header
 * @subpackage Wp_Security_Header/includes
 */

/**
 *
 *
 * @since      1.0.0
 * @package    Wp_Security_Header
 * @subpackage Wp_Security_Header/includes
 * @author     Jens Wiecker <email@jenswiecker.de>
 */
class WP_Security_Header_SCP
{
    //STATIC INSTANCE
    private static $instance;
    //OPTION TRAIT
    use WpSecurityHeaderTrait;


    /**
     * Store plugin main class to allow admin access.
     *
     * @since    2.0.0
     * @access   private
     * @var Wp_Security_Header $main The main class.
     */
    protected Wp_Security_Header $main;

    /**
     * The ID of this theme.
     *
     * @since    2.0.0
     * @access   private
     * @var      string $basename The ID of this theme.
     */
    protected string $basename;

    /**
     * The version of this theme.
     *
     * @since    2.0.0
     * @access   private
     * @var      string $version The current version of this theme.
     */
    protected string $version;

    /**
     * @return static
     */
    public static function init(string $plugin_name, string $version, Wp_Security_Header $main): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self($plugin_name, $version, $main);
        }

        return self::$instance;
    }

    public function __construct(string $plugin_name, string $version, Wp_Security_Header $main)
    {
        $this->basename = $plugin_name;
        $this->version = $version;
        $this->main = $main;
    }

    public function wp_security_header_get_footer_data()
    {
        add_action('wp_enqueue_scripts', array($this, 'theme_v2_get_footer_file_data'), 999, 2);
    }

    public function theme_v2_get_footer_file_data()
    {
        global $wp_scripts;
        if (!$wp_scripts instanceof WP_Scripts) {
            $wp_scripts = new WP_Scripts();
        }
        $scriptArr = [];
        $scriptLocalize = [];
        $doHeaderJS = $wp_scripts->do_head_items();

        foreach ($wp_scripts->queue as $src) {
            if (in_array($src, $doHeaderJS)) {
                continue;
            }
            $source = $wp_scripts->query($src);
            if (isset($source->extra['data'])) {
                $item = [
                    'script-id' => $src,
                    'script->js' => $source->extra['data']
                ];
                $scriptArr[] = $item;
                //$wp_scripts->remove( $src );
            }
        }

        //add_filter('script_loader_tag',array($this, 'starter_theme_v2_script_tag_nonce',) 10, 3);
        add_filter('wp_inline_script_attributes', array($this, 'wp_security_header_script_tag_nonce'), 10, 2);

    }

    public function wp_security_header_script_tag_nonce($attributes, $handle): array
    {
        $attr = [];
        foreach ($attributes as $tmp) {
            if (isset($tmp['nonce'])) {
                unset ($tmp['nonce']);
            }
            $attr[] = $tmp;
        }
        return $attr;
    }

    public function set_security_header_template_redirect()
    {
        // Collect full page output.
        ob_start(function ($output) {
            $headers = get_option($this->basename.'-plugin_security_header');
            $csp = [];
            $cspScriptNonce = false;
            $cspStyleNonce = false;
            foreach ($headers['csp'] as $tmp) {
                if ($tmp['name'] && $tmp['value'] && $tmp['aktiv']) {
                    $name = htmlspecialchars_decode($tmp['name']);
                    $name = stripslashes_deep($name);
                    $value = html_entity_decode($tmp['value'], ENT_QUOTES);
                    $value = str_replace('&#39;',"'", $value);
                    if ($name == 'script-src') {
                        if (strpos($value, '%s')) {
                            $cspScriptNonce = true;
                        }
                    }
                    if ($tmp['name'] == 'style-src') {
                        if (strpos($value, '%s')) {
                            $cspStyleNonce = true;
                        }
                    }
                    $csp[] = "{$name} {$value}";
                }
            }
            if($csp) {
                $csp = implode('; ', $csp);
            }

            $pr = [];
            foreach ($headers['pr'] as $tmp) {
                if ($tmp['name'] && $tmp['value'] && $tmp['aktiv']) {
                    $name = htmlspecialchars_decode($tmp['name']);
                    $name = stripslashes_deep($name);
                    $value = html_entity_decode($tmp['value'], ENT_QUOTES);
                    $value = str_replace('&#39;',"'", $value);
                    $pr[] = "{$name}={$value}";
                }
            }

            $pr = implode(', ', $pr);
            $nonces = [];
            $regEx = '#<script.*?\>#';
            $output = preg_replace_callback($regEx, function ($matches) use (&$nonces) {
                $nonce = $this->generateRandomId(10,0,7);
                $nonces[] = $nonce;
                return str_replace('<script', "<script nonce='{$nonce}'", $matches[0]);
            }, $output);

            $nonces_csp = array_reduce($nonces, function ($header, $nonce) {
                return "{$header} 'nonce-{$nonce}'";
            }, '');

            if($cspScriptNonce){
                $header = sprintf($csp, $nonces_csp);
            } else {
                $header = $csp;
            }

            $ah = [];
            foreach ($headers['ah'] as $tmp) {
                if ($tmp['name'] && $tmp['value'] && $tmp['aktiv']) {
                    $name = htmlspecialchars_decode($tmp['name']);
                    $value = html_entity_decode($tmp['value'], ENT_QUOTES);
                    $value = str_replace('&#39;',"'", $value);
                    $ah[] = "{$name}: {$value}";
                }
            }

            if($ah) {
                foreach ($ah as $h) {
                    header($h);
                }
            }
            if($pr){
                header("Permissions-Policy: $pr");
            }
            if($header){
                header("Content-Security-Policy: $header");
            }

            return $output;
        });
    }

    public function starter_theme_vs_script_tag_nonce($tag, $handle, $src)
    {
        $t[] = $handle;
        foreach ($t as $a) {
            if ($handle === $a) {
                $nonce = $this->generateRandomId(10,0,7);
                $tag = str_replace('<script ', "<script nonce='$nonce' ", $tag);
            }
            return $tag;
        }
    }

    public function wp_security_header_style_tag_nonce($tag, $handle, $src)
    {
        $t[] = $handle;
        foreach ($t as $a) {
            if ($handle === $a) {
                $nonce = $this->generateRandomId(10,0,7);
                $tag = str_replace('<link ', "<link nonce='$nonce' ", $tag);
            }
            return $tag;
        }
    }

    public function wp_security_header_inline_script_attributes($attributes, $javascript)
    {
        if (!isset($attributes['nonce'])) {
            $nonce = $this->generateRandomId(10,0,7);
            $attributes['nonce'] = $nonce;
        }
        $attributes['source'] = 'inline';

        return $attributes;
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
}