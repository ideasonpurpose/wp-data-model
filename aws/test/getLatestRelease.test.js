const { handler } = require("../lambda/wp-update-handler/index");
const semver = require("semver");

const body = {
  slug: "njhi-data-model",
  version: "2.1.3",
};

const event = { body: JSON.stringify(body) };

// test("sort latest releases", async () => {
//   expect(await handler(event)).toHaveProperty("statusCode", 200);
// });

test("semver ?s", () => {
  const fileVer = semver.coerce("njhi-data-model_3.2.55.zip");

  expect(semver.gt(fileVer, "2.1.0")).toBe(true);
  expect(semver.gt(fileVer, "6.1.0")).toBe(false);
  expect(semver.gt(fileVer, "0.0.0")).toBe(true);

  expect(semver.coerce("no-versionhere.zip")).toBe(null);
  expect(semver.coerce("v4.3.2").toString()).toBe("4.3.2");
});
