const penthouse = require("penthouse");
const fs = require("fs").promises;
const path = require("path");
require('dotenv').config();

const cssDir = "./css"; // Defining the directory where CSS files will be stored

const url = process.env.URL || "http://local.piearsta.lv"; // Setting the URL to be used for generating critical CSS

const links = [
    "/lv", 
    "/lv/arstu-katalogs", 
    "/lv/ka-lietot",
    "/lv/iestazu-katalogs",
]; // Defining an array of URLs for which critical CSS needs to be generated

const generateFileName = (url) => {
    const parts = url.split("/");
    const lastPart = parts[parts.length - 1];

    if (lastPart === "") {
        return "";
    }

    if (/^(ru|en|lv)$/.test(lastPart)) {
        // If the last part of URL is "ru", "lv" or "en" - define default critical css file name
        return "default.css";
    } 
    
    // Define critical css file name based on last part of URI
    return `${lastPart.substring(lastPart.lastIndexOf("/") + 1)}.css`; 
};

const getFilePath = (fileName) => {
    return path.join(cssDir, fileName);
};

const generateCss = async (url, cssString) => {
    try {
        const fileName = generateFileName(url);
        const cssPath = getFilePath(fileName); // Setting the path for the CSS file

        const criticalCss = await penthouse({
            url: url,
            cssString: cssString, 
        }); // Generating the critical CSS using Penthouse

        await fs.writeFile(cssPath, criticalCss); // Writing the critical CSS to a file

        console.log(
            `New critical CSS for ${url} has been written to ${cssPath}`
        );
    } catch (error) {
        console.error(
            `An error occurred while generating critical CSS for ${url}: `,
            error
        );
    }
};

const getMostRecentCssFile = async () => {
    let newestFile = "";

    const files = await fs.readdir(cssDir); // Reading all the files in the CSS directory

    for (const file of files) {
        const filePath = path.join(cssDir, file);
        const fileStat = await fs.stat(filePath);

        if (fileStat.isFile() && (!newestFile || fileStat.mtimeMs > (await fs.stat(newestFile)).mtimeMs)) {
            // Checking if the current file is newer than the previous newest file
            newestFile = filePath;
        }
    }

    console.log(`Used as base file: ${newestFile}`);

    return newestFile; // Returning the path of the most recent CSS file
};

const deleteOldFiles = async () => {
    const oldFiles = links.map((link) => {
        const fileName = generateFileName(url + link);
        return getFilePath(fileName);
    }); // Generating an array of old CSS files that will be deleted

    const files = await fs.readdir(cssDir); // Reading all the files in the CSS directory

    // Preparing old files to deleting
    const filesToDelete = files.filter((file) =>
        oldFiles.includes(path.join(cssDir, file))
    );

    // Deleting all old CSS files
    
    await Promise.all(
        filesToDelete.map((file) => fs.unlink(path.join(cssDir, file)))
    );

    console.log(`Directory ${cssDir} cleaned`);
};

const penthouseOptions = {
    cssString: async () => await fs.readFile(await getMostRecentCssFile(), "utf8")
}; // Setting the options for Penthouse, including reading the most recent CSS file

const makeCriticalCss = async () => {
    await deleteOldFiles(); // Cleaning the CSS directory before generating new critical CSS 
    const cssString = await penthouseOptions.cssString();
    await Promise.all(
        links.map((link) => generateCss(url + link, cssString))
    ); // Generating critical CSS for all the URLs in the links array
};

makeCriticalCss(); // Calling the function to generate critical CSS
