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
         * Each Taxonomy may be declared with a list of post_type slugs to be
         * associated with.
         *
         * Taxonomies will appear in declaration order, so define them by importance.
         */
        new Taxonomy\Topic();

        /**
         * Custom Post Types
         *
         * Each declaration can include an integer indicating it's menu_index
         */
        new CPT\News(20);

        /**
         * Remove taxonomies from post_types
         *
         * @link https://developer.wordpress.org/reference/functions/unregister_taxonomy_for_object_type/
         */
        unregister_taxonomy_for_object_type('category', 'post');

        /**
         * Rename built-in Posts, Tags and Categories
         */
        new Rename\Post();
        new Rename\Category();
        new Rename\Tag();

        /**
         * Attach post_types to taxonomies
         *
         * Using Taxonomy slugs as keys, assign an array of post_type slugs
         * Both custom and built-in Taxonomies and post_types will work.
         * Undefined taxonomies or post_types will pass through with no effect.
         *
         * Note: The slugs for built-in taxonomies are: 'post_tag' and 'category'
         */
        $this->taxonomyMap = [
            'post_tag' => ['news'],
            'topic' => ['post', 'news'],
            'undefined_tax' => ['undefined_post_type', 'post'],
        ];

        /**
         * Set separators for the WordPress admin. Separators will be added after any matching menu_indexes.
         */
        new WP\Admin\Separators(20, 21);
    }
}

new DataModel();
