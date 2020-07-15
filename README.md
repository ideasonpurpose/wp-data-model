# wp-data-model

#### Version 0.2.7

Base package for building data models for WordPress sites.

## AWS Lambda

The **aws** directory contains the lambda function which handles update requests from the WordPress Admin Plugins page.

## GitHub Actions

The publish-to-aws GitHub Action will build the plugin and push the artifact to our S3 updates bucket. The project needs to define two GitHub Secrets access AWS, those names are found in the `env` section at the top fo the file.

## Example data-model Plugin

To start a new data-model plugin, copy the **example** directory. Create new CPTs and Taxonomies in the **lib** directory. Call them from **main.php**.
