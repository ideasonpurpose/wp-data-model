const archiver = require("archiver");
const fs = require("fs-extra");
const filesize = require("filesize");
const prettyHrtime = require("pretty-hrtime");

const start = process.hrtime();

const outFile = "aws/_build/wp-update-handler.zip";
fs.ensureFileSync(outFile);
const outStream = fs.createWriteStream(outFile);

let lastCount = 0;

outStream.on("close", async () => {
  const size = filesize(archive.pointer());
  const time = prettyHrtime(process.hrtime(start));
  console.log(`
   AWS Lambda source & assets zipped to ${outFile}
   ${lastCount} files totaling ${size}, processed in ${time}.`);
});

const archive = archiver("zip", { zlib: { level: 3 } });
archive.pipe(outStream);

archive.on("progress", (stats) => {
  lastCount = stats.entries.total;
  process.stdout.write(`Found ${lastCount} files...\r`);
});

archive.on("error", (err) => {
  console.log(err);
  throw err;
});

// archive.file('aws/lambda/wp-update-handler/package.json');

archive.glob("**/*", {
  cwd: "aws/lambda/wp-update-handler",
  ignore: ["**/*.map", "**/tsconfig.tsbuildinfo"],
  nodir: true,
});

// console.log(archive);

archive.finalize();
