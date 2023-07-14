const compress_images = require('compress-images');
const fs = require('fs');
const path = require('path');

const srcDir = 'img/';
const destDir = 'img_source/'

function moveFilesAndFolders(sourcePath, destinationPath, callback) {
    if (!fs.existsSync(destinationPath)) {
      fs.mkdirSync(destinationPath);
    }
  
    const filesAndFolders = fs.readdirSync(sourcePath);
  
    filesAndFolders.forEach(fileOrFolder => {
      const source = path.join(sourcePath, fileOrFolder);
      const destination = path.join(destinationPath, fileOrFolder);
      const stats = fs.statSync(source);
  
      if (stats.isFile()) {
        if (!fs.existsSync(destination)) {
          fs.copyFileSync(source, destination);
          fs.unlinkSync(source); // Delete the source file after copying
        }
      } else if (stats.isDirectory()) {
        moveFilesAndFolders(source, destination, callback);
        fs.rmdirSync(source); // Delete the source directory after recursively moving its contents
      }
    });
  
    callback(null); // Call the callback once all files and folders are moved and deleted
  }

moveFilesAndFolders(srcDir, destDir, (err) => {
  if (err) {
    console.error('An error occurred:', err);
  } else {
    console.log('All files and folders moved successfully!');
  }
});

const INPUT_path = "img_source/**/*.{jpg,JPG,jpeg,JPEG,png,svg,gif}";
const OUTPUT_path = "img/";

compress_images(INPUT_path, OUTPUT_path, { compress_force: false, statistic: true, autoupdate: true }, false,
    { jpg: { engine: "mozjpeg", command: ["-quality", "60"] } },
    { png: { engine: "pngquant", command: ["--quality=20-50", "-o"] } },
    { svg: { engine: "svgo", command: "--multipass" } },
    { gif: { engine: "gifsicle", command: ["--colors", "64", "--use-col=web"] } },
    function (error, completed, statistic) {
        console.log("-------------");
        console.log(error);
        console.log(completed);
        console.log(statistic);
        console.log("-------------");
    }
);