<?php

namespace IdeasOnPurpose\CPT;

use IdeasOnPurpose\WP;

class News extends WP\CPT
{
    public function props()
    {
        $this->type = 'news';

        $this->define();
        $this->styles();

        // add_filter("manage_edit-{$this->type}_columns", [$this, 'addColumns']);
        // add_action("manage_{$this->type}_posts_custom_column", [$this, 'renderColumns'], 10, 2);
    }

    public function define()
    {
        $labels = WP\DataModel\Labels::post_type($this->type);

        $this->args = [
            'label' => 'News',
            'labels' => $labels,
            'description' => 'News',
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_rest' => true,
            'rest_base' => 'news',
            'has_archive' => true,
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
            'supports' => [
                'title',
                'editor',
                'thumbnail',
                'excerpt',
                'revisions',
                'author',
                'custom-fields',
            ],
            'menu_icon' =>
                'data:image/svg+xml;base64,' .
                base64_encode(
                    // font-awesome: newspaper (light)
                    '<svg aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="currentColor" d="M552 64H88c-13.234 0-24 10.767-24 24v8H24c-13.255 0-24 10.745-24 24v280c0 26.51 21.49 48 48 48h504c13.233 0 24-10.767 24-24V88c0-13.233-10.767-24-24-24zM32 400V128h32v272c0 8.822-7.178 16-16 16s-16-7.178-16-16zm512 16H93.258A47.897 47.897 0 0 0 96 400V96h448v320zm-404-96h168c6.627 0 12-5.373 12-12V140c0-6.627-5.373-12-12-12H140c-6.627 0-12 5.373-12 12v168c0 6.627 5.373 12 12 12zm20-160h128v128H160V160zm-32 212v-8c0-6.627 5.373-12 12-12h168c6.627 0 12 5.373 12 12v8c0 6.627-5.373 12-12 12H140c-6.627 0-12-5.373-12-12zm224 0v-8c0-6.627 5.373-12 12-12h136c6.627 0 12 5.373 12 12v8c0 6.627-5.373 12-12 12H364c-6.627 0-12-5.373-12-12zm0-64v-8c0-6.627 5.373-12 12-12h136c6.627 0 12 5.373 12 12v8c0 6.627-5.373 12-12 12H364c-6.627 0-12-5.373-12-12zm0-128v-8c0-6.627 5.373-12 12-12h136c6.627 0 12 5.373 12 12v8c0 6.627-5.373 12-12 12H364c-6.627 0-12-5.373-12-12zm0 64v-8c0-6.627 5.373-12 12-12h136c6.627 0 12 5.373 12 12v8c0 6.627-5.373 12-12 12H364c-6.627 0-12-5.373-12-12z"></path></svg>'
                ),
        ];
    }

    public function addColumns($cols)
    {
        $newCols = [];

        foreach ($cols as $key => $value) {
            $newCols[$key] = $value;
            if ($key === 'cb') {
                // $newCols['example_column'] = 'Example Column';
            }
        }
        return $newCols;
    }

    public function renderColumns($col, $id)
    {
        switch ($col) {
            case 'example_column':
                // echo column content
                break;
        }
    }

    public function styles()
    {
        $this->css = "
        .post-type-$this->type th#example_column {
            width: 25%;
        };";
    }
}
