<?php

namespace IdeasOnPurpose\Rename;

use IdeasOnPurpose\WP;

class Category extends WP\Rename
{
    public function rename()
    {
        /**
         * Category labels will be updated with terms from this array
         * The `category` slug will not be updated
         */
        $this->labels = [
            'name' => 'Categories',
            'singular_name' => 'Category',
            'search_items' => 'Search Categories',
            'popular_items' => null,
            'all_items' => 'All Categories',
            'parent_item' => 'Parent Category',
            'parent_item_colon' => 'Parent Category:',
            'edit_item' => 'Edit Category',
            'view_item' => 'View Category',
            'update_item' => 'Update Category',
            'add_new_item' => 'Add New Category',
            'new_item_name' => 'New Category Name',
            'separate_items_with_commas' => null,
            'add_or_remove_items' => null,
            'choose_from_most_used' => null,
            'not_found' => 'No categories found.',
            'no_terms' => 'No categories',
            'items_list_navigation' => 'Categories list navigation',
            'items_list' => 'Categories list',
            'most_used' => 'Most Used',
            'back_to_items' => '&larr; Back to Categories',
            'menu_name' => 'Categories',
            'name_admin_bar' => 'category',
        ];
    }
}
