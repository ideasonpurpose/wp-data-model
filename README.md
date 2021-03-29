# wp-data-model

#### Version 0.4.3

Base package for building data models for WordPress sites.

## AWS Lambda

The **aws** directory contains the lambda function which handles update requests from the WordPress Admin Plugins page. The function should be deployed manually through the AWS Console. Use this link to switch to the correct role: [AWS Login](https://signin.aws.amazon.com/switchrole?roleName=OrganizationAccountAccessRole&account=iop003&displayName=IOP&color=B7CA9D)

## GitHub Actions

The publish-to-aws GitHub Action will build the plugin and push the artifact to our S3 updates bucket. The project needs to define two GitHub Secrets for accessing AWS, these names are found in the `env` section at the top of the [example/.github/workflows/publish-to-aws.yml](https://github.com/ideasonpurpose/wp-data-model/blob/master/example/.github/workflows/publish-to-aws.yml#L9-L10) file:

```yaml
env:
  S3_URI: s3://ideasonpurpose-wp-updates
  AWS_ACCESS_KEY_ID: ${{ secrets.AWS_ACCESS_KEY_ID }}
  AWS_SECRET_ACCESS_KEY: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
```

## Example data-model Plugin

To start a new data-model plugin, copy the **example** directory. Create new CPTs and Taxonomies in the **lib** directory, then instantiate them from **main.php**. Rename built-in Post Types and Taxonomies by updating the corresponding files in **lib/Rename**.

### WordPress Compatibility

<!-- TODO: this should happen at build time, not deploy -->

The value of `tested` is generated on deploy by the GitHub action. It is assumed that whatever
the latest version as reported by the WordPress API will be what the plugin was tested against.

## Testing plugin updates

Our [WordPress Updates AWS endpoint][wp-update] can be tested by sending a POST request with a raw JSON body which looks something like this:

```json
{
  "version": "0.0.1",
  "slug": "gip-data-model",
  "plugin": "gip-data-model/main.php"
}
```

### Taxonomy Maps and post_type assignment

Post_types are attached to taxonomies using an array map where each key is a single taxonomy and each value is an array of post_types to which the taxonomy will be attached. An example looks like this:

```php
$this->taxonomyMap = [
  'category' => ['post', 'policy', 'help'],
  'audience' => ['post', 'help', 'event'],
  'fellowship' => ['event'],
];
```

### Renaming Built-in Post Types and Taxonomies

Built-in post_types and taxonomies can be easily renamed. The DataModel object adds a static function to the WP namespace which can be called like this:

```php
WP\Rename::post('topic'); // rename Posts to Topics
WP\Rename::category('colors'); // Rename Categories to Colors
WP\Rename::tag('flavors', ['popular_items' => 'Most delicious flavors']); // Rename Tags to Flavors with and override label
```

DataModel will normalize singular and plural terms and capitalization to match WordPress best practices. For non-standard uses, supply override labels.

`tag` is an alias for `post_tag`, both can be used.

#### Yuck, why a static call?

Using a static call for renames was a deliberate choice. A non-static alternative was prototyped but unlike creating a new CPT or Taxonomy, renaming does not create anything new, so the `new` invocation didn't make sense, even though it paralleled the existing syntax.

The `new CPT` and new Taxonomy` syntax make sense because those commands are _creating_ something new. For renaming, the command acts on something which already exists.

The syntax for renaming can often be achieved in a single line whereas creating new CPTs or Taxonomies usually require defining additional actions and filters.

[wp-update]: https://1q32dgotuh.execute-api.us-east-2.amazonaws.com/production
