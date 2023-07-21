const fs = require("fs").promises;
const path = require("path");
const CleanCSS = require("clean-css");

const filePath = "./css/main.css"; // Predefined file path

const minifyCss = async (filePath) => {
    const cssFileData = await fs.readFile(filePath, "utf8");

    const minified = new CleanCSS({
        level: { 1: { specialComments: 0 } }
    }).minify(cssFileData);

    if (minified.errors.length || minified.warnings.length) {
        // Something went wrong during the minification
        console.error(
            "Minification errors/warnings: ",
            minified.errors,
            minified.warnings
        );
    }
    // Write minified styles back to the file
    await fs.writeFile(path.resolve(filePath), minified.styles);

    return minified.styles; // If everything went smoothly return minified styles
};

minifyCss(filePath)
        .then(() => console.log(`CSS minification completed for ${filePath}`))
        .catch((error) => console.error(`An error occurred: ${error}`));
