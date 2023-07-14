// separate spinner functionality
window.$spinner_timeout = 0;
window.$is_spinner_timeout = false;

if (!window.window.document.getElementById("_divSizer")) {
    document.write("<div id='_divSizer'></div>");
}
var opts = {
  lines: 13 // The number of lines to draw
, length: 24 // The length of each line
, width: 8 // The line thickness
, radius: 48 // The radius of the inner circle
, scale: 0.5 // Scales overall size of the spinner
, corners: 1 // Corner roundness (0..1)
, color: '#000' // #rgb or #rrggbb or array of colors
, opacity: 0.1 // Opacity of the lines
, rotate: 0 // The rotation offset
, direction: 1 // 1: clockwise, -1: counterclockwise
, speed: 1 // Rounds per second
, trail: 60 // Afterglow percentage
, fps: 20 // Frames per second when using setTimeout() as a fallback for CSS
, zIndex: 2e9 // The z-index (defaults to 2000000000)
, className: 'spinner-new' // The CSS class to assign to the spinner
, top: '50%' // Top position relative to parent
, left: '50%' // Left position relative to parent
, shadow: false // Whether to render a shadow
, hwaccel: false // Whether to use hardware acceleration
, position: 'absolute' // Element positioning
}
var _spinner = {
    getScreenSize:function() {
         var $x = Math.max(window.document.body.scrollWidth, document.getElementById("_divSizer").offsetLeft);
         var $y = Math.max(window.document.body.scrollHeight, document.getElementById("_divSizer").offsetTop);
         return new Array($x+"px", $y+"px", $x, $y);
    },
    getSpinnerDiv:function() {
        return '<div class="sk-fading-circle">  <div class="sk-circle1 sk-circle"></div>  <div class="sk-circle2 sk-circle"></div>  <div class="sk-circle3 sk-circle"></div>  <div class="sk-circle4 sk-circle"></div>  <div class="sk-circle5 sk-circle"></div>  <div class="sk-circle6 sk-circle"></div>  <div class="sk-circle7 sk-circle"></div>  <div class="sk-circle8 sk-circle"></div>  <div class="sk-circle9 sk-circle"></div>  <div class="sk-circle10 sk-circle"></div>  <div class="sk-circle11 sk-circle"></div>  <div class="sk-circle12 sk-circle"></div></div>';
    },
    getSpinnerCSS:function() {
        return '.sk-fading-circle {  margin: 20px auto;  width: 50px;  height: 50px;  position: relative;}.sk-fading-circle .sk-circle {  width: 100%;  height: 100%;  position: absolute;  left: 0;  top: 0;}.sk-fading-circle .sk-circle:before {  content: "";  display: block;  margin: 0 auto;  width: 15%;  height: 15%;  background-color: #333;  border-radius: 100%;  -webkit-animation: sk-circleFadeDelay 1.2s infinite ease-in-out both;          animation: sk-circleFadeDelay 1.2s infinite ease-in-out both;}.sk-fading-circle .sk-circle2 {  -webkit-transform: rotate(30deg);      -ms-transform: rotate(30deg);          transform: rotate(30deg);}.sk-fading-circle .sk-circle3 {  -webkit-transform: rotate(60deg);      -ms-transform: rotate(60deg);          transform: rotate(60deg);}.sk-fading-circle .sk-circle4 {  -webkit-transform: rotate(90deg);      -ms-transform: rotate(90deg);          transform: rotate(90deg);}.sk-fading-circle .sk-circle5 {  -webkit-transform: rotate(120deg);      -ms-transform: rotate(120deg);          transform: rotate(120deg);}.sk-fading-circle .sk-circle6 {  -webkit-transform: rotate(150deg);      -ms-transform: rotate(150deg);          transform: rotate(150deg);}.sk-fading-circle .sk-circle7 {  -webkit-transform: rotate(180deg);      -ms-transform: rotate(180deg);          transform: rotate(180deg);}.sk-fading-circle .sk-circle8 {  -webkit-transform: rotate(210deg);      -ms-transform: rotate(210deg);          transform: rotate(210deg);}.sk-fading-circle .sk-circle9 {  -webkit-transform: rotate(240deg);      -ms-transform: rotate(240deg);          transform: rotate(240deg);}.sk-fading-circle .sk-circle10 {  -webkit-transform: rotate(270deg);      -ms-transform: rotate(270deg);          transform: rotate(270deg);}.sk-fading-circle .sk-circle11 {  -webkit-transform: rotate(300deg);      -ms-transform: rotate(300deg);          transform: rotate(300deg); }.sk-fading-circle .sk-circle12 {  -webkit-transform: rotate(330deg);      -ms-transform: rotate(330deg);          transform: rotate(330deg); }.sk-fading-circle .sk-circle2:before {  -webkit-animation-delay: -1.1s;          animation-delay: -1.1s; }.sk-fading-circle .sk-circle3:before {  -webkit-animation-delay: -1s;          animation-delay: -1s; }.sk-fading-circle .sk-circle4:before {  -webkit-animation-delay: -0.9s;          animation-delay: -0.9s; }.sk-fading-circle .sk-circle5:before {  -webkit-animation-delay: -0.8s;          animation-delay: -0.8s; }.sk-fading-circle .sk-circle6:before {  -webkit-animation-delay: -0.7s;          animation-delay: -0.7s; }.sk-fading-circle .sk-circle7:before {  -webkit-animation-delay: -0.6s;          animation-delay: -0.6s; }.sk-fading-circle .sk-circle8:before {  -webkit-animation-delay: -0.5s;          animation-delay: -0.5s; }.sk-fading-circle .sk-circle9:before {  -webkit-animation-delay: -0.4s;          animation-delay: -0.4s;}.sk-fading-circle .sk-circle10:before {  -webkit-animation-delay: -0.3s;          animation-delay: -0.3s;}.sk-fading-circle .sk-circle11:before {  -webkit-animation-delay: -0.2s;          animation-delay: -0.2s;}.sk-fading-circle .sk-circle12:before {  -webkit-animation-delay: -0.1s;          animation-delay: -0.1s;}@-webkit-keyframes sk-circleFadeDelay {  0%, 39%, 100% { opacity: 0; }  40% { opacity: 1; }}@keyframes sk-circleFadeDelay {  0%, 39%, 100% { opacity: 0; }  40% { opacity: 1; } }';
    },
    addCSS:function(css) {
        var head = window.document.getElementsByTagName('head')[0];
        var s = window.document.createElement('style');
        s.setAttribute('type', 'text/css');
        if (s.styleSheet) {   // IE
            s.styleSheet.cssText = css;
        } else {                // the world
            s.appendChild(window.document.createTextNode(css));
        }
        head.appendChild(s);
    },
    show:function($mess) {
        var $newDiv = window.document.createElement("DIV");
        $newDiv.id  = "_divLoader";
        $newDiv.style.zIndex = 160;
        $newDiv.style.left = ((_spinner.getScreenSize()[2] - 220)/2) + "px";
        $newDiv.style.top  = ((_spinner.getScreenSize()[3] - 220)/2) + "px";
        $newDiv.style.height = "95px";
        var $msg = $mess ? $mess : "<?php echo _('Uzgaidiet, lādējam datus'); ?>";
        $newDiv.innerHTML = $msg + '<br>'+_spinner.getSpinnerDiv();
//        $newDiv.innerHTML = $msg + '<br>';
        $newDiv.className = "_popupLoading";
        window.document.body.appendChild($newDiv);

        if(!isNaN(window.$spinner_timeout) && (window.$spinner_timeout > 0)) {
            setTimeout(function() {
                _spinner.hide();
                window.$is_spinner_timeout = true;
            }, (window.$spinner_timeout * 1000));
        };

    },
    hide:function() {
        var $loader = window.document.getElementById('_divLoader');
        if($loader) $loader.parentNode.removeChild($loader);
    },
    off_timeout:function($timeout) {
        if($timeout) {
            $timeout = parseInt($timeout);
            if(!isNaN($timeout) && ($timeout > 0)) {
                window.timerId = null;
                window.$spinner_timeout = $timeout;
            };
        };
    }
};
_spinner.addCSS(_spinner.getSpinnerCSS());
