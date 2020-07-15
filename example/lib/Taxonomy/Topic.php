<?php

namespace IdeasOnPurpose\Taxonomy;

class Topic
{
    public function __construct($types = null)
    {
        $this->slug = 'topic';
        $this->types = $types;

        add_action('admin_enqueue_scripts', [$this, 'adminStyles'], 100);

        add_action('init', [$this, 'register']);
    }

    public function register()
    {
        $labels = [
          'name' => 'Topics',
          'singular_name' => 'Topic',
          'search_items' => 'Search Topics',
          'all_items' => 'All Topics',
          'parent_item' => 'Parent Topic',
          'parent_item_colon' => 'Parent Topic:',
          'edit_item' => 'Edit Topic',
          'view_item' => 'View Topic',
          'update_item' => 'Update Topic',
          'add_new_item' => 'Add Topic',
          'new_item_name' => 'New Topic',
          'separate_with_commas' => 'Comma-delimited list of Topics',
          'add_or_remove_items' => 'Add or remove Topics',
          'choose_from_most_used' => 'Choose from the most used Topics',
          'not_found' => 'No Topics Found',
          'no_terms' => 'No Topics',
          'back_to_items' => 'Back to Topics',
      ];

        $args = [
            'show_admin_column' => true,
            'show_in_menu' => true,
            'show_in_quick_edit' => true,
            'show_ui' => true,
            'hierarchical' => true,
            'labels' => $labels,
            'query_var' => true,
            'show_admin_column' => true,
            'show_in_rest' => true,
            'show_ui' => true,
            'rewrite' => [
                'slug' => $this->slug,
                'with_front' => false,
                'hierarchical' => false,
            ],
        ];

        register_taxonomy($this->slug, $this->types, $args);
    }

    public function adminStyles()
    {
        $css = "
        th#taxonomy-$this->slug {
            width: 12%;
        }";

        wp_add_inline_style('wp-admin', $css);
    }
}
