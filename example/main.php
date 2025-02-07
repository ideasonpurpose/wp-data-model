<?php
/**
 * Plugin Name:       Example Data Model
 * Plugin URI:        https://www.github.com/ideasonpurpose
 * Description:       Custom Post Types and Taxonomies for the Example website.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      8.2
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
         * Taxonomies will appear in declaration order, so define them by importance.
         *
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
        WP\Rename::post('article', 'articles', ['not_found' => 'Nope, no articles here.']);
        WP\Rename::tag('color', 'colors');
        WP\Rename::category('topic', 'topics');

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
         * Add taxonomy filters to post_type admin screens
         *
         * This can be a duplicate of the $this->taxonomyMap, or an array using the
         * same taxonomy-key to post_types-array values.
         *
         *    [
         *        'taxonomy_slug' => ['post_type_slug', 'post_type_slug']
         *    ];
         */
        $this->taxonomyFilterMap = [
            'post_tag' => ['news'],
            'topic' => ['post', 'news'],
        ];

        /**
         * Set separators for the WordPress admin. Separators will be added after any matching menu_indexes.
         */
        new WP\Admin\Separators(20, 21);
    }
}

new DataModel();
