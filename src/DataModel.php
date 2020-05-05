<?php

namespace Ideasonpurpose\WP;

abstract class DataModel
{
    /**
     * Override `register` and define Taxonomies and Custom Post Types in the child class
     */
    abstract protected function register();

    public function __construct()
    {
        /**
         * Plugins are usually defined with __FILE__ but that won't work inside
         * a parent class, so we use a ReflectionClass with the inherited name to
         * discover the file path of the child class.
         */
        $childRef = new \ReflectionClass(get_class($this));
        $this->childFilePath = $childRef->getFileName();

        /**
         * To be sure any init hooks defined in the plugin are run correctly, the
         * `register` method is called from the ealier `plugins_loaded` hook.
         */
        add_action('plugins_loaded', [$this, 'register']);

        add_filter('pre_set_site_transient_update_plugins', [$this, 'update']);
        add_filter('plugins_api', [$this, 'details'], 10, 3);

        add_action('admin_enqueue_scripts', [$this, 'adminStyles'], 100);

        register_activation_hook($this->childFilePath, [$this, 'activate']);
    }

    public function activate()
    {
        $this->register();
        flush_rewrite_rules();
    }

    /**
     * These `get_plugin_data` and `plugin_basename` functions are not available
     * until late in the init hook.
     */
    public function getInfo()
    {
        $this->plugin_info = get_plugin_data($this->childFilePath);
        $this->plugin_id = plugin_basename($this->childFilePath);
        $this->plugin_slug = dirname($this->plugin_id);

        $this->transient = "ideasonpurpose-update-check_{$this->plugin_id}";
    }

    public function updateCheck()
    {
        $this->getInfo();
        $response = get_transient($this->transient);

        if (WP_DEBUG === true) {
            $response = false;
        }

        if ($response === false) {
            $remote = wp_remote_post('https://1q32dgotuh.execute-api.us-east-2.amazonaws.com/production', [
                'body' => json_encode([
                    'version' => $this->plugin_info['Version'],
                    'slug' => $this->plugin_slug,
                ]),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
                'data_format' => 'body',
            ]);

            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                echo "Something went wrong: $error_message";
            } else {
                /**
                 * WordPress expects $response to be an object with all internal keys being arrays
                 * So, json_decode to an associative array, then cast it to an object later on
                 */
                $response = json_decode($remote['body'], true);
                set_transient($this->transient, $response, 24 * HOUR_IN_SECONDS);
            }
        }
        return (object) $response;
    }

    public function update($transient)
    {
        /**
         * These `get_plugin_data` and `plugin_basename` functions are not available
         * until late in the init hook. The're only used by the update method, so
         * there's no need to move them out of here.
         */
        // $this->plugin_info = get_plugin_data($this->childFilePath);
        // $this->plugin_id = plugin_basename($this->childFilePath);
        // $this->plugin_slug = dirname($this->plugin_id);

        $this->getInfo();
        // $this->transient = "ideasonpurpose-update-check_{$this->plugin_id}";
        // $response = get_transient($this->transient);

        // if (WP_DEBUG === true) {
        //     $response = false;
        // }

        // if ($response === false) {
        //     $remote = wp_remote_post('https://1q32dgotuh.execute-api.us-east-2.amazonaws.com/production', [
        //         'body' => json_encode([
        //             'version' => $this->plugin_info['Version'],
        //             'slug' => $this->plugin_slug,
        //         ]),
        //         'headers' => [
        //             'Content-Type' => 'application/json',
        //             'Accept' => 'application/json',
        //         ],
        //         'timeout' => 10,
        //         'data_format' => 'body',
        //     ]);

        //     if (is_wp_error($response)) {
        //         $error_message = $response->get_error_message();
        //         echo "Something went wrong: $error_message";
        //     } else {
        //         /**
        //          * WordPress expects $response to be an object with all internal keys being arrays
        //          * So, json_decode to an associative array, then cast it to an object later on
        //          */
        //         $response = json_decode($remote['body'], true);
        //         set_transient($this->transient, $response, 24 * HOUR_IN_SECONDS);
        //     }
        // }

        $response = $this->updateCheck();

        if ((array) $response) {
            $transient->response[$this->plugin_id] = (object) $response;
        }

        \Kint::$mode_default = \Kint::MODE_CLI;
        error_log(@d($transient->response));

        \Kint::$mode_default = \Kint::MODE_RICH;

        return $transient;
    }

    public function details($result, $action, $args)
    {
        $this->getInfo();
        $this->response = $this->updateCheck();

        \Kint::$mode_default = \Kint::MODE_CLI;
        error_log(@d($result, $action, $args));

        \Kint::$mode_default = \Kint::MODE_RICH;

        if (empty($args->slug) || $args->slug != $this->plugin_slug) {
            return false;
        }
        //  $response = clone $args;
        $response = new \StdClass();
        $response->slug = $this->plugin_slug;
        $response->name = $this->plugin_info['Name'];
        $response->author = $this->plugin_info['Author'];
        $response->requires = $this->plugin_info['RequiresWP'];
        $response->homepage = $this->plugin_info['PluginURI'];
        $response->tested = $this->response->tested;
        $response->banners = $this->response->banners;
        $response->version = $this->response->new_version;
        $response->last_updated = $this->response->last_modified;
        $response->download_link = $this->response->package;

        $response->sections = [
            'description' => $this->plugin_info['Description'],
            'changelog' => file_get_contents(realpath(dirname($this->childFilePath) . '/CHANGELOG.html')),
            'About' => 'About Page',
        ];

        \Kint::$mode_default = \Kint::MODE_CLI;
        error_log(@d($this->plugin_info, $this->plugin_id, $this->plugin_slug, $result, $action, $args, $response));

        \Kint::$mode_default = \Kint::MODE_RICH;
        return $response;
    }

    /**
     * Workaround for auto-changelog H2 tags being styled as "clear: both" by WP
     */
    public function adminStyles()
    {
        wp_add_inline_style('wp-admin', '.plugin-install-php .section h2 { clear: none }');
    }
}
