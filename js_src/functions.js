function ajaxRequest(url, data, processor) {

	if (typeof(webLang) != 'undefined' && webLang != '') {
		if (typeof(data) == 'object') {
			data['webLang'] = webLang;
		} 
		
		if (typeof(data) == 'string') {
			data += '&webLang=' + webLang;
		}
	}
	
	return $.post(url, data, processor, "json");
}

function evalJson(json){
	return eval('(' + json + ')');
}

function isFieldEmpty(field) {
	if ($(field).value == "") {
		$(field).focus();
		return true;
	}
	return false;
}

function isFieldEmail(field) {
	if (!/[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z0-9.-]{2,4}$/.test($(field).val())) {
		return true;
	}
	return false;
}

function spSearch(url) {

	var $doctorFilterSearch = $('#doctors_filter_search');

	if ($doctorFilterSearch.val() !== '') {

		if ($doctorFilterSearch.val() !== $doctorFilterSearch.attr('data-default')) {

			if($doctorFilterSearch.data('spec')) {

				url += '?doctors_filter_specialty=' + $doctorFilterSearch.val();

			} else {

				url += '?doctors_filter_search=' + $doctorFilterSearch.val();
			}

		}

		window.location.href = url;
	}
}

function monkeyPatchAutocomplete() {

	var oldFn = $.ui.autocomplete.prototype._renderItem;

	$.ui.autocomplete.prototype._renderItem = function( ul, item) {

		var spec = item.spec ? ' (specialitate)' : '';
		var a_class = spec ? 'specialty' : '';

		var t = String(item.value).replace(
			new RegExp(this.term, "gi"),
			"<span class='ui-state-highlight'>$&</span>"
		);

		t += spec;

        return $("<li></li>").data( "item.autocomplete", item ).append( "<a class='" + a_class + "'>" + t + "</a>" ).appendTo( ul );
    };
}

function setCookie(cname, cvalue, msec) {
	var d = new Date();
	d.setTime(d.getTime() + msec);
	var expires = "expires=" + d.toUTCString();
	document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function getCookie(cookieName) {

	let cookie = {};

	document.cookie.split(';').forEach(function (el) {

		let [key, value] = el.split('=');

		cookie[key.trim()] = value;
	})

	return cookie[cookieName];
}

function getAllCookies() {

	let cookies = document.cookie.split(';')
	let ret = []

	for(var i = 1; i <= cookies.length; i++) {

		let curCSplit = cookies[i - 1].split('=')

		let currentCookie = {
			name: curCSplit[0],
			value: curCSplit[1],
		}

		ret.push(currentCookie)
	}

	return ret;
}

/**
 *
 * @param iban
 * @returns {boolean}
 */
function validateIBAN(iban) {
	var newIban = iban.toUpperCase(),
		modulo = function (divident, divisor) {
			var cDivident = '';
			var cRest = '';

			for (var i in divident ) {
				var cChar = divident[i];
				var cOperator = cRest + '' + cDivident + '' + cChar;

				if ( cOperator < parseInt(divisor) ) {
					cDivident += '' + cChar;
				} else {
					cRest = cOperator % divisor;
					if ( cRest == 0 ) {
						cRest = '';
					}
					cDivident = '';
				}

			}
			cRest += '' + cDivident;
			if (cRest == '') {
				cRest = 0;
			}
			return cRest;
		};

	if (newIban.search(/^[A-Z]{2}/gi) < 0) {
		return false;
	}

	newIban = newIban.substring(4) + newIban.substring(0, 4);

	newIban = newIban.replace(/[A-Z]/g, function (match) {
		return match.charCodeAt(0) - 55;
	});

	return parseInt(modulo(newIban, 97), 10) === 1;
}



// // //
//
// Query string functions
//
// // //

/**
 *
 * @param queryString
 */
function parseQuery(queryString) {
	var query = {};
	var pairs = (queryString[0] === '?' ? queryString.substr(1) : queryString).split('&');
	for (var i = 0; i < pairs.length; i++) {
		var pair = pairs[i].split('=');
		query[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1] || '');
	}
	return query;
}

/**
 *
 * @param name
 * @returns {string}
 */
function getParameterByName(name) {
	name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
	var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
		results = regex.exec(location.search);
	return results === null ? "" : decodeURIComponent(results[1].replace(/\+/g, " "));
}

/**
 *
 * @param key
 * @param sourceURL
 * @returns {string | *}
 */
function removeParam(key, sourceURL) {
	var rtn = sourceURL.split("?")[0],
		param,
		params_arr = [],
		queryString = (sourceURL.indexOf("?") !== -1) ? sourceURL.split("?")[1] : "";
	if (queryString !== "") {
		params_arr = queryString.split("&");
		for (var i = params_arr.length - 1; i >= 0; i -= 1) {
			param = params_arr[i].split("=")[0];
			if (param === key) {
				params_arr.splice(i, 1);
			}
		}
		rtn += params_arr.length > 0 ? '?' : '';
		rtn = rtn + params_arr.join("&");
	}
	return rtn;
}

function setFilters(el, requestUrl)
{


	$(function () {

		$(document).ready(function () {


			filterSendData = {};
			filterSendData['fields'] = {};


				$(document).on('click', '#langShow .menuList a', function (e) {

					e.preventDefault();

					var queryString = location.search

					var url = this.href


					$.each(el, function (key, value) {

							if (value.attr('type') === 'checkbox') {

								if (value.is(':checked')) {
									filterSendData['fields'][value.attr('id')] = value.val();
								} else {
									filterSendData['fields'][value.attr('id')] = '0';
								}

							} else if (value.attr('type') === 'radio') {

								if (value.is(':checked')) {
									filterSendData['fields'][value.attr('name')] = value.val();
								} else {
									filterSendData['fields'][value.attr('name')] = '0';
								}

							} else {

								if (typeof (filterSendData['fields'][value.attr('id')]) != 'undefined') {
									filterSendData['fields'][value.attr('id')] += value.val();
								} else {
									filterSendData['fields'][value.attr('id')] = value.val();
								}
							}

					});

					let date = $("#filter_date")
					if (date.val()){
						filterSendData['fields']['filter_date'] = date.val()
					}

					filterSendData['action'] = 'setFilters';

					$.ajax({
						url: requestUrl,
						data: {fields: filterSendData},
						type: "POST",
						cache: false,

						success: function () {

							url = url.split(/[?#]/)[0]

							window.location.href = url + queryString

						},
						error: function () {
							url = url.split(/[?#]/)[0]

							window.location.href = url + queryString
						},
					});
				});
			}
		)
	})
}