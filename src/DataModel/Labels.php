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
        $default_labels = $is_post
            ? \WP_Post_Type::get_default_labels()
            : \WP_Taxonomy::get_default_labels();

        $labels = [];
        foreach ($default_labels as $key => $value) {
            if ($value[intval($is_hierarchical)]) {
                $labels[$key] = $value[intval($is_hierarchical)];
            }
        }

        $newLabels = self::updateLabels($singular, $plural, $labels);

        return $newLabels;
    }

    /**
     * A copy of updateLabels which requires a singular and plural label definition
     * so we can remove the inflector dependency.
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
    public static function updateLabels($_singular, $_plural, $labels)
    {
        $singularSrc = strtolower($labels['singular_name']);
        $singularSrcTitleCase = ucwords($singularSrc);
        $pluralSrc = strtolower($labels['name']);
        $pluralSrcTitleCase = ucwords($pluralSrc);

        $singular = strtolower($_singular);
        $singularTitleCase = ucwords($singular);
        $plural = strtolower($_plural);
        $pluralTitleCase = ucwords($plural);

        $patterns = [
            "/\b$singularSrc\b/",
            "/\b$singularSrcTitleCase\b/",
            "/\b$pluralSrc\b/",
            "/\b$pluralSrcTitleCase\b/",
        ];
        $replacements = [$singular, $singularTitleCase, $plural, $pluralTitleCase];

        $newLabels = [];
        foreach ($labels as $key => $value) {
            $newLabels[$key] = preg_replace($patterns, $replacements, $value);
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
