<?php

namespace FBApiPlugin\SrvApi\Endpoint;

use stdClass;
use WP_Error;
use Wp_Facebook_Importer;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPFacebook\Importer\WP_Facebook_Importer_Defaults;

/**
 * SRV-API ENDPOINT
 *
 * @link       https://wwdh.de
 * @since      1.0.0
 *
 */


defined('ABSPATH') or die();

class Srv_Api_Endpoint extends WP_REST_Controller
{

    /**
     * Store plugin main class to allow public access.
     *
     * @since    1.0.0
     * @access   private
     * @var Wp_Facebook_Importer $main The main class.
     */
    private Wp_Facebook_Importer $main;

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $basename The ID of this plugin.
     */
    private string $basename;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private string $version;

    /**
     * TRAIT of Default Settings.
     *
     * @since    1.0.0
     */
    use WP_Facebook_Importer_Defaults;

    public function __construct($plugin_name, $plugin_version, Wp_Facebook_Importer $main)
    {
        $this->main = $main;
        $this->basename = $plugin_name;
        $this->version = $plugin_version;

    }

    /**
     * Register the routes for the objects of the controller.
     */
    public function register_routes()
    {

        $version = $this->version;
        $namespace = 'plugin/' . $this->basename . '/v' . $version;
        $base = '/';

        @register_rest_route(
            $namespace,
            $base,
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_registered_items'),
                'permission_callback' => array($this, 'permissions_check')
            )
        );

        @register_rest_route(
            $namespace,
            $base . '(?P<method>[\S^/]+)',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'fb_importer_api_rest_endpoint'),
                'permission_callback' => array($this, 'permissions_check')
            )
        );
        @register_rest_route(
            $namespace,
            $base . '(?P<method>[\S^/]+)',
            array(
                'methods' => 'POST',
                'callback' => array($this, 'fb_importer_api_rest_post_endpoint'),
                'permission_callback' => array($this, 'permissions_check')
            )
        );
    }


    /**
     * Get a collection of items.
     *
     * @param WP_REST_Request $request Full data about the request.
     *
     * @return WP_Error|WP_REST_Response
     */
    public function get_registered_items(WP_REST_Request $request)
    {
        $data = [];

        return rest_ensure_response($data);

    }

    public function fb_importer_api_rest_post_endpoint(WP_REST_Request $request) {
        $data = $this->get_item($request);
        return rest_ensure_response($data);
    }

    /**
     * Get one item from the collection.
     *
     *
     * @return WP_Error|WP_REST_Response
     */


    public function fb_importer_api_rest_endpoint()
    {
        $response = new WP_REST_Response();
        $data = [
            'status' => $response->get_status(200),
            'slug' => $this->basename,
            'version' => $this->version
        ];

        return rest_ensure_response($data);
    }

    public function get_item($request)
    {
        $method = $request->get_param('method');
        $response = new WP_REST_Response();
        global $wpRemoteExec;

        /**
         * Fires after a message is created via the REST API
         *
         * @param object $message Data used to create message
         * @param WP_REST_Request $request Request object.
         * @param bool $bool A boolean that is false.
         */

        switch ($method) {
            case'update-config':
                $body =  $request->get_json_params();
                $makeJob = $wpRemoteExec->make_api_exec_job($method, $body);
                if($makeJob['status'] != 200) {
                    return new WP_Error($makeJob['code'], __($makeJob['msg']), array('status' => $makeJob['status']));
                }
                $response->set_data([
                    'data' => $makeJob
                ]);
                $response = rest_ensure_response($response);
                $response->set_status(200);
                return $response;
            default:
                return new WP_Error('rest_update_failed', __('Method not found.'), array('status' => 404));
        }

    }


    /**
     * Check if a given request has access.
     *
     * @return string
     */
    public function permissions_check(): string
    {
        return '__return_true';
    }


}
