<?php
/**
 * Plugin Name:       example Data Model
 * Plugin URI:        https://www.github.com/ideasonpurpose
 * Description:       Custom Post Types and Taxonomies for the Example website.
 * Version:           0.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Ideas On Purpose
 * Author URI:        https://www.ideasonpurpose.com
 * Text Domain:       plugin-slug
 * License:           TBD
 * License URI:       TBD
 */

namespace IdeasOnPurpose;

require __DIR__ . '/vendor/autoload.php';

class DataModel extends WP\DataModel
{
    public function register()
    {
        /**
         * Custom Taxonomies
         *
         * Each Taxonomy declaration should include a list of post_type slugs which it
         * will be associated with.
         *
         * Taxonomies are listed internally using this order, so define them by importance.
         */
        new Taxonomy\Topic([ 'news']);

        /**
         * Custom Post Types
         *
         * Each declaration can include an integer indicating it's menu_index
         */
        new CPT\News(20);

        /**
         * Assign built-in Tag and Category taxonomies to Custom Post Types
         */
        register_taxonomy_for_object_type('post_tag', 'news');
        register_taxonomy_for_object_type('category', 'news');

        /**
         * Set separators for the WordPress admin
         */
        new WP\Admin\Separators(20, 21);
    }
}

new Example();
