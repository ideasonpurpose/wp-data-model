<?php

namespace IdeasOnPurpose\WP\Plugin;

class Api
{
    public function __construct($plugin = null)
    {
        $this->plugin = $plugin;

        register_activation_hook($this->plugin->__FILE__, [$this, 'activate']);
        register_deactivation_hook($this->plugin->__FILE__, [$this, 'deactivate']);

        add_filter('pre_set_site_transient_update_plugins', [$this, 'update'], 10, 2);
        add_filter('plugins_api', [$this, 'details'], 10, 3);

        add_action('upgrader_process_complete', [$this, 'updaterComplete'], 10, 2);
    }

    public function activate()
    {
        $this->plugin->register();
        flush_rewrite_rules();
    }

    public function deactivate()
    {
        flush_rewrite_rules();
    }

    /**
     * Updates the WordPress transient with a new response
     * containing update info from our AWS Lambda endpoint
     *
     * Called from the `pre_set_site_transient_update_plugins` filter
     *
     * @param object $transient
     * @param string $action
     * @return void $transient, with updated response
     */
    public function update($transient, $action)
    {
        $this->pluginInfo();
        $this->updateCheck();

        /**
         * Casting the Object to an array checks that it's non-empty since [] is false
         * Check that it isn't just false, or casting to array yields `[false]`
         */
        if ($this->response && (array) $this->response) {
            $transient->response[$this->plugin_id] = $this->response;
        }

        return $transient;
    }

    /**
     * Injects details about the plugin into `plugin_information` requests to the plugin_api
     *
     * Called from the `plugins_api` filter
     *
     * @param  boolean $result
     * @param  string $action
     * @param  object $args
     * @return object A response object containing information about the plugin
     */
    public function details($result, $action, $args)
    {
        $this->pluginInfo();
        $this->updateCheck();

        /**
         *  Keeping this conditional after `updateCheck` so we can potentially "warm" the lambda
         */
        if (
            $args->slug !== $this->plugin_slug ||
            $action !== 'plugin_information' ||
            $this->response === false
        ) {
            return $result;
        }

        $response = new \StdClass();
        $response->slug = $this->plugin_slug;

        $response->name = $this->plugin_data['Name'];
        $response->author = $this->plugin_data['Author'];
        $response->requires = $this->plugin_data['RequiresWP'];
        $response->homepage = $this->plugin_data['PluginURI'];

        $response->tested = $this->response->tested;
        $response->banners = $this->response->banners;
        $response->version = $this->response->new_version;
        $response->last_updated = $this->response->last_modified;
        $response->download_link = $this->response->package;
        $response->sections = $this->response->sections;

        $response->sections['description'] = $this->plugin_data['Description'];

        return $response;
    }

    /**
     * Following a successful update, this will clear the transient so the WordPress admin
     * interface stops
     */
    public function updaterComplete($upgrader_object, $options)
    {
        \Kint::$mode_default = \Kint::MODE_CLI;
        error_log(@d('updaterComplete', $options));
        \Kint::$mode_default = \Kint::MODE_RICH;

        // If an update has taken place and the updated type is plugins and the plugins element exists
        if (
            $options['action'] == 'update' &&
            $options['type'] == 'plugin' &&
            isset($options['plugins'])
        ) {
            $this->pluginInfo();
            if (in_array($this->plugin_id, $options['plugins'])) {
                delete_transient($this->transient);
            }
        }
    }

    /**
     * pluginInfo - Gathers and refreshes plugin metadata.
     *
     * The set values look like this:
     *   $this->plugin_data = See https://developer.wordpress.org/reference/functions/get_plugin_data/
     *   $this->plugin_id = "njhi-data-model/main.php"
     *   $this->plugin_slug = "njhi-data-model"
     */
    public function pluginInfo()
    {
        $this->plugin_data = get_plugin_data($this->plugin->__FILE__);
        $this->plugin_id = plugin_basename($this->plugin->__FILE__);
        $this->plugin_slug = dirname($this->plugin_id);
        $this->transient = "ideasonpurpose-update-check_{$this->plugin_id}";
    }

    public function updateCheck()
    {
        error_log('---- updateCheck');
        $this->pluginInfo();
        $this->response = get_transient($this->transient);

        /**
         * Disable transients when WP_DEBUG is true
         */
        if (WP_DEBUG === true) {
            $this->response = false;
        }

        /**
         * Query the lambda to see if we've got an update
         */
        if ($this->response === false) {
            $remote = wp_remote_post(
                'https://1q32dgotuh.execute-api.us-east-2.amazonaws.com/production',
                [
                    'body' => json_encode([
                        'version' => $this->plugin_data['Version'],
                        'slug' => $this->plugin_slug,
                        'plugin' => $this->plugin_id,
                    ]),
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'timeout' => 20,
                    'data_format' => 'body',
                ]
            );

            if (is_wp_error($remote)) {
                error_log('Something went wrong: ' . $remote->get_error_message());
            } elseif ($remote['response']['code'] != 200) {
                error_log("Something went wrong: {$remote['body']}");
            } else {
                /**
                 * WordPress expects $response to be an object with all internal keys
                 * being arrays.
                 * Quick solution: json_decode to an associative array, then cast it
                 * to an object so all top-level keys become properties.
                 */
                $this->response = (object) json_decode($remote['body'], true);

                set_transient($this->transient, $this->response, HOUR_IN_SECONDS);
            }
        }
    }
}