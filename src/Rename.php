<?php

namespace IdeasOnPurpose\WP;

use IdeasOnPurpose\DataModel;

abstract class Rename
{
    /**
     * __callStatic is a magic method which renames the invoked thing using a string in the first
     * index of $args. Built-in types must be referenced by a known short name, eg. 'post', 'page',
     * 'category', 'tag', etc. Tags can be either 'tag' or 'post_tag'.
     *
     * A simple example call which renames 'Pages' to 'Chapters' looks like this:
     *
     *     WP\Rename::page('chapter');
     *
     * Set the second argument to false to disable singular/plural inflection. Renaming 'Categories'
     * to 'Class' would look like this:
     *
     *     WP\Rename::category('class', false);
     *
     * Individual labels can be overridden by sending an array in the third argument:
     *
     *     WP\Rename::tag('flavors', true, ['popular_items' => 'Most delicious flavors']);
     *
     * Unknown types will be ignored. Overrides should be an associative array and can include some
     * or all available labels.
     *
     * @param String $name - The name of the invoked magic method, use this as the thing to rename
     * @param Array $args - An array of additional args. Breaks down like this:
     *                      [ $labelBase, $inflect, $overrides ]
     */
    public static function __callStatic($name, $args)
    {
        $labelBase = $args[0] ?? null;
        $inflect = !!($args[1] ?? true);
        $overrides = $args[2] ?? [];

        if (!$labelBase) {
            return new Error('A new name must be provided when renaming.');
        }
        if (!is_array($overrides)) {
            return new Error('Overrides must be an array.');
        }

        /**
         * This is a Special case for "Tags" since WordPress stores them as "post_tag". We just re-map it.
         */
        $name = strtolower($name) === 'tag' ? 'post_tag' : $name;

        self::update($name, $labelBase, $inflect, $overrides);
    }

    /**
     * Generates a set of type-specific labels based on $labelBase, then assigns those labels
     * to the Post_type or Taxonomy $object
     */
    protected static function update($object, $labelBase, $inflect = true, $overrides = [])
    {
        global $wp_post_types, $wp_taxonomies;

        /**
         * Assign new labels to native objects
         */
        if (array_key_exists($object, $wp_post_types)) {
            $wp_post_types[$object]->labels = DataModel::postTypeLabels(
                $labelBase,
                $inflect,
                $overrides
            );
        } elseif (array_key_exists($object, $wp_taxonomies)) {
            $wp_taxonomies[$object]->labels = DataModel::taxonomyLabels(
                $labelBase,
                $inflect,
                $overrides
            );
        } else {
            new Error("'{$object}' is not a known Post_type or Taxonomy. Unable to rename.");
        }
    }
}
