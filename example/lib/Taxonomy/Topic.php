<?php

namespace IdeasOnPurpose\Taxonomy;

use IdeasOnPurpose\WP;

class Topic extends WP\Taxonomy
{
    public function props()
    {
        $this->slug = 'topic';
        $this->define();
        $this->styles();
    }

    public function define()
    {
        $labels = WP\DataModel\Labels::taxonomy('topic');

        $this->args = [
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
    }

    public function styles()
    {
        $this->css = "
        th#taxonomy-$this->slug {
            width: 12%;
        }";
    }
}
