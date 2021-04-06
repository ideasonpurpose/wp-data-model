# wp-data-model

#### Version 0.4.3

Base package for building data model plugins for WordPress sites at [Ideas On Purpose](https://www.ideasonpurpose.com).

## Example data-model Plugin

To start a new data-model plugin, copy the **example** directory. Create new CPTs and Taxonomies in the **lib** directory, then instantiate them from **main.php**. Connect Taxonomies to Post_Types with a `taxonomyMap` and rename built-in Post_Types and Taxonomies with static calls to `WP\Rename`.

### Create Custom Post Types and Taxonomies

New Custom Post_Types are created as PHP classes which extend `IdeasOnPurpose\WP\CPT`, new Taxonomies extend `IdeasOnPurpose\WP\Taxonomy`. All new classes should include a `props` method which defines `$this->args`.

### Taxonomy Maps and post_type assignment

The **main.php** file connects post_types to taxonomies using an array-map where each key is a single taxonomy and each value is an array of post_types to attach to. An example looks like this:

```php
// main.php
$this->taxonomyMap = [
  "category" => ["post", "policy", "help"],
  "audience" => ["post", "help", "event"],
  "fellowship" => ["event"],
];
```

### Admin CSS

New Post_Types and Taxonomies can add specific CSS Rules to the WordPress admin by defining a `$css` property.

### Generating Labels

A default set of labels can be generated from `WP\DataModel::postTypeLabels()` and `WP\DataModel::taxonomyLabels()`. These are often used to populate the `labels` value of the `$args` property when defining a new Post_Type or Taxonomy . Arguments are:

- **`$labelBase`** _String_
  The base name of the label. This will be inflected and case-corrected to match WordPress defaults.
- **`$inflect`** _[Boolean]_, default `true`<br>
  A boolean switch to enable singular/plural inflection of `$labelBase`.
- **`$overrides`** _[Array]_, default: `[]` <br>
  An array of labels which will override the generated default labels.

### WordPress Compatibility

WordPress reports a `tested` value describing the latest version the plugin was developed and tested on. This value is auto-generated before the package.json **version** script runs. Tested values are collected from the [WordPress Stable-Check API](http://api.wordpress.org/core/stable-check/1.0) and stored in **assets/tested.json**.

### Renaming Built-in Post Types and Taxonomies

Built-in post_types and taxonomies can be easily renamed. The DataModel object adds a static function to the WP namespace which can be called like this:

```php
WP\Rename::post("topic"); // rename Posts to Topics
WP\Rename::category("colors"); // Rename Categories to Colors
WP\Rename::tag("flavors", ["popular_items" => "Most delicious flavors"]); // Rename Tags to Flavors with and override label
```

DataModel will normalize singular and plural terms and capitalization to match WordPress best practices. For non-standard uses, supply override labels.

`tag` is an alias for `post_tag`, both can be used.

#### Yuck, why a static call?

Using a static call for renames was a deliberate choice. Unlike creating a new CPT or Taxonomy, renaming does not create anything new, so a `new` invocation doesn't make sense, even though it would parallel existing syntax.

The `new CPT` and `new Taxonomy` syntax make sense because those commands are _creating_ something new. For renaming, the command acts on something which already exists, so the invocation syntax would be inconsistent with the performed action.

The syntax for renaming can often be achieved in a single line whereas creating new CPTs or Taxonomies usually require defining additional actions and filters.

### Composer Updates

The docker-compose Composer service will mount and use local auth credentials if they exist in **~/.composer/auth.json**. If those credentials don't exist and Composer hits an API rate limit, pasting a token will create a new auth.json file in the mount which with persist on the host system.

## Automatic plugin updates and AWS

Whenever a new version is pushed, a GitHub Action runs which compiles and packages the project, then pushes the versioned asset to one of our AWS S3 buckets. A lambda microservice handles queries from the plugin and enables native WordPress plugin updates for new versions.

### AWS Lambda

The **aws** directory contains the lambda function which handles update requests from the WordPress Admin Plugins page. The function should be deployed manually through the AWS Console. Use this link to switch to the correct role: [AWS Login](https://signin.aws.amazon.com/switchrole?roleName=OrganizationAccountAccessRole&account=iop003&displayName=IOP&color=B7CA9D)

### GitHub Actions

The publish-to-aws GitHub Action will build the plugin and push the artifact to our S3 updates bucket. The project needs to define two GitHub Secrets for accessing AWS, these names are found in the `env` section at the top of the [example/.github/workflows/publish-to-aws.yml](https://github.com/ideasonpurpose/wp-data-model/blob/master/example/.github/workflows/publish-to-aws.yml#L9-L10) file:

```yaml
env:
  S3_URI: s3://ideasonpurpose-wp-updates
  AWS_ACCESS_KEY_ID: ${{ secrets.AWS_ACCESS_KEY_ID }}
  AWS_SECRET_ACCESS_KEY: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
```

### Testing plugin updates

Our [WordPress Updates AWS endpoint][wp-update] can be tested by sending a POST request with a raw JSON body which looks something like this:

```json
{
  "version": "0.0.1",
  "slug": "gip-data-model",
  "plugin": "gip-data-model/main.php"
}
```

## Default Post_Type and Taxonomy Labels

Default labels can be extracted from WordPress by dumping the global `$wp_post_types[$type]->labels` and `$wp_taxonomies[$taxonomy]->labels` objects. WordPress defines labels as an Array, but stores them as an object. Posts and Pages overlap cleanly, Tags and Categories include special-cases for hierarchical display.

```php
// Default Page labels
[
  "name" => "Pages",
  "singular_name" => "Page",
  "add_new" => "Add New",
  "add_new_item" => "Add New Page",
  "edit_item" => "Edit Page",
  "new_item" => "New Page",
  "view_item" => "View Page",
  "view_items" => "View Pages",
  "search_items" => "Search Pages",
  "not_found" => "No pages found.",
  "not_found_in_trash" => "No pages found in Trash.",
  "parent_item_colon" => "Parent Page:",
  "all_items" => "All Pages",
  "archives" => "Page Archives",
  "attributes" => "Page Attributes",
  "insert_into_item" => "Insert into page",
  "uploaded_to_this_item" => "Uploaded to this page",
  "featured_image" => "Featured image",
  "set_featured_image" => "Set featured image",
  "remove_featured_image" => "Remove featured image",
  "use_featured_image" => "Use as featured image",
  "filter_items_list" => "Filter pages list",
  "filter_by_date" => "Filter by date",
  "items_list_navigation" => "Pages list navigation",
  "items_list" => "Pages list",
  "item_published" => "Page published.",
  "item_published_privately" => "Page published privately.",
  "item_reverted_to_draft" => "Page reverted to draft.",
  "item_scheduled" => "Page scheduled.",
  "item_updated" => "Page updated.",
  "menu_name" => "Pages",
  "name_admin_bar" => "Page",
];
```

```php
// Default Category labels
[
  "name" => "Categories",
  "singular_name" => "Category",
  "search_items" => "Search Categories",
  "popular_items" => null,
  "all_items" => "All Categories",
  "parent_item" => "Parent Category",
  "parent_item_colon" => "Parent Category:",
  "edit_item" => "Edit Category",
  "view_item" => "View Category",
  "update_item" => "Update Category",
  "add_new_item" => "Add New Category",
  "new_item_name" => "New Category Name",
  "separate_items_with_commas" => null,
  "add_or_remove_items" => null,
  "choose_from_most_used" => null,
  "not_found" => "No categories found.",
  "no_terms" => "No categories",
  "filter_by_item" => "Filter by category",
  "items_list_navigation" => "Categories list navigation",
  "items_list" => "Categories list",
  "most_used" => "Most Used",
  "back_to_items" => "&larr; Go to Categories",
  "menu_name" => "Categories",
  "name_admin_bar" => "category",
];
```

```php
// Default Tag (post_tag) labels
[
  "name" => "Tags",
  "singular_name" => "Tag",
  "search_items" => "Search Tags",
  "popular_items" => "Popular Tags",
  "all_items" => "All Tags",
  "parent_item" => null,
  "parent_item_colon" => null,
  "edit_item" => "Edit Tag",
  "view_item" => "View Tag",
  "update_item" => "Update Tag",
  "add_new_item" => "Add New Tag",
  "new_item_name" => "New Tag Name",
  "separate_items_with_commas" => "Separate tags with commas",
  "add_or_remove_items" => "Add or remove tags",
  "choose_from_most_used" => "Choose from the most used tags",
  "not_found" => "No tags found.",
  "no_terms" => "No tags",
  "filter_by_item" => null,
  "items_list_navigation" => "Tags list navigation",
  "items_list" => "Tags list",
  "most_used" => "Most Used",
  "back_to_items" => "&larr; Go to Tags",
  "menu_name" => "Tags",
  "name_admin_bar" => "post_tag",
];
```

[wp-update]: https://1q32dgotuh.execute-api.us-east-2.amazonaws.com/production
