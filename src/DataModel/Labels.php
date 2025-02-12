<?php

namespace IdeasOnPurpose\WP\DataModel;

class Labels
{
    /**
     * Create i18n friendly labels from WP_Post_Type and WP_Taxonomy default_labels
     *
     * There are two primary differentiations: post_type or taxonomy, and hierarchy
     *
     * NOTE: Overrides has been removed, if a label needs ot be overridden, just do it after
     * creating the object.
     */
    public static function labels($singular, $plural, $is_post, $is_hierarchical = true)
    {
        // TODO: Should support 'post_type" or "taxonomy" as well.
        //          check for Boolean, then string match?
        // It should accept the following: post_type, post, page, taxonomy, category, tag
        // post, page, category, and tag should set $is_post and $is_hierarchical
        if (in_array($is_post, ['post', 'page', 'category', 'tag', 'post_type', 'taxonomy'])) {
            $is_hierarchical = in_array($is_post, ['page', 'category']);
            $is_post = in_array($is_post, ['post', 'page', 'post_type']);
        }

        $default_labels = $is_post
            ? \WP_Post_Type::get_default_labels()
            : \WP_Taxonomy::get_default_labels();

        $labels = new \stdClass();
        foreach ($default_labels as $key => $value) {
            if ($value[intval($is_hierarchical)]) {
                $labels->$key = $value[intval($is_hierarchical)];
            }
        }

        /**
         * The menu_name label is a special case which is added after default menus are retrieved
         * @link https://github.com/WordPress/wordpress-develop/blob/ac2eeb9868d995f5632fcfdc40e8b36e22724ba7/src/wp-includes/post.php#L2102
         * @link https://github.com/WordPress/wordpress-develop/blob/ac2eeb9868d995f5632fcfdc40e8b36e22724ba7/src/wp-includes/taxonomy.php#L720
         */
        $labels->menu_name = $labels->name;

        $newLabels = self::updateLabels($singular, $plural, $labels);

        return $newLabels;
    }

    /**
     * A copy of updateLabels which requires a singular and plural label definition
     * so we can remove the inflector dependency.
     *
     * Note: strtolower and ucwords have no effect on Japanese text
     *
     * Note: WordPress is inconsistent about whether the labels property of the
     * register_taxonomy and register_post_type functions should be an object
     * or an array. But the value always gets cast to an array anyway, so just
     * send an array.
     * @link https://github.com/WordPress/wordpress-develop/blob/8711aa5ab60e35d78dab73e316674b59b90246da/src/wp-includes/taxonomy.php#L707
     *
     * @param array $labelBase
     * @param mixed $labels
     * @return array
     */
    public static function updateLabels($_singular, $_plural, $_labels)
    {
        $labels = (object) $_labels;

        $singularSrc = strtolower($labels->singular_name);
        $singularSrcTitleCase = ucwords($singularSrc);
        $pluralSrc = strtolower($labels->name);
        $pluralSrcTitleCase = ucwords($pluralSrc);

        $singular = strtolower($_singular);
        $singularTitleCase = ucwords($singular);
        $plural = strtolower($_plural);
        $pluralTitleCase = ucwords($plural);

        $patterns = [
            preg_quote($singularSrc, '/'),
            preg_quote($singularSrcTitleCase, '/'),
            preg_quote($pluralSrc, '/'),
            preg_quote($pluralSrcTitleCase, '/'),
        ];

        /**
         * Check for Japanese characters
         */
        if (!preg_match('/[\p{Katakana}\p{Hiragana}\p{Han}]+/u', $labels->name)) {
            /**
             * Wrap non-japanese regex patterns in \b word boundary delimiters
             */
            $patterns = array_map(fn($p) => '\b' . $p . '\b', $patterns);
        }
        /**
         * Wrap regex patterns in /.../u
         */
        $patterns = array_map(fn($p) => "/{$p}/u", $patterns);

        $replacements = [$singular, $singularTitleCase, $plural, $pluralTitleCase];

        $newLabels = new \stdClass();
        $labels = (array) $labels;
        foreach ($labels as $key => $value) {
            $newLabels->$key = preg_replace($patterns, $replacements, $value);
        }

        return $newLabels;
    }

    /**
     * Return a set of Post_type Labels
     * Defaults to Page-like hierarchical labels.
     * Set $hierarchical = false to create post-like labels.
     */
    public static function post_type($singular, $plural, $hierarchical = true)
    {
        return self::labels($singular, $plural, true, $hierarchical);
    }

    /**
     * Return a set of Taxonomy Labels
     * Defaults to Category-like hierarchical labels.
     * Set $hierarchical = false to create tag-like labels.
     */
    public static function taxonomy($singular, $plural, $hierarchical = true)
    {
        return self::labels($singular, $plural, false, $hierarchical);
    }
}
