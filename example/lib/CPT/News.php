<?php

namespace IdeasOnPurpose\CPT;

use IdeasOnPurpose\WP;

class News extends WP\CPT
{
    public function __construct()
    {
        $this->type = 'news';
        $this->rewrite_base = $this->type;

        add_action('admin_enqueue_scripts', [$this, 'adminStyles'], 100);

        parent::__construct(...func_get_args());
    }

    public function define()
    {
        $labels = [
            'name' => 'News',
            'singular_name' => 'News',
            'menu_name' => 'News',
            'name_admin_bar' => 'News',
            'archives' => 'Archived News',
            'attributes' => 'News Attributes',
            'parent_item_colon' => 'Parent Item:',
            'all_items' => 'All News Posts',
            'add_new_item' => 'Add News Post',
            'add_new' => 'Add News',
            'new_item' => 'New News',
            'edit_item' => 'Edit News',
            'update_item' => 'Update News',
            'view_item' => 'View News',
            'view_items' => 'View News',
            'search_items' => 'Search News',
            'not_found' => 'No matching News Posts',
            'not_found_in_trash' => 'Not found in Trash',
            'featured_image' => 'Hero Image',
            'set_featured_image' => 'Set Hero Image',
            'remove_featured_image' => 'Remove Hero Image',
            'use_featured_image' => 'Use as Hero Image',
            'insert_into_item' => 'Insert into News',
            'uploaded_to_this_item' => 'Uploaded to this item',
            'items_list' => 'News list',
            'items_list_navigation' => 'News list navigation',
            'filter_items_list' => 'Filter News list',
        ];

        $args = [
            'label' => 'News',
            'labels' => $labels,
            'description' => 'News',
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_rest' => true,
            'rest_base' => 'news',
            'has_archive' => $this->rewrite_base,
            'menu_icon' => 'dashicons-njhi-news',
            'show_in_menu' => true,
            'exclude_from_search' => false,
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'hierarchical' => false,
            'rewrite' => [
                'slug' => 'news',
                'with_front' => false,
            ],
            'query_var' => true,
            'menu_position' => $this->menu_index,
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'author', 'custom-fields'],
            'menu_icon' =>
                'data:image/svg+xml;base64,' .
                base64_encode(
                    // font-awesome: newspaper (light)
                    '<svg aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M552 64H88c-13.234 0-24 10.767-24 24v8H24c-13.255 0-24 10.745-24 24v280c0 26.51 21.49 48 48 48h504c13.233 0 24-10.767 24-24V88c0-13.233-10.767-24-24-24zM32 400V128h32v272c0 8.822-7.178 16-16 16s-16-7.178-16-16zm512 16H93.258A47.897 47.897 0 0 0 96 400V96h448v320zm-404-96h168c6.627 0 12-5.373 12-12V140c0-6.627-5.373-12-12-12H140c-6.627 0-12 5.373-12 12v168c0 6.627 5.373 12 12 12zm20-160h128v128H160V160zm-32 212v-8c0-6.627 5.373-12 12-12h168c6.627 0 12 5.373 12 12v8c0 6.627-5.373 12-12 12H140c-6.627 0-12-5.373-12-12zm224 0v-8c0-6.627 5.373-12 12-12h136c6.627 0 12 5.373 12 12v8c0 6.627-5.373 12-12 12H364c-6.627 0-12-5.373-12-12zm0-64v-8c0-6.627 5.373-12 12-12h136c6.627 0 12 5.373 12 12v8c0 6.627-5.373 12-12 12H364c-6.627 0-12-5.373-12-12zm0-128v-8c0-6.627 5.373-12 12-12h136c6.627 0 12 5.373 12 12v8c0 6.627-5.373 12-12 12H364c-6.627 0-12-5.373-12-12zm0 64v-8c0-6.627 5.373-12 12-12h136c6.627 0 12 5.373 12 12v8c0 6.627-5.373 12-12 12H364c-6.627 0-12-5.373-12-12z"></path></svg>'
                ),
        ];

        register_post_type($this->type, $args);
    }

    public function adminStyles()
    {
        $css = '';

        wp_add_inline_style('wp-admin', $css);
    }
}
