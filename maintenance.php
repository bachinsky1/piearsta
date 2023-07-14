<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:o="urn:schemas-microsoft-com:office:office"
      style="font-family:helvetica, 'helvetica neue', arial, sans-serif">
<head>
    <meta http-equiv="content-type" content="text/html;charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0,user-scalable=0"/>
    <meta content="telephone=no" name="format-detection"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="x-apple-disable-message-reformatting"/>
    <meta charset="UTF-8"/>

    <title id="pageTitle">Uzalbojumi...</title>
    <link rel="icon" href="/favicon.ico">
</head>
<body>
<section class="page-wrap">
    <header class="header">
        <div class="header__content">
            <div class="header__logo-column">
                <a class="logo__item" href="#" title="">
                    <img src="/img/piearsta-logo.png" border="0" alt="Piearsta.lv"></a>
            </div>

            <div class="header_lang lang-open">
                <div class="header_lang-container">
                    <a id="currentLang">Latviski</a>
                </div>
                <div class="menuList">
                    <a onclick="setLang('lv')">Latviski</a>
                    <a onclick="setLang('ru')">Русский</a>
                    <a onclick="setLang('en')">English</a>
                </div>
            </div>
        </div>
    </header>


    <div id="content" class="page-content">
        <div class="img-wrap">
        </div>
        <div id="siteContent">
            <div class="texts-wrap" id="siteContentLv" style="display: block">
                <p class="text-big">Cienījamais lietotāj,</p>
                <h2>šobrīd portāls nav pieejams
                    <span>plānoto uzlabojumu dēļ.</span>
                </h2>
                <p>Uzlabojumus plānots veikt aptuveni 4 stundas. </p>
                <p>Apkopes gaitu Jūs varat apskatīt <a href="#"> šeit</a>. Paldies par sapratni.</p>

            </div>
            <div class="texts-wrap" id="siteContentRu" style="display: none">
                <p class="text-big">дорогой пользователь,</p>
                <h2>портал временно недоступен
                    <span>в связи с плановыми улучшениями.</span>
                </h2>
                <p>Планируется, что улучшения займут около 4 часов.</p>
                <p>Вы можете просмотреть ход обслуживания<a href="#"> здесь</a>. Спасибо за Ваше понимание.</p>
            </div>
            <div class="texts-wrap" id="siteContentEn" style="display: none">
                <p class="text-big">Dear user,</p>
                <h2>currently system is not available
                    <span>due to planned maintenance activities.</span>
                </h2>
                <p>The planned maintenance time takes 4 hours.</p>
                <p>You can view the maintenance progress<a href="#"> here</a>. Thank you for your understanding.</p>
            </div>
        </div>
    </div>
</section>
</body>
<script>

    let langMenuArr = document.querySelectorAll('.header_lang-container');

    langMenuArr.forEach(function (el) {

        el.addEventListener('click', function (e) {

            e.stopImmediatePropagation()

            if (el.classList.contains('open')) {
                el.classList.remove('open')
            } else {
                el.classList.add('open')
            }
        })
    })

    document.addEventListener('click', function (e) {
        langMenuArr.forEach(function (el) {
            el.classList.remove('open')
        })
    })

    function setLang(lang) {
        location.hash = lang;
        location.reload();
    }

    var language = {
        lv: {
            contentText: document.getElementById('siteContentLv'),
            lang: "Latviski",
            title: "Uzlabojumi..."
        },
        ru: {
            contentText: document.getElementById('siteContentRu'),
            lang: "Русский",
            title: "Улучшения..."
        },
        en: {
            contentText: document.getElementById('siteContentEn'),
            lang: "English",
            title: "Maintenance process..."
        }
    };

    if (window.location.hash) {

        let siteContent = document.getElementById('siteContent');
        let currentLang = document.getElementById('currentLang');
        let pageTitle = document.getElementById('pageTitle');

        if (window.location.hash == "#lv") {
            let newLang = language.lv
            siteContent = newLang.contentText
            newLang.contentText.style.display = 'block'
            currentLang.textContent = newLang.lang
            pageTitle.textContent = newLang.title
        } else if (window.location.hash == "#ru") {
            let newLang = language.ru
            siteContent = newLang.contentText;
            newLang.contentText.style.display = 'block'
            language.lv.contentText.style.display = 'none'
            currentLang.textContent = newLang.lang
            pageTitle.textContent = newLang.title
        } else if (window.location.hash == "#en") {
            let newLang = language.en
            siteContent = newLang.contentText;
            newLang.contentText.style.display = 'block'
            language.lv.contentText.style.display = 'none'
            currentLang.textContent = newLang.lang
            pageTitle.textContent = newLang.title
        }
    }

    let languageSwitcher = document.querySelectorAll('.menuList > a');

    languageSwitcher.forEach(function (el) {

        if (el.text === currentLang.textContent) {
            if (el.classList.contains('active')) {
                el.classList.remove('active')
            } else {
                el.classList.add('active')
            }
        }
    })


</script>
<style>
    body, html {
        height: 100%;
        margin: 0;
    }

    body {
        overflow-y: scroll;
        padding: 0;
        margin: 0;
        font-family: "Roboto", Arial;
        -webkit-text-size-adjust: 100%;
        font-size: 17px;
        line-height: 1.5;
        width: 100%;
        height: 100%;
        position: relative;
        overflow: auto;
        -webkit-tap-highlight-color: transparent;
    }

    div, p, th, td {
        font-family: "Roboto", Arial;
    }

    div, p {
        box-sizing: border-box;
    }

    article, aside, footer, header, nav, section {
        display: block;
    }

    a, a:focus, a:active, a:visited {
        text-decoration: none;
        outline: none;
    }

    a {
        background-color: transparent;
        color: #1254af;
    }

    ul, ul li {
        display: block;
        list-style: none;
    }

    p {
        line-height: 1.5;
    }


    h1, h2, h3 {
        font-size: 46px;
        line-height: 1.3;
        font-weight: 600;
        margin: 0 0 33px 0;
    }

    h1 span,
    h2 span,
    h3 span {
        font-weight: 300;
        display: block;
    }

    .text-big {
        font-size: 27px;
        margin-bottom: 17px;
    }


    .page-wrap {
        position: relative;
        margin: 0 auto;
        max-width: 900px;
        height: 100%;
    }


    .header {
        width: calc(100% - 40px);
        margin: 0 auto;
        height: 120px;
        border-bottom: solid 1px #dedede;
        position: relative;
    }

    .header__content {
        height: 100%;
        margin: 0;
        display: flex;
        flex-direction: row;
        flex-wrap: nowrap;
        flex: 0 0 auto;
        justify-content: space-between;
        align-items: center;
        padding: 20px 0;
    }

    .header__logo-column.a-center {
        text-align: center;
        margin: auto;
    }

    .header__logo-column .logo__item img {
        width: 209px;
        height: auto;
    }

    .header__logo-column.a-center .logo__item img {
        width: 260px;
    }

    .header_lang {
        position: relative;
        padding-right: 20px;
        cursor: pointer;
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
    }

    .header_lang::after {
        content: " ";
        cursor: pointer;
        height: 5px;
        width: 5px;
        border: 0;
        border-right: 1px solid #7d8798;
        border-top: 1px solid #7d8798;
        transform: translateY(-10px) rotate(135deg);
        position: absolute;
        top: 17px;
        right: 0;
        margin-top: 0;
    }

    .header_lang-container.open + .menuList {
        visibility: visible;
    }

    .menuList {
        visibility: hidden;
        position: absolute;
        border: 1px solid #fff;
        box-shadow: 0 6px 28px 0 rgb(134 156 181 / 20%);
        background: #fff;
        top: 40px;
        right: -20px;
        z-index: 10010;
        border-radius: 6px;
        padding: 15px 1px;
    }

    .menuList a {
        line-height: 2.0;
        display: block;
        padding: 0 35px;
        transition: background .2s ease-out;
    }

    .menuList a:hover {
        background-color: #f6f7f8;
    }

    .menuList a.active {
        color: #222;
    }


    .page-content {
        width: calc(100% - 40px);
        height: 62%;
        margin: 50px auto;
        position: relative;
        text-align: center;
        display: flex;
        flex-direction: column;
    }

    .img-wrap {
        width: 100%;
        /* max-width: 363px; */
        min-height: 255px;
        margin: auto;
        background: url(/img/img1.png) center center no-repeat;
        background-size: contain;
    }

    .texts-wrap {
        padding-bottom: 36px;
    }


    @media only screen and (max-width: 480px) {

        .header {
            height: 86px;
        }

        .header__logo-column .logo__item img {
            width: 170px;
        }

        h1, h2, h3 {
            font-size: 30px;
        }

        .img-wrap {
            height: 180px;
            min-height: 120px;
        }

        .text-big {
            font-size: 24px;
            margin-bottom: 0px;
        }

        .tabs__tab-link.lang.open + .level_overlay {
            visibility: visible;
        }

        .level_overlay {
            visibility: hidden;
            position: absolute;
            border: 1px solid #fff;
            box-shadow: 0 0 24px 0 rgb(22 85 158 / 20%);
            background: #fff;
            top: 40px;
            right: -20px;
            z-index: 10010;
            border-radius: 6px;
        }

        .page-content {
            height: calc(96% - 187px);
        }
    }


</style>