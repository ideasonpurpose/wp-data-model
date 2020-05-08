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

        add_filter('pre_set_site_transient_update_plugins', [$this, 'update'], 10, 2);
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
     *
     * The set values look like this:
     *   $this->plugin_info = See https://developer.wordpress.org/reference/functions/get_plugin_data/
     *   $this->plugin_id = "njhi-data-model/main.php"
     *   $this->plugin_slug = "njhi-data-model"
     *
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
            $remote = wp_remote_post('https://1q32dgotuh.execute-api.us-east-2.amazonaws.com/production', [
                'body' => json_encode([
                    'version' => $this->plugin_info['Version'],
                    'slug' => $this->plugin_slug,
                    'plugin' => $this->plugin_id,
                ]),
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'timeout' => 10,
                'data_format' => 'body',
            ]);

            if (is_wp_error($remote)) {
                $error_message = $remote->get_error_message();
                error_log("Something went wrong: $error_message");
            } elseif ($remote->statusCode != 200) {
                error_log("Something went wrong: {$remote->body}");
            } else {
                /**
                 * WordPress expects $response to be an object with all internal keys
                 * being arrays.
                 * Quick solution: json_decode to an associative array, then cast it
                 * to an object so all top-level keys become properties.
                 */
                $this->response = (object) json_decode($remote['body'], true);

                set_transient($this->transient, $this->response, 24 * HOUR_IN_SECONDS);
            }
        }
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
        $this->getInfo();
        $this->updateCheck();

        /**
         * Casting the Object to an array checks that it's non-empty since [] is false
         */
        if ((array) $this->response) {
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
        $this->getInfo();
        $this->updateCheck();

        if ($action !== 'plugin_information') {
            return $result;
        }

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
