require("dotenv").config({ path: "~/.aws/credentials" });
process.env.AWS_ACCESS_KEY_ID = process.env.aws_access_key_id;
process.env.AWS_SECRET_ACCESS_KEY = process.env.aws_secret_access_key;

const {
  handler,
  getLatestRelease,
} = require("../lambda/wp-update-handler/index");
const semver = require("semver");

const slug = "nrmp-data-model";
const body = {
  slug,
  plugin: `${slug}/main.php`,
  version: "0.0.0",
};

const event = { body: JSON.stringify(body) };

test("aws config?", () => {
  expect(Object.keys(process.env)).toContain("aws_access_key_id");
  expect(Object.keys(process.env)).toContain("AWS_ACCESS_KEY_ID");
});

test("Return from inside subdir", async () => {
  const response = await handler(event);
  expect(response.body).toMatch(new RegExp(`${slug}/${slug}`));
});

test("sort latest releases", async () => {
  // const response = await handler(event);
  const latest = await getLatestRelease({
    Bucket: "ideasonpurpose-wp-updates",
    // Delimiter: "/",
    // Delimiter: "iop",
    // Delimiter: "nrmp-data-model",
    Prefix: "nrmp-data-model",
    // Prefix: "nrmp",
  });
  expect(latest).toHaveProperty("version");
});

test("sort latest releases", async () => {
  // const response = await handler(event);
  const latest = await getLatestRelease({
    Bucket: "ideasonpurpose-wp-updates",
    // Delimiter: "/",
    // Delimiter: "iop",
    // Delimiter: "nrmp-data-model",
    // Prefix: "nrmp-data-model/",
    Prefix: "nrmp-data-model",
  });
  // console.log(latest);
  expect(latest).toHaveProperty("version.version");
  // expect(latest).toHaveProperty("version.version", "0.1.11"); // why was this pinned to 0.1.11
});

test("semver ?s", () => {
  const fileVer = semver.coerce("njhi-data-model_3.2.55.zip");

  expect(semver.gt(fileVer, "2.1.0")).toBe(true);
  expect(semver.gt(fileVer, "6.1.0")).toBe(false);
  expect(semver.gt(fileVer, "0.0.0")).toBe(true);

  expect(semver.coerce("no-versionhere.zip")).toBe(null);
  expect(semver.coerce("v4.3.2").toString()).toBe("4.3.2");
});
