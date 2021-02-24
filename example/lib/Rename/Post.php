<?php

namespace IdeasOnPurpose\Rename;

use IdeasOnPurpose\WP;

class Post extends WP\Rename
{
    public function rename()
    {
        /**
         * Post labels will be updated with terms from this array
         * The `post` slug will not be updated
         */
        $this->labels = [
            'FAKE' => 'nope',
            'name' => 'Posts',
            // 'singular_name' => 'Post',
            // 'add_new' => 'Add New',
            // 'add_new_item' => 'Add New Post',
            // 'edit_item' => 'Edit Post',
            // 'new_item' => 'New Post',
            // 'view_item' => 'View Post',
            // 'view_items' => 'View Posts',
            // 'search_items' => 'Search Posts',
            // 'not_found' => 'No posts found.',
            // 'not_found_in_trash' => 'No posts found in Trash.',
            // 'parent_item_colon' => null,
            // 'all_items' => 'All Post',
            // 'archives' => 'Post Archives',
            // 'attributes' => 'Post Attributes',
            // 'insert_into_item' => 'Insert into post',
            // 'uploaded_to_this_item' => 'Uploaded to this post',
            // 'featured_image' => 'Featured image',
            // 'set_featured_image' => 'Set featured image',
            // 'remove_featured_image' => 'Remove featured image',
            // 'use_featured_image' => 'Use as featured image',
            // 'filter_items_list' => 'Filter posts list',
            // 'items_list_navigation' => 'Posts list navigation',
            // 'items_list' => 'Posts list',
            // 'item_published' => 'Post published.',
            // 'item_published_privately' => 'Post published privately.',
            // 'item_reverted_to_draft' => 'Post reverted to draft.',
            // 'item_scheduled' => 'Post scheduled.',
            // 'item_updated' => 'Post updated.',
            // 'menu_name' => 'Posts',
            // 'name_admin_bar' => 'Post',
        ];
    }
}
