# wp-data-model

#### Version 0.4.2

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
