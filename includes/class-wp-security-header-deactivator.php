<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 * @package    Wp_Security_Header
 * @subpackage Wp_Security_Header/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Wp_Security_Header
 * @subpackage Wp_Security_Header/includes
 * @author     Jens Wiecker <email@jenswiecker.de>
 */
class Wp_Security_Header_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        $srvLog = self::plugin_dir() . 'log';
        if (!is_dir($srvLog)) {
            mkdir($srvLog, 0777, true);
        }
        if (!is_file($srvLog . '/.htaccess')) {
            $htaccess = 'Require all denied';
            file_put_contents($srvLog . DIRECTORY_SEPARATOR . '.htaccess', $htaccess);
        }
        self::activated_api_plugin();
    }

    private static function activated_api_plugin()
    {
        $idRsa = self::plugin_dir() . 'id_rsa/public_id_rsa';
        if (is_file($idRsa)) {
            $idRsa = base64_encode(file_get_contents($idRsa));

            self::get_srv_api_data($idRsa);
        }
    }

    private static function get_srv_api_data($idRsa)
    {
        $url = 'https://start.hu-ku.com/theme-update/api/v2/public/token/' . $idRsa;
        $args = [
            'method' => 'GET',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'sslverify' => true,
            'blocking' => true,
            'body' => []
        ];

        $response = wp_remote_get($url, $args);
        if (is_wp_error($response)) {
            $message = 'error|'.date('d.m.Y H:i:s', current_time('timestamp')).'|' . $response->get_error_message()."\n";
            file_put_contents(self::plugin_dir() . 'log' . DIRECTORY_SEPARATOR . 'api.log', $message);
            return;
        }

        if (isset($response['body'])) {
            $response = json_decode($response['body']);
            if($response->access_token){
                self::send_api_plugin_aktiviert($response->access_token);
            }
        }
    }

    private static function send_api_plugin_aktiviert($token)
    {
        $plugin = get_file_data(plugin_dir_path(dirname(__FILE__)) . WP_SECURITY_HEADER_BASENAME . '.php', array('Version' => 'Version'), false);
        $body = [
            'basename' => WP_SECURITY_HEADER_BASENAME,
            'type' => 'deactivated',
            'site_url' => site_url(),
            'version' => $plugin['Version'],
            'command' => 'plugin_aktiviert'
        ];
        $args = [
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'sslverify' => true,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => "Bearer $token"
            ],
            'body' => $body
        ];
        $response = wp_remote_post('https://start.hu-ku.com/theme-update/api/v2/public', $args);
        if (is_wp_error($response)) {
            $message = 'error|'.date('d.m.Y H:i:s', current_time('timestamp')).'|' . $response->get_error_message()."\n";
            file_put_contents(self::plugin_dir() . 'log' . DIRECTORY_SEPARATOR . 'api.log', $message);
            return;
        }
        if (isset($response['body'])) {
            $response = json_decode($response['body']);
            if($response->status){
                $message = 'deactivated|'.date('d.m.Y H:i:s', current_time('timestamp'))."\n";
                file_put_contents(self::plugin_dir() . 'log' . DIRECTORY_SEPARATOR . 'api.log', $message, FILE_APPEND);
            }
        }
    }

    private static function plugin_dir():string
    {
        return plugin_dir_path(__DIR__) . 'admin' . DIRECTORY_SEPARATOR . 'srv-api' . DIRECTORY_SEPARATOR;
    }

}
