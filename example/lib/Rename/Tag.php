<?php

namespace IdeasOnPurpose\Rename;

use IdeasOnPurpose\WP;

class Tag extends WP\Rename
{
    public function rename()
    {
        /**
         * Tag labels will be updated with terms from this array
         * The `post_tag` slug will not be updated
         */
        $this->labels = [
            'name' => 'Tags',
            'singular_name' => 'Tag',
            'search_items' => 'Search Tags',
            'popular_items' => 'Popular Tags',
            'all_items' => 'All Tags',
            'parent_item' => null,
            'parent_item_colon' => null,
            'edit_item' => 'Edit Tag',
            'view_item' => 'View Tag',
            'update_item' => 'Update Tag',
            'add_new_item' => 'Add New Tag',
            'new_item_name' => 'New Tag Name',
            'separate_items_with_commas' => 'Separate tags with commas',
            'add_or_remove_items' => 'Add or remove tags',
            'choose_from_most_used' => 'Choose from the most used tags',
            'not_found' => 'No tags found.',
            'no_terms' => 'No tags',
            'items_list_navigation' => 'Tags list navigation',
            'items_list' => 'Tags list',
            'most_used' => 'Most Used',
            'back_to_items' => '&larr; Back to Tags',
            'menu_name' => 'Tags',
            'name_admin_bar' => 'post_tag',
        ];
    }
}
