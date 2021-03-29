<?php

namespace IdeasOnPurpose\WP;

use IdeasOnPurpose\DataModel;

abstract class Rename
{
    /**
     * __callStatic is a magic method which renames the invoked thing using the string in the first
     * index of $args. Labels can be individually overridden by supplying an array of overrides
     * as the second argument. An example call which renames 'Pages' to 'Chapters' looks like this:
     *
     *     WP\Rename::page('chapter', ['not_found' => 'Nope, no chapters here.']);
     *
     * Individual labels can be overridden by sending an array in the second argument:
     *
     *     WP\Rename::tag('flavors', ['popular_items' => 'Most delicious flavors']);
     *
     * Unknown types will be ignored. Overrides should be an associative array and can include some
     * or all available labels.
     *
     * @param String $name - The name of the invoked magic method, use this as the thing to rename
     * @param Array $args - An array of additional args. The first item is the name the thing will
     *                      be renamed to.
     */
    public static function __callStatic($name, $args)
    {
        $labelBase = $args[0] ?? null;
        $overrides = $args[1] ?? [];
        if (!$labelBase) {
            return new Error('A new name must be provided when renaming.');
        }
        if (!is_array($overrides)) {
            return new Error('Overrides must be an array.');
        }

        /**
         * Special case for "Tags" being stored as "post_tag". We just re-map it.
         */
        $name = strtolower($name) === 'tag' ? 'post_tag' : $name;

        self::update($name, $labelBase, $overrides);
    }

    /**
     * Generates a set of type-specific labels based on $labelBase, then assigns those labels
     * to the Post_type or Taxonomy $object
     */
    protected static function update($object, $labelBase, $overrides = [])
    {
        global $wp_post_types, $wp_taxonomies;

        /**
         * Assign new labels to native objects
         */
        if (array_key_exists($object, $wp_post_types)) {
            $wp_post_types[$object]->labels = DataModel::postTypeLabels($labelBase, $overrides);
        } elseif (array_key_exists($object, $wp_taxonomies)) {
            $wp_taxonomies[$object]->labels = DataModel::taxonomyLabels($labelBase, $overrides);
        } else {
            new Error("'{$object}' is not a known Post_type or Taxonomy. Unable to rename.");
        }
    }
}
