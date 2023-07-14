const penthouse = require("penthouse");
const fs = require("fs");
const path = require("path");
const { DOMParser } = require("xmldom");

const criticalCssPath = "./css/critical.css";
const cssDir = "./css";
const criticalCssDir = "./css/critical";
const url = process.env.URL || "http://local.piearsta.lv";

let siteMapLinks = [];

const makeCriticalCss = async () => {
    const response = await fetch(url + "/sitemap.php");
    const xmlString = await response.text();

    const parser = new DOMParser();
    const xmlDoc = parser.parseFromString(xmlString, "text/xml");
    const locElements = xmlDoc.getElementsByTagName("loc");

    for (let i = 0; i < locElements.length; i++) {
        siteMapLinks.push(locElements[i].textContent);
    }

    siteMapLinks = siteMapLinks.filter(
        (link, index, self) => self.indexOf(link) === index
    );

    for (const link of siteMapLinks) {
        await generateCss(link);
    }
};

const penthouseOptions = {
    cssString: () =>
        fs.readFileSync(
            "./css/1dea749d66d0fca1f9c587199de6732c.1689325612.css",
            "utf8"
        )
};

const generateCss = async (url) => {
    console.log(url);
    try {
        const criticalCss = await penthouse({
            url: url,
            cssString: penthouseOptions.cssString()
        });

        // Создание папки, если она не существует
        if (!fs.existsSync(criticalCssDir)) {
            fs.mkdirSync(criticalCssDir, { recursive: true });
        }

        // Извлечение пути из URL
        const { URL } = require("url");
        const siteUrl = new URL(url);

        // разбиваем путь на сегменты
        let pathSegments = siteUrl.pathname.split("/");

        // удаляем первый пустой сегмент, поскольку pathname всегда начинается со слеша
        pathSegments = pathSegments.filter((segment) => segment);

        // создание новой директории с учетом пути из URL
        const newPath = path.join(criticalCssDir, ...pathSegments);

        if (!fs.existsSync(newPath)) {
            fs.mkdirSync(newPath, { recursive: true });
        }

        const outputFilePath = path.join(newPath, `style.css`);
        fs.writeFileSync(outputFilePath, criticalCss);

        console.log(
            `Critical CSS for ${url} has been written to ${outputFilePath}`
        );
    } catch (error) {
        console.error(
            `An error occurred while generating critical CSS for ${url}: `,
            error
        );
    }
};

makeCriticalCss();

