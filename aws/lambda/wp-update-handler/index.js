const AWS = require("aws-sdk");
const s3 = new AWS.S3({ apiVersion: "2006-03-01", signatureVersion: "v4" });

const semver = require("semver");

const Bucket = "ideasonpurpose-wp-updates";
const awsUrl = `https://${Bucket}.s3.us-east-2.amazonaws.com`;
const logoFile = "iop-logo.svg";
const bannerFile = "iop-banner-1544x500.jpg";
const defaultParams = { Bucket, Delimiter: "/" };

/**
 * Version of latest WordPress plugin
 * TODO: need to pull this value from somewhere else
 */
const tested = "5.5.1";

/**
 *
 * @returns {Object} {Key: 'filename_1.2.3.zip', Version: <semver version object>}
 * @param {Object} params parameters for s3
 * @param {Array} allKeys Container for returned keys, used for recursion
 */
const getLatestRelease = async (params, allKeys = []) => {
  const response = await s3.listObjectsV2(params).promise();
  if (response.KeyCount == 0) return false;

  response.Contents.forEach((obj) => allKeys.push(obj));

  if (response.NextContinuationToken) {
    params.ContinuationToken = response.NextContinuationToken;
    await getLatestRelease(params, allKeys); // RECURSIVE CALL
  }

  const latest = allKeys.reduce(
    (latest, { Key, LastModified }) => {
      const version = semver.coerce(Key);
      return version && semver.gt(version, latest.version)
        ? { Key, LastModified, version }
        : latest;
    },
    { version: "0.0.0" }
  );

  return latest;
};

/**
 * GitHub actions outputs a release named like `plugin-name_1.2.3.zip`
 * Those filenames can be coerced to valid semver versions with `semver.coerce`
 *
 * The handler collects all files in the S3 bucket matching the plugin name, then
 * returns the most recent file by semver version.
 *
 * Compares the provided version against the latest released version. if the latest
 * version is greater than the provided version, the latest version is returned. If
 * no version is provided, the latest release is always returned.
 *
 * The event body should contain two arguments, encoded in a JSON object
 */
exports.handler = async (event) => {
  const response = { statusCode: 200 };
  const { slug, plugin, version: currentVersion } = JSON.parse(event.body);
  const params = { ...defaultParams, Prefix: slug };

  const sections = {};
  const pages = ["about", "changelog"];

  for (const page of pages) {
    const content = await s3
      .getObject({ Bucket, Key: `${slug}/${page}.html` })
      .promise()
      .catch((err) => console.log(page, err));
    if (content) {
      sections[page] = content.Body.toString();
    }
  }

  if (!slug || !plugin) {
    response.statusCode = 500;
    response.body = "A plugin or theme name is required.";
  } else if (plugin.indexOf(slug) !== 0) {
    response.statusCode = 500;
    response.body = "The plugin and slug must match.";
  } else {
    const latest = await getLatestRelease(params);
    if (!latest) {
      response.statusCode = 404;
      response.body = `No plugin or theme matched "${slug}"`;
    } else {
      if (semver.gt(latest.version, currentVersion)) {
        response.statusCode = 200; // redundant but the clarity is nice
        response.body = JSON.stringify({
          slug,
          plugin,
          new_version: latest.version.version,
          last_modified: latest.LastModified,
          package: `${awsUrl}/${latest.Key}`,
          tested,
          icons: {
            "1x": `${awsUrl}/${slug}/${logoFile}`,
            svg: `${awsUrl}/${slug}/${logoFile}`,
          },
          banners: {
            high: `${awsUrl}/${slug}/${bannerFile}`,
          },
          compatibility: {},
          sections,
        });
      }
    }
  }

  return response;
};
