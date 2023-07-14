// Detect the computer is exiting sleep mode and trigger event
// var lastTime = (new Date()).getTime();
//
// setInterval(function() {
//
//     console.log(1);
//
//     var currentTime = (new Date()).getTime();
//     if (currentTime > (lastTime + 500*2)) {  // ignore small delays
//         setTimeout(function() {
//             //enter code here, it will run after wake up
//             $(window).trigger('pa.wakeup');
//         }, 500);
//     }
//     lastTime = currentTime;
// }, 500);

var Base64 = {
    _keyStr: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=", encode: function (e) {
        var t = "";
        var n, r, i, s, o, u, a;
        var f = 0;
        e = (!e) ? "" : Base64._utf8_encode(e);
        while (f < e.length) {
            n = e.charCodeAt(f++);
            r = e.charCodeAt(f++);
            i = e.charCodeAt(f++);
            s = n >> 2;
            o = (n & 3) << 4 | r >> 4;
            u = (r & 15) << 2 | i >> 6;
            a = i & 63;
            if (isNaN(r)) {
                u = a = 64
            } else if (isNaN(i)) {
                a = 64
            }
            t = t + this._keyStr.charAt(s) + this._keyStr.charAt(o) + this._keyStr.charAt(u) + this._keyStr.charAt(a)
        }
        return t
    }, decode: function (e) {
        var t = "";
        var n, r, i;
        var s, o, u, a;
        var f = 0;
        e = e.replace(/[^A-Za-z0-9+/=]/g, "");
        while (f < e.length) {
            s = this._keyStr.indexOf(e.charAt(f++));
            o = this._keyStr.indexOf(e.charAt(f++));
            u = this._keyStr.indexOf(e.charAt(f++));
            a = this._keyStr.indexOf(e.charAt(f++));
            n = s << 2 | o >> 4;
            r = (o & 15) << 4 | u >> 2;
            i = (u & 3) << 6 | a;
            t = t + String.fromCharCode(n);
            if (u != 64) {
                t = t + String.fromCharCode(r)
            }
            if (a != 64) {
                t = t + String.fromCharCode(i)
            }
        }
        t = Base64._utf8_decode(t);
        return t
    }, _utf8_encode: function (e) {
        e = e.replace(/rn/g, "n");
        var t = "";
        for (var n = 0; n < e.length; n++) {
            var r = e.charCodeAt(n);
            if (r < 128) {
                t += String.fromCharCode(r)
            } else if (r > 127 && r < 2048) {
                t += String.fromCharCode(r >> 6 | 192);
                t += String.fromCharCode(r & 63 | 128)
            } else {
                t += String.fromCharCode(r >> 12 | 224);
                t += String.fromCharCode(r >> 6 & 63 | 128);
                t += String.fromCharCode(r & 63 | 128)
            }
        }
        return t
    }, _utf8_decode: function (e) {
        var c1, c2, c3;
        var t = "";
        var n = 0;
        var r = c1 = c2 = 0;
        while (n < e.length) {
            r = e.charCodeAt(n);
            if (r < 128) {
                t += String.fromCharCode(r);
                n++
            } else if (r > 191 && r < 224) {
                c2 = e.charCodeAt(n + 1);
                t += String.fromCharCode((r & 31) << 6 | c2 & 63);
                n += 2
            } else {
                c2 = e.charCodeAt(n + 1);
                c3 = e.charCodeAt(n + 2);
                t += String.fromCharCode((r & 15) << 12 | (c2 & 63) << 6 | c3 & 63);
                n += 3
            }
        }
        return t
    }
}

function ver() {
    if ($('.ver3').first().is(':visible')) return 3;
    else if ($('.ver2').first().is(':visible')) return 2;
    return 1;
}

var mobile = false, ios, ver2 = ver(), generated_click = false;

if ($.support.touch) mobile = true; //check if we're dealing with mobile
if (mobile) ios = /(iPad|iPhone|iPod)/g.test(navigator.userAgent); //check if we're dealing with iOS

// global vars
var at = 200; //animation time

$(document).ready(function () {
    /*default script snippets*//*iphone delegation*/
    $('*').click(function () {
    });/*email _at_ replace*/
    $('a[href*="mailto"]').each(function () {
        var t = $(this), n = t.attr('href').replace('_at_', '@'), m = t.text().replace('_at_', '@');
        t.attr('href', n).text(m);
    });/*empty links disable*/
    $('[href="#"]').click(function (e) {
        e.preventDefault();
    });/*css3pie*/
    if (window.PIE) {
        $('.css3').each(function () {
            PIE.attach(this);
        });
        $(window).scroll(function () {
            updatePIEButtons()
        });
    }
    var updatePIEButtons = function () {
        if (window.PIE) {
            $('.css3').each(function () {
                PIE.detach(this);
                PIE.attach(this);
            });
        }
    };

    /*data-goto*/
    $('[data-goto]').click(function (e) {
        e.preventDefault();

        console.log($('#' + $(this).data('goto')));

        $('html,body').stop(true).animate({'scrollTop': $('#' + $(this).data('goto')).offset().top - 100 + 'px'}, 1000);
    });


    cinput($('.cinput'));

    footer();
    mob_menu();

    language_bar();

    find_doctors_mob();
    partners();

    doclist();
    sidemenu();
    opendoc();

    auth_gender();
    checkboxes();
    tip();
    doctors_list_arrows();
    doctors_list_line();
    doctors_table_head_fixed();
    heroblock_advanced();

    doctors_open_fav();
    doctors_open_slider();
    doctors_open_slider_resize();
    doctors_open_moreinfo();
    doctors_open_fb_resize();

    my_doctors_fav();

    addMore();

    cselect();
    popup_close();
    popup_client_select();
    position_popup();
    position_popup_fix_top();

    header_logged_dropdown();
    signups_block_mobile_menu();

    setHeightItemProfile();
    resizeAuthorCoupons();
    if (ver() === 2) {
        resizeCoupons();
        resizeTitleCoupons();
        resizeAuthorCoupons();
    }
    if (ver() === 3) {
        $('header:not(.nofollow)').addClass('fixed');
        resizeCouponsMobile();
        resizeTitleCoupons();
        resizeAuthorCoupons();
    }

    $('.partners_block').addClass('auto');
    $('.partners_block').hover(
        function () {
            $('.partners_block').removeClass('auto');
        },
        function () {
            $('.partners_block').addClass('auto');
        }
    );

    slider_auto_slide();
    couponsMessageOrder();

    var $findMore = $('.find-more');

    if($findMore.length) {

        $findMore.on('click', function () {

            switch ($(this).data('find')) {
                case 'cities':
                    location.href = '/iestazu-katalogs/?find="cities"';
                    break;
                case 'specialties':
                    location.href = '/arstu-katalogs/?find="specialties"';
                    break;
                case 'clinics':
                    location.href = '/iestazu-katalogs/?find="clinics"';
                    break;
                case 'ics':
                    location.href = '/arstu-katalogs/?find="ics"';
                    break;
                default:
                    location.href = '/arstu-katalogs/?find="extended"';
            }
        });
    }

    if( ($('.clinic_list') || $('.doctors_list')) && window.findMore ) {

        setTimeout(function () {

            var findMore = window.findMore,
                $el;

            window.findMore = null;

            switch (findMore) {
                case 'cities':
                    $el = $('#cilnics_filter_city');
                    $el.focus();
                    break;
                case 'specialties':
                    $el = $('#doctors_filter_specialty');
                    $el.focus();
                    break;
                case 'clinics':
                    $el = $('#clinics_filter_search');
                    $el.focus();
                    break;
                case 'ics':
                    $el = $('#doctors_filter_ic');
                    $el.focus();
                    break;
                default:
            }

        }, 500);
    }
});

$(window).load(function () {
    footer();
    $('.m_lineText').dotdotdot();
    resize_partners();

    doctors_table_head_fixed();
    doctors_open_slider_resize();
    position_popup();
    position_popup_fix_top();
    doctors_open_fb_resize();
});

$(window).scroll(function () {

    doctors_table_head_fixed();
    position_popup();
    position_popup_fix_top();

    if (ver() === 3) {
        mob_menu_position();
    }
});

$(window).resize(function () {
    footer();
    doctors_table_head_fixed();
    position_popup();
    position_popup_fix_top();
    doctors_open_fb_resize();
    $('.m_lineText').dotdotdot();
    if (ver() === 3) {
        resizeCouponsMobile();
        resizeTitleCoupons();
        resizeAuthorCoupons();
        $('header:not(.nofollow)').addClass('fixed');
        mob_menu_position();
    } else {
        resizeCoupons();
        resizeTitleCoupons();
        resizeAuthorCoupons();
        $('header').removeClass('fixed');
    }

    if (ver2 !== ver()) {
        ver2 = ver();
        $('.find_block .list .item .expand').css('display', '');
        resize_partners();
        doctors_open_slider_resize();
        closeAddMore();
        couponsMessageOrder();
    }

    if (ver() !== 3) {
        $('header .expand').stop(true).slideUp(at);
        $('header .header_menu_mob.open').removeClass('open');
    }
});


function couponsMessageOrder() {
    if ($('.cont_b_m').length > 0) {
        var th = $('.cont_b_m'), text = $('.text_cont', th), btn = $('.btn_cont', th);
        if (ver() === 3) {
            btn.insertAfter(text);
        } else {
            btn.insertBefore(text);
        }
    }
}

function addMore() {
    $('.find_block .item').each(function () {

        var th = $(this);

        $('.expand .more', th).click(function () {
            th.find('.more_item').css('display', 'block');
            $(this).css('display', 'none');
        });

    });
}

function closeAddMore() {
    $('.find_block .item').each(function () {
        var th = $(this);
        th.find('.more_item').css('display', 'none');
        th.find('.more').css('display', 'block');
    });
}


function signups_block_mobile_menu() {
    $('.signup_block .menu2 .trigger').click(function () {
        var th = $(this).closest('.menu2'), ex = $('.expand', th);
        if (th.hasClass('open')) {
            ex.stop(true).slideUp(at, function () {
                th.removeClass('open');
            });
        } else {
            ex.stop(true).slideDown(at);
            th.addClass('open');
        }
    });
}

function header_logged_dropdown() {
    $('header .line1 .right .logged .profile .textcont').click(function () {
        if ($(this).closest('.profile').hasClass('open')) {
            $(this).closest('.profile').removeClass('open');
        } else {
            $(this).closest('.profile').addClass('open');
        }
    });
    $(document).click(function (e) {
        if (!generated_click && (!$(e.target).closest('.profile').length && !$(e.target).closest('.logged').length)) {
            $('header .line1 .right .logged .profile.open').removeClass('open');
        }
    });
}


function popup_client_select() {
    $('.popup_attendee_select input[name=popup_attendee]').change(function () {
        if ($(this).val() === 'me') {
            $('.popup_attendee_notme').addClass('off');
            $(window).trigger('attendeeChange', {person: false});
        } else {
            $('.popup_attendee_notme').removeClass('off');
            $(window).trigger('attendeeChange', {person: true});
        }
        position_popup();
        position_popup_fix_top()
    });

    $('.popup_attendee_notme .addnew>a').click(function (e) {
        e.preventDefault();
        $('#profile_person_id').val('').trigger('change');
        $('.popup_attendee_notme').addClass('open');
        position_popup();
        position_popup_fix_top()
    });
    $('.popup_attendee_notme .existing>a').click(function () {
        $('.popup_attendee_notme').removeClass('open');
        position_popup();
        position_popup_fix_top()
    });
}

function doctors_open_fb_resize() {
    $('.doctors_open .more_info .right .cont,.clinic_open .more_info .right .cont').each(function () {
        if ($('.item.fb .fb-comments iframe', this).length) {
            var c = $(this), w = c.width();
            var str = $('.item.fb .fb-comments iframe', c).attr('src');
            var strw = str.indexOf('width=');
            if (strw > 0) {
                strw += 6;
                var strwe = str.indexOf('&', strw);
                var newstr = str.substring(0, strw) + w;

                if (strwe > 0) newstr += str.substring(strwe);

                $('.item.fb .fb-comments', c).attr('data-width', w);
                $('.item.fb .fb-comments>span', c).width(w);
                $('.item.fb .fb-comments iframe', c).width(w);

                $('.item.fb .fb-comments iframe', c).attr('src', newstr);
            }
        } else {
            $('.item.fb .fb-comments', this).attr('data-width', $(this).width());
        }
    });
}

function position_popup(top) {

    top = top || null;

    if ($('.popup').length && $('.popup').css('display') !== 'none') {

        $('html').addClass('popup_open');

        var h = $('body').scrollTop();

        if (h <= 0) h = $('html').scrollTop();

        $('.popup:not(.fixTop)').each(function () {

            var th = $(this), wh = $(window).height(), ph = th.height();

            if (ph < wh) {

                th.css({'position': '', 'top': '', 'margin-top': ''});

                if (ver() !== 3) {
                    th.css({'margin-top': -Math.floor(ph / 2) + 'px'});
                }

            } else {

                th.css({'position': 'absolute', 'margin-top': ''});

                var pt = parseInt(th.css('top'));

                if (isNaN(pt)) pt = 0;

                if (pt >= h) {
                    th.css('top', h + 'px');
                } else if (h + wh > pt + ph) {

                    if(top) {
                        th.css('top', h + 'px');
                    } else {
                        th.css('top', (h + wh - ph) + 'px');
                    }
                }
            }

            if (ver() === 3) {
                th.css({'width': ''});
            }
        });
    }
}

function position_popup_fix_top() {
    if ($('.popup').length && $('.popup').css('display') != 'none') {

        $('html').addClass('popup_open');


        var h = $('body').scrollTop();
        if (h <= 0) h = $('html').scrollTop();

        $('.popup.fixTop').each(function () {
            var th = $(this), wh = $(window).height(), ph = th.height();
            if (ph > wh) {
                th.css({'position': 'absolute', 'margin-top': ''});
                var pt = parseInt(th.css('top'));
                if (isNaN(pt)) pt = 0;
                if (pt >= h) {
                    th.css('top', 0 + 'px');
                } else if (h + wh > ph) {
                    th.css('top', (h + wh - ph) + 'px');
                }
            } else {
                th.css({'position': '', 'top': '', 'margin-top': ''});
                if (ver() !== 3) {
                    th.css({'margin-top': 0 + 'px'});
                }
            }
            if (ver() === 3) {
                th.css({'width': ''});
            }
        });
    }
}


function popup_close() {
    $('body').on('click', '.popup .close,.popup_bg', function () {
        $('.popup_bg,.popup').remove();
        $('html').removeClass('popup_open');
    });
}

// Should be removed probably???
function cselect() {
    var $selects = $('.cselect');

    $selects.find('select').on('focus', function () {
        $(this).parents('.cselect').addClass('focused');
    });

    $selects.find('select').on('blur', function () {
        $(this).parents('.cselect').removeClass('focused');
    });

    for (var i = 0; i < $selects.length; i++) {
        $($selects[i]).toggleClass('default', !$($selects[i]).find('select').val());
    }

    $('.cselect select').change(function () {

        var c = $(this).closest('.cselect'),
            t = $('.text', c),
            s = $(this);

        if (s.val() === 'default') {
            c.addClass('default');
            t.html(s.data('default'));
        } else {
            c.toggleClass('default', !$(this).val());
            t.html(s.find('option:selected').text());
        }
    });

    $('.cselect select').each(function () {

        var c = $(this).closest('.cselect'),
            t = $('.text', c),
            s = $(this);

        t.html(s.find('option:selected').text());
    });
}

function heroblock_advanced() {
    $('.hero_block .search .advanced').click(function () {
        if ($(this).hasClass('on')) {
            $(this).removeClass('on');
            $(this).closest('.search').find('.collapsible').stop(true).slideUp(at);
        } else {
            $(this).addClass('on');
            $(this).closest('.search').find('.collapsible').stop(true).slideDown(at);
        }
    });
}

function doctors_open_moreinfo() {
    $('.doctors_open .more_info .right .select .item').click(function () {
        if (!$(this).hasClass('active')) {
            var c = $(this).closest('.select'), c2 = $(this).closest('.right').find('.cont');
            $('.item', c).removeClass('active');
            $('.item', c2).removeClass('active');
            $(this).addClass('active');
            $('.item.' + $(this).data('tab'), c2).addClass('active');
        }
    });
}

function doctors_open_slider_resize() {
    $('.doctors_open .table_body .slide_' + ver()).each(function () {

        if($(this).is(':visible')) {

            var h = 0;
            $('.line', this).show();
            $('.times', this).height('').each(function () {
                if ($(this).height() > h) h = $(this).height();
            }).height(h);
            centerMessageTable(h);
            $('.line', this).css('display', '');
        }
    });
}

function centerMessageTable(h) {
    $('.message_cont').each(function () {
        var msg = $(this);

        if(msg.is(':visible')) {

            var h_message = $('.message', msg).height(),
                $days = $(msg.parents('.days')),
                top = (h_message + (h / 2));

            if($days.hasClass('calend_doctorView')) {

                var dayHeight = $days.find('.day').height() + parseInt($days.find('.day').css('paddingTop')),
                    cellHeight = $days.find('.cell').height(),
                    timesHeight = $days.find('.times').height();

                top = cellHeight - dayHeight - (msg.height() / 2);
            }

            $('.message_cont').css({'top': top,});
        }
    });
}

function doctors_open_slider() {
    $('.doctors_open .table_body').each(function () {
        var c = $(this), current_week = [1, 1, 1];
        $('.arrow_left', c).click(function () {
            var ve = ver();
            var el1 = $('.slide_' + ve + ' .line[data-id=' + current_week[ve - 1] + ']', c);
            var el2 = $('.slide_' + ve + ' .line[data-id=' + (current_week[ve - 1] - 1) + ']', c);

            if (!c.hasClass('animating') && el1.length > 0 && el1.length === el2.length) {
                c.addClass('animating');

                var w = $('.slide_' + ve, c).first().width();
                el2.css('left', -w).addClass('active');
                el1.css({'position': 'absolute'}).animate({'left': w}, at, function () {
                    $(this).removeClass('active').css({'left': '', 'position': ''});
                });
                el2.animate({'left': 0}, at, function () {
                    c.removeClass('animating');
                });

                current_week[ve - 1]--;
            }
        });
        $('.arrow_right', c).click(function () {
            var ve = ver();
            var el1 = $('.slide_' + ve + ' .line[data-id=' + current_week[ve - 1] + ']', c);
            var el2 = $('.slide_' + ve + ' .line[data-id=' + (current_week[ve - 1] + 1) + ']', c);
            if (!c.hasClass('animating') && el1.length > 0 && el1.length === el2.length) {
                c.addClass('animating');

                var w = $('.slide_' + ve, c).first().width();
                el2.css('left', w).addClass('active');
                el1.css({'position': 'absolute'}).animate({'left': -w}, at, function () {
                    $(this).removeClass('active').css({'left': '', 'position': ''});
                });
                el2.animate({'left': 0}, at, function () {
                    $(this).css('left', '');
                    c.removeClass('animating');
                });

                current_week[ve - 1]++;
            }
        });
    });
}

function doctors_open_fav() {
    $('.doctors_open .profile .fav .btnw:not(.haveScript)').click(function () {
        if ($(this).hasClass('added')) {
            $('.doctors_open .profile .fav .btnw').removeClass('added');
        } else {
            $('.doctors_open .profile .fav .btnw').addClass('added');
        }
    }).addClass('haveScript');
}

function doctors_table_head_fixed() {
    var _h = $('body').scrollTop();
    if (_h <= 0) _h = $('html').scrollTop();
    $('.doctors_list .table_head .space_taken').each(function () {
        if (_h > $(this).offset().top && _h < $('.doctors_list .table_body .list .item:last-child .slide').offset().top - $(this).height()) $(this).closest('.table_head').find('.moving_part').addClass('fixed');
        else $(this).closest('.table_head').find('.moving_part').removeClass('fixed');
    });
}

function doctors_list_line() {
    $('.doctors_list .table_body .list .item .week .arrow:not(.have_animation)').on('click', function () {
        var up = true, c = $(this).closest('.cell');
        if (!c.hasClass('animating')) {
            c.addClass('animating');
            if ($(this).hasClass('arrow_down')) up = false;
            if (up) {
                var itemnew = $('.line:not(.hidden)', c).first().prev(), itemold = $('.line:not(.hidden)', c).last();
                itemnew.stop(true).slideDown(at, function () {
                    itemnew.removeClass('hidden').css('display', '');
                }).css('display', 'block');
                itemold.stop(true).slideUp(at, function () {
                    itemold.addClass('hidden').css('display', '');
                    $('.arrow_down', c).addClass('active');
                    if (!$('.line:not(.hidden)', c).first().prev().hasClass('line')) $('.arrow_up', c).removeClass('active');
                    c.removeClass('animating');
                });
            } else {
                var itemnew = $('.line:not(.hidden)', c).last().next(), itemold = $('.line:not(.hidden)', c).first();
                itemnew.stop(true).slideDown(at, function () {
                    itemnew.removeClass('hidden').css('display', '');
                }).css('display', 'block');
                itemold.stop(true).slideUp(at, function () {
                    itemold.addClass('hidden').css('display', '');
                    $('.arrow_up', c).addClass('active');
                    if (!$('.line:not(.hidden)', c).last().next().hasClass('line')) $('.arrow_down', c).removeClass('active');
                    c.removeClass('animating');
                });
            }
        }
    });

    $('.doctors_list .table_body .list .item:not(.has_fav_animation)').hover(function () {
        if (!$('.fav_cont', this).hasClass('faved')) {
            $('.fav_cont .btnw', this).stop(true).animate({'top': 0}, at);
        }
    }, function () {
        if (!$('.fav_cont', this).hasClass('faved')) {
            $('.fav_cont .btnw', this).stop(true).animate({'top': -35}, at);
        }
    }).addClass('has_fav_animation');

    $('.doctors_list .table_body .list .item .fav_cont .btnw:not(.have_script)').click(function () {
        $(this).addClass('have_script');
        if ($(this).closest('.fav_cont').hasClass('faved')) {
            $(this).closest('.fav_cont').removeClass('faved');
        } else {
            $(this).closest('.fav_cont').addClass('faved');
        }
    });
}

function my_doctors_fav() {
    $('.my_doctors .cont .list .item .fav_cont .btng').click(function () {
        if ($(this).closest('.fav_cont').hasClass('faved')) {
            $(this).closest('.fav_cont').removeClass('faved');
            $(this).closest('.item').fadeOut();
        }
    });
}


function doctors_list_arrows() {
    $('.doctors_list').each(function () {
        var c = $(this), current_week = 1;
        $('.table_head .calendar', c).on('swipeleft', function () {
            $('.table_head .arrow_right', c).trigger('click');
        }).on('swiperight', function () {
            $('.table_head .arrow_left', c).trigger('click');
        });
        $('.table_head .arrow_left', c).click(function () {
            var el1 = $('.slide .week[data-id=' + current_week + ']', c);
            var el2 = $('.slide .week[data-id=' + (current_week - 1) + ']', c);

            if (!c.hasClass('animating') && el1.length > 0 && el1.length === el2.length) {
                c.addClass('animating');

                var w = $('.slide', c).first().width();
                el1.animate({'left': w}, at, function () {
                    $(this).removeClass('active');
                });
                el2.css('left', -w).addClass('active').animate({'left': 0}, at, function () {
                    c.removeClass('animating');
                });

                current_week--;
            }
        });
        $('.table_head .arrow_right', c).on('click', function () {
            var el1 = $('.slide .week[data-id=' + current_week + ']', c);
            var el2 = $('.slide .week[data-id=' + (current_week + 1) + ']', c);
            if (!c.hasClass('animating') && el1.length > 0 && el1.length === el2.length) {
                c.addClass('animating');

                var w = $('.slide', c).first().width();
                el1.animate({'left': -w}, at, function () {
                    $(this).removeClass('active').css('left', '');
                });
                el2.css('left', w).addClass('active').animate({'left': 0}, at, function () {
                    $(this).css('left', '');
                    c.removeClass('animating');
                });

                current_week++;
            }
        });
    });
}

function tip() {

    $('[data-hastip]').each(function () {

        var th = $(this);
        var tipClass = this.dataset.tipclass ? this.dataset.tipclass : ''

        th.hover(function () {

            console.log('tip hover')

            if (ver() === 1) {

                th.addClass('tip_expanded');
                var tip_cont = $('<div class="expanded_tooltip' + (tipClass ? ' ' + tipClass : '') + '"></div>');
                tip_cont.html(th.data('hastip'));
                tip_cont.append('<div class="pointer"></div>');
                tip_cont.appendTo('body');

                var tt = th.offset().top - tip_cont.outerHeight(false) - 5,
                    tl = th.offset().left + Math.floor((th.outerWidth(false) - tip_cont.outerWidth(false)) / 2);

                if(tipClass === 'bottom') {
                    tt = th.offset().top + th.outerHeight(false) + 5
                }

                if (tl < 0) {
                    $('.pointer').css('margin-left', tl - 5);
                    tl = 0;
                }

                if (tl + tip_cont.outerWidth(false) > $(window).width()) {
                    var offsetleft = tl;
                    tl = $(window).width() - tip_cont.outerWidth(false);
                    offsetleft -= tl;
                    $('.pointer').css('margin-left', offsetleft - 5);
                }

                tip_cont.css({'top': tt, 'left': tl});
            }

        }, function () {

            if (ver() === 1) {

                $('.expanded_tooltip').remove();
                th.removeClass('tip_expanded');
            }
        });

        th.click(function () {

            console.log('tip click')

            if (ver() !== 1 && !$(this).hasClass('tip_expanded')) {

                th.addClass('tip_expanded');

                var tip_cont = $('<div class="expanded_tooltip' + (tipClass ? ' ' + tipClass : '') + '"></div>');

                tip_cont.html(th.data('hastip'));
                tip_cont.append('<div class="pointer"></div>');
                tip_cont.appendTo('body');

                var tt = th.offset().top - tip_cont.outerHeight(false) - 5,
                    tl = Math.floor(th.offset().left + ((th.outerWidth(false) - tip_cont.outerWidth(false)) / 2));

                if(tipClass === 'bottom') {
                    tt = th.offset().top + th.outerHeight(false) + 5
                }

                if (tl < 0) {
                    $('.pointer').css('margin-left', tl - 5);
                    tl = 0;
                }

                if (tl + tip_cont.outerWidth(false) > $(window).width()) {
                    var offsetleft = tl;
                    tl = $(window).width() - tip_cont.outerWidth(false);
                    offsetleft -= tl;
                    $('.pointer').css('margin-left', offsetleft - 5);
                }

                tip_cont.css({'top': tt, 'left': tl});
            }
        });
    });

    $(document).click(function (e) {
        if (!generated_click &&
            ((!$(e.target).closest('.expanded_tooltip').length) && (!$(e.target).hasClass('.expanded_tooltip'))) &&
            ((!$(e.target).closest('.tip_expanded').length) && (!$(e.target).hasClass('.tip_expanded')))) {
            $('.expanded_tooltip').remove();
            $('.tip_expanded').removeClass('tip_expanded');
        }
    });

    $(window).resize(function () {
        $('.expanded_tooltip').remove();
        $('.tip_expanded').removeClass('tip_expanded');
    });
}

function checkboxes() {

    $(document).on('click', 'input[type=checkbox]', function () {

        var $this = $(this);

        if ($this.prop('checked')) {
            $this.closest('.item').addClass('checked');
            $this.closest('.table_line').addClass('selected');
        } else {
            $this.closest('.item').removeClass('checked');
            $this.closest('.table_line').removeClass('selected');
        }
    });

    let $chk = $('input[type=checkbox]');

    for(let i = 0; i < $chk.length; i++) {

        if($($chk[i]).prop('checked')) {
            $($chk[i]).closest('.item').addClass('checked');
        } else {
            $($chk[i]).closest('.item').removeClass('checked');
        }
    }
}

function auth_gender() {
    $('.gender_select select').change(function () {
        var c = $(this).closest('.gender_select');
        if ($(this).val() === 'female') {
            c.removeClass('male').addClass('female');
            $('.text', c).html(c.data('female'));
        } else {
            c.removeClass('female').addClass('male');
            $('.text', c).html(c.data('male'));
        }
    });
}

function opendoc() {
    $('.opendoc table').each(function () {
        var $this = $(this);
        if ($this.attr('border') == 0) {
            $this.addClass('noborder_table');
        }
        $this.wrap('<div class="table"></div>');
    });
}

function sidemenu() {
    $('.sidemenu .trigger').click(function () {
        var c = $(this).closest('.sidemenu');
        if (c.hasClass('open')) {
            $('.menu', c).stop(true).slideUp(function () {
                $('.menu', c).css('display', '');
                c.removeClass('open');
            });
        } else {
            $('.menu', c).stop(true).slideDown(function () {
                $('.menu', c).css('display', '');
            });
            c.addClass('open');
        }
    });
    $(document).click(function (e) {
        $('.sidemenu.open').each(function () {
            if (!$(e.target).closest('.sidemenu').is($(this)))
                $('.menu', this).stop(true).slideUp(function () {
                    $(this).css('display', '');
                    $(this).closest('.sidemenu').removeClass('open');
                });
        });
    });
}

function doclist() {
    $('.doclist arcticle').each(function () {
        if ($('>img,>.img', this).length) $(this).addClass('with_image');
    });
}

function menu() {
    $('.menu').each(function () {
        var c = $(this), ul = $('>ul', c);
        $('.main_trigger', c).click(function () {
            if (c.hasClass('open')) {
                ul.stop(true).slideUp(function () {
                    c.removeClass('open');
                    ul.css('display', '');
                });
            } else {
                c.addClass('open');
                ul.stop(true).slideDown();
            }
        });
        $('>.has_sub:not(.active)>.trigger', ul).click(function () {
            var th = $(this).closest('.has_sub'), sub = $('.sub', th);
            if (th.hasClass('open')) {
                sub.stop(true).slideUp(function () {
                    th.removeClass('open');
                    sub.css('display', '');
                });
            } else {
                th.addClass('open');
                sub.stop(true).slideDown();
            }
        });
    });
}

function find_doctors_mob() {
    $('.find_block .list .item .trigger').click(function () {
        var c = $(this).closest('.item');
        if (c.hasClass('open') && ver() === 3) {
            $('.expand', c).stop(true).slideUp(at, function () {
                c.removeClass('open');
                closeAddMore();
            });
        } else if (ver() === 3) {
            $('.expand', c).stop(true).slideDown(at);
            c.addClass('open');
        }
    });
}

function resize_partners() {
    $('.partners_block .list .slide').each(function () {
        var c = $(this);
        if (ver() !== 1) {
            var w = 0;
            $('.item', c).each(function () {
                w += $(this).outerWidth(true);
            });
            c.width(w);
        }
    });
}

function resizeCoupons() {

    $('.signup_block.coupons .list .item,.cont_coupons .col1 .list .item').each(function () {

        var th = $(this);

        $('.image img', th).each(function () {
            var w = $(this).width();
            var h = $(this).height();
            var ratio = w / h;
            var list = th.parent();
            var listW = list.width() - 40;
            var itemW = Math.floor(listW / 3);
            var maxWImg = itemW;
            th.width(itemW);
            $(this).css('width', maxWImg);
            $(this).css('height', maxWImg / ratio);
        });
    });
}

function resizeTitleCoupons() {
    var maxH = 0;

    $('.signup_block.coupons .list .item .title h3,.cont_coupons .col1 .list .item .title h3').each(function () {

        var h = $(this).height();

        if (h > maxH) {
            maxH = h;
        }
    });

    $('.signup_block.coupons .list .item .title,.cont_coupons .col1 .list .item .title').css('height', maxH);
}

function resizeAuthorCoupons() {
    var maxHT = 0,
        $author = $('.signup_block.coupons .list .item .author,.cont_coupons .col1 .list .item .author, .offers_block .list .item .author');

    $author.each(function () {

        var h = $(this).height();

        if (h > maxHT) {
            maxHT = h;
        }
    });

    $author.css('height', maxHT);
}

function resizeCouponsMobile() {
    $('.signup_block.coupons .list .item, .cont_coupons .col1 .list .item').each(function () {
        var th = $(this);
        $('.image img', th).each(function () {
            var w = $(this).width();
            var h = $(this).height();
            var ratio = w / h;
            var list = th.parent();
            var listW = list.width() - 20;
            var itemW = Math.floor(listW / 2);
            var maxWImg = itemW;
            th.width(itemW);
            $(this).css('width', maxWImg);
            $(this).css('height', maxWImg / ratio);
        });
    });
}

// slider functions
function listSlideLeft(c, th) {
    if (!c.hasClass('animating')) {
        c.addClass('animating');
        var im = $('.item', th).last().prependTo(th);
        im.css({'margin-left': -im.outerWidth() - 40}).stop(true).animate({'margin-left': 0}, function () {
            im.css({'margin-left': ''});
            c.removeClass('animating');
        });
    }
}

function listSlideRight(c, th) {
    if (!c.hasClass('animating')) {
        c.addClass('animating');
        var im = $('.item', th).first();
        im.stop(true).animate({'margin-left': -im.outerWidth() - 40}, function () {
            im.css({'margin-left': ''}).appendTo(th);
            c.removeClass('animating');
        });
    }
}

function arrowSlideLeft(c, th) {
    var x = th.parent().scrollLeft(), w = 0, i = 0;
    $('.item', th).each(function () {
        if ($(this).position().left + parseInt($(this).css('margin-left')) < 0) i++;
    });
    i--;
    if (i < 0) i = 0;
    var needed_item = $('.item', th).eq(i);
    if (!needed_item.length) needed_item = $('.item', th).first();
    th.parent().stop(true).animate({scrollLeft: x + needed_item.position().left + parseInt(needed_item.css('margin-left'))}, at);
}

function arrowSlideRight(c, th) {
    var x = th.parent().scrollLeft(), w = 0, i = 0;
    $('.item', th).each(function () {
        if (Math.floor($(this).position().left + parseInt($(this).css('margin-left'))) <= 0) i++;
    });
    var needed_item = $('.item', th).eq(i);
    if (!needed_item.length) needed_item = $('.item', th).last();
    th.parent().stop(true).animate({scrollLeft: Math.floor(x + needed_item.position().left + parseInt(needed_item.css('margin-left')))}, at);
}

function partners() {

    $('.partners_block').each(function () {

        var c = $(this),
            th = $('.list .cont .slide', c);

        $('.list .arrow_left', c).on('click listSlideLeft', function () {
            listSlideLeft(c, th);
        });
        $('.list .arrow_right', c).on('click listSlideRight', function () {
            listSlideRight(c, th);
        });
        $('.arrows1 .arrow_left', c).on('click arrowSlideLeft', function () {
            arrowSlideLeft(c, th);
        });
        $('.arrows1 .arrow_right', c).on('click arrowSlideRight', function () {
            arrowSlideRight(c, th);
        });
    });
}

function footer() {

    var $footerPush = $('.footer_push'),
        $footer = $('footer'),
        h = null;

    $footerPush.height('');
    $footer.css({'height': '', 'margin-top': ''});

    if (ver() === 1 && !$footer.hasClass('no_sticky_dt')) {

        h = $footer.outerHeight();

        $footerPush.height(h);
        $footer.css({'height': h, 'margin-top': -h});

    } else if (ver() === 2 && !$footer.hasClass('no_sticky_tab')) {

        h = $footer.outerHeight();

        $footerPush.height(h);
        $footer.css({'height': h, 'margin-top': -h});

    } else if (ver() === 3 && !$footer.hasClass('no_sticky_mob')) {

        h = $footer.outerHeight();

        $footerPush.height(h);
        $footer.css({'height': h, 'margin-top': -h});
    }
}

var storeStart = 0;

function mob_menu() {
    $('header .header_menu_mob .trigger').click(function () {
        var c = $(this).closest('.header_menu_mob');
        if (c.hasClass('open')) {
            $(this).parent().parent().parent().parent().children('.expand').stop(true).slideUp(at, function () {
                c.removeClass('open');
            });
        } else {
            $(this).parent().parent().parent().parent().children('.expand').stop(true).slideDown(at).show(function () {
                storeStart = $(document).scrollTop();
                mob_menu_position();
            });

            c.addClass('open');
        }
    });
    $(document).click(function (e) {
        if (!generated_click && !$(e.target).closest('.header_menu_mob').length) {
            $('header .expand').stop(true).slideUp(at);
            $('header .header_menu_mob.open').removeClass('open');
        }
    });
}

function mob_menu_position() {

    var $header = $('header'),
        $headerExpand = $('header .expand');

    if ($headerExpand.length && $headerExpand.css('display') !== 'none') {
        var h = $('body').scrollTop();
        if (h <= 0) h = $('html').scrollTop();


        var th = $headerExpand,
            wh = Math.max(document.documentElement.clientHeight, window.innerHeight || 0),
            head = $header.outerHeight(),
            ph = th.outerHeight(),
            phTotal = ph + head,
            topFix = wh + head - phTotal;

        if (phTotal < wh) {
            $(this).css({'position': '', 'top': '', 'margin-top': '0'});
        } else {
            th.css({'position': 'absolute', 'margin-top': head + 'px'});
            var pt = parseInt(th.css('top'));
            if (isNaN(pt)) {
                pt = topFix;
            }

            if (pt >= h) {
                th.css('top', h + 'px');
            } else if ((phTotal + pt) > wh) {
                if (h < storeStart) {
                    th.css('top', '0px');
                } else {
                    th.css('top', '-' + (h - storeStart - ph + wh - topFix) + 'px');
                }
            } else if ((phTotal + pt) === (wh)) {
                if (($(document).scrollTop() - storeStart) <= (-parseInt(th.css('top')))) {
                    th.css('top', '-' + (h - storeStart - ph + wh - topFix) + 'px');
                }
            } else if (phTotal - wh + pt !== 0) {
                th.css('top', pt - (phTotal - wh + pt) + 'px');
            }

            var check = (th.outerHeight() + $header.outerHeight()) - wh + parseInt(th.css('top'));

            if (check < 0) {
                th.css('top', pt - (phTotal - wh + pt) + 'px');

            }
        }
    }
}

function setHeightItemProfile() {
    var th = $('.signup_block.profils .col2_y'), ell = $('.item.left .cont', th), elr = $('.item.right .cont', th),
        hLeft, hRight, max;
    hLeft = ell.height();
    hRight = elr.height();
    max = (hLeft > hRight) ? max = hLeft : max = hRight;
    ell.css('min-height', max);
    elr.css('min-height', max);
}

function language_bar() {
    $('header .controls .languages').each(function () {
        var c = $(this);
        $('.trigger', c).click(function () {
            if (!c.hasClass('open')) {
                $('.dropdown .list', c).stop(true).slideDown(at);
                c.addClass('open');
            }
        });
        $(document).click(function (e) {
            if (!generated_click && !$(e.target).closest('.languages').is(c)) {
                $('.dropdown .list', c).stop(true).slideUp(at);
                c.removeClass('open');
            }
        });
    });
}

function cinput(a) {

    // a.each(function () {
    //     var th = $(this), f = th.find('input, textarea');
    //     if (f.attr('type') === 'password' && typeof (f.data('default')) != 'undefined') {
    //         th.append('<input type="text" class="dummy" value="' + f.data('default') + '" style="position:absolute;top:' + th.css('padding-top') + ';left:' + th.css('padding-left') + ';right:' + th.css('padding-right') + ';">');
    //         f.css({'position': 'relative', 'z-index': '2', 'opacity': 0}).focus(function () {
    //             f.css('opacity', '');
    //             $('.dummy', th).hide();
    //         }).blur(function () {
    //             if (f.val().length <= 0) {
    //                 f.css('opacity', 0);
    //                 $('.dummy', th).show();
    //             }
    //         });
    //     }
    //
    //     var email = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
    //
    //     if (!f.val()) {
    //         th.addClass('default').addClass('invalid');
    //         f.val(f.data('default'));
    //     } else if (th.data('type') === 'email' && !email.test(f.val())) {
    //         th.addClass('invalid');
    //     }
    //
    //     f.focus(function () {
    //         if (th.hasClass('wrong')) {
    //             var msg = th.next();
    //         }
    //         th.removeClass('invalid').removeClass('wrong').addClass('focused');
    //         if (th.hasClass('default')) {
    //
    //             if(f.data('current') === f.data('default')) {
    //                 f.data('current', '');
    //             }
    //
    //             if(f.val() !== f.data('current') && f.val() !== '') {
    //                 if(!th.hasClass('no-clear')) {
    //                     f.val('');
    //                 }
    //                 th.removeClass('default');
    //             }
    //         }
    //     });
    //
    //     f.blur(function () {
    //
    //         th.removeClass('focused');
    //
    //         if (!f.val()) {
    //
    //             if(f.hasClass('ui-autocomplete-input') && f.data('current') && f.val() !== '') {
    //                 f.val(f.data('current'));
    //                 th.addClass('default').addClass('invalid');
    //             } else {
    //                 f.val(f.data('default'));
    //                 th.addClass('default').addClass('invalid');
    //             }
    //         } else if (th.data('type') === 'email' && !email.test(f.val())) {
    //             th.addClass('invalid');
    //         }
    //     });
    // });
}


function slider_auto_slide() {

    setTimeout(function () {

        if (($('header .header_menu_mob').hasClass('open') && ver() === 3) || $('.line1 .logged .profile').hasClass('open')) {
            slider_auto_slide();
            return true;
        }

        $('.partners_block.auto .list .arrow_right').trigger('listSlideRight');
        slider_auto_slide();
    }, 2500);
}

function setDatapicker(el, callback = null) {

    $(el).datepicker({
        dateFormat: 'yy-mm-dd',
        firstDay:1,
        dayNamesMin:['Sv','P','O','T','C','Pk','S'],
        monthNames:messages.monthNames,
        minDate:'0d',
        showOtherMonths:true,
        selectOtherMonths:true,
        beforeShow: function (textbox, instance) {

            if(instance.dpDiv.is(':visible')) {
                return false;
            }

            setTimeout(function () {

                var	btn = $(textbox).siblings('.calendar-trigger'),
                    offsets = btn.offset(),
                    top = offsets.top + 5 + btn.outerHeight();

                instance.dpDiv.css({
                    top: top,
                    left: offsets.left - instance.dpDiv.outerWidth() + btn.outerWidth(),
                });
            }, 50);
        },
        onSelect: function(dateText) {

            if(!callback) {
                profile.filterReservationCalendar('', dateText);
            } else {
                callback(dateText);
            }
        }
    });

    $('.calendar-trigger').off('click').on('click', function(e){

        e.preventDefault()
        $('#jqui-calendar').datepicker('show');
    });
}

function setInsDatepicker(el, callback = null, popup = false)
{

    let opts = {
        dateFormat: 'dd.mm.yy',
        firstDay: 1,
        dayNamesMin: ['Sv', 'P', 'O', 'T', 'C', 'Pk', 'S'],
        monthNames: messages.monthNames,
        minDate: -365,
        showOtherMonths: true,
        selectOtherMonths: true,
    };

    if (popup) {

        opts['beforeShow'] = function (input, inst) {

            let cinput = $(input).parent('.cinput');

            inst.dpDiv.css({
                marginTop: '13px',
                marginLeft: '-12px'
            });
        }
    }

    $(el).datepicker(opts);
}

// voice object constructor

function VoiceField(el, options = null) {

    /*
    * This function is voice input object constructor
    *
    * It accepts DOM element (input, textarea) on which we want to allow the voice input functionality
    * The HTML structure should include the wrapper element with .voice css class as a parent of el
    * and as well the element for toggle voice input button (html-element with .voiceBtn_toggle class)
    *
    * The wrapper element will have .recordStarted class when recording started to provide the ability
    * to change css styles to indicate the voice input active state
    *
    * As an addition we can define voice synthesis button to read the content of el input or textarea (we don't implement for now...)
    * */

    let api = window.speechRecognition || window.webkitSpeechRecognition;

    // we just return null if speechRecognition api is unavailable

    if(!api) {
        return null;
    }

    // default options

    this.opts = {
        showPlayButton: false,
    };

    // override options by user defined if passed

    if(options && typeof options === 'object') {

        for(prop in this.opts) {

            if(this.opts.hasOwnProperty(prop)) {

                if(options.hasOwnProperty(prop)) {
                    this.opts[prop]
                }
            }
        }
    }

    // timeout in seconds
    // if nothing changed in textarea we just re-init api and controls

    const timeout = 15 * 1000;
    let initialContent = null;

    this.el = el;
    this.container = el.closest('.voice');
    this.container.classList.add('voice_api_available');
    this.toggle = this.container.querySelector('.voiceBtn_toggle');
    this.voiceError = this.container.querySelector('.voiceError');
    this.recordStarted = false;
    this.timer = null;
    this.langSelect = this.container.querySelector('.voiceLangSelect');

    // Инициализируем с латышским языком по умолчанию

    this.lang = 'lv-LV';

    // текст ту спич пока комментируем, требует еще исследований
    // this.playButton = null;
    // this.speechSynthesisApi = null;
    // this.speechSynthesis = false;
    // this.speak = null;

    // if showPlayButton is set to true we init play button and speechSynthesis api

    /*
    * Пока что не решено как подгрузить латышский язык... Имеется 66 различных голосов, но латышского нет среди них
    * */

    // if(this.opts.showPlayButton) {
    //
    //     this.playButton = this.container.querySelector('.playBtn_toggle');
    //     this.speechSynthesisApi = window.speechSynthesis;
    //
    //     if(this.playButton && this.speechSynthesisApi) {
    //
    //         this.speechSynthesis = true;
    //
    //
    //         let voices = this.speechSynthesisApi.getVoices();
    //
    //
    //         setTimeout(() => {
    //
    //             console.log('voices');
    //             console.log(window.speechSynthesis.getVoices());
    //
    //             voices = window.speechSynthesis.getVoices();
    //
    //             var englishes = voices.filter(function (voice) { return voice.lang.substr(0, 2) == "en" });
    //
    //             window.speechSynthesis.onvoiceschanged = function() {
    //
    //                 let updatedVoices = window.speechSynthesis.getVoices();
    //
    //                 console.log('Voices changed:');
    //                 console.log(updatedVoices);
    //             };
    //
    //             console.log('englishes:');
    //             console.log(englishes);
    //
    //             this.speak = () => {
    //
    //                 if(this.speechSynthesisApi.speaking) {
    //
    //                     this.speechSynthesisApi.cancel();
    //                     console.log('Currently speaking')
    //
    //                 } else if (el.value !== '') {
    //
    //                     this.container.classList.add('playStarted');
    //
    //                     const utterThis = new SpeechSynthesisUtterance(el.value);
    //
    //                     // console.log('utterThis.lang:');
    //                     // console.log(utterThis.lang);
    //
    //                     // utterThis.lang = 'lv-LV';
    //                     utterThis.lang = 'lv';
    //
    //                     console.log(utterThis.lang);
    //
    //                     utterThis.onend = (event) => {
    //                         this.container.classList.remove('playStarted');
    //                         console.log('SpeechSynthesisUtterance.onend');
    //                     };
    //
    //                     utterThis.onerror = (event) => {
    //                         this.container.classList.remove('playStarted');
    //                         console.error('SpeechSynthesisUtterance.onerror');
    //                     };
    //
    //                     utterThis.onpause = (event) => {
    //
    //                         const char = event.utterance.text.charAt(event.charIndex);
    //
    //                         this.container.classList.remove('playStarted');
    //
    //                         console.log('Speech paused at character ' +
    //                             event.charIndex +
    //                             ' of "' +
    //                             event.utterance.text +
    //                             '", which is "' +
    //                             char +
    //                             '".'
    //                         );
    //                     };
    //
    //                     this.speechSynthesisApi.speak(utterThis);
    //                 }
    //             }
    //
    //         }, 50);
    //     }
    // }

    // init speech recognition api

    this.api = null;

    this.init = () => {
        this.api = new api();
        this.api.continuous = true;
        this.api.interimResults = false;
        this.api.lang = this.lang;
    }

    this.init()

    // Обработчик смены языка голосового ввода
    // Использован jQuery поскольку мы используем здесь плагин Selectric для единообразия селектов в проекте

    $(document).on('change', '#voiceLangSelect', (e) => {
        this.stop();
        this.lang = e.target.value;
        this.api.lang = e.target.value;
    });

    // result of voice input handler

    this.api.onresult = (e) => {

        let resultIndex = e.resultIndex,
            transcript = e.results[resultIndex][0].transcript;

        this.el.value += (this.el.value === '') ? transcript : (' ' + transcript);
    }

    // end of api work handler -- we just call stop method

    this.api.onend = (e) => {
        this.stop();
    }

    // error event handler -- we call stop method

    this.api.onerror = (e) => {
        console.log('Speech recognition error:');
        console.log(e);
        this.stop();
        this.init();
        this.voiceError.style.display = 'block';
    }

    // start / stop voice recognition methods

    this.setTimer = () => {

        this.timer = setTimeout(() => {

            if(initialContent === this.el.value) {
                this.stop()
                this.init()
                clearTimeout(this.timer)
                this.timer = null
            } else {
                clearTimeout(this.timer)
                this.timer = null
                this.setTimer()
            }

        }, timeout)
    }

    this.start = () => {

        if(!this.api) {
            this.init()
        }

        this.api.start()
        this.recordStarted = true
        this.container.classList.add('recordStarted')
        this.voiceError.style.display = 'none'
        initialContent = this.el.value
        $(this.langSelect).attr('disabled', true)
        $(this.langSelect).selectric('refresh')
        this.setTimer()
    }

    this.stop = () => {

        if(!this.api) {
            this.init()
        }

        this.api.stop()
        $(this.langSelect).attr('disabled', false)
        $(this.langSelect).selectric('refresh')
        clearTimeout(this.timer)
        this.timer = null
        this.recordStarted = false
        this.container.classList.remove('recordStarted')
    }


    // define click handler for toggle button

    this.toggle.addEventListener('click', (e) => {

        if(!this.recordStarted) {
            this.start();
        } else {
            this.stop();
        }
    });

    // define playBtn handler

    // if(this.speechSynthesis) {
    //
    //     this.playButton.addEventListener('click', (e) => {
    //
    //         this.speak();
    //     });
    // }
}

// init voice input on textarea element

function set_voice_input(el) {
    el.dataset.voice = 'on';
    return new VoiceField(el);
}

// init page datepickers

$(document).on('ready', function () {

    var $dp = $('#jqui-calendar');
    var $dpIns = $('.jq-calend');

    if($dp.length) {

        $.each($dp, function (key, el) {
            setDatapicker(el);
        });
    }

    if($dpIns.length) {

        $.each($dpIns, function (key, el) {
            setInsDatepicker(el);
        });
    }

    $(window).on('resize', function () {

        $('.hasDatepicker')
            .datepicker('hide')
            .datepicker('option', 'disabled', true)
            .datepicker('option', 'disabled', false);
    });

    var $anchors = $('a.anchor');

    $anchors.on('click', function (e) {

        if (this.hash !== "") {
            e.preventDefault();

            var hash = this.hash;

            $('html, body').animate({
                scrollTop: $(hash).offset().top
            }, 300, function(){
                //window.location.hash = hash;
            });
        }
    });

});

function scrollPrevent(prevent) {

    var $html = $('html') ;

    if(prevent) {

        var curScrollTop = $(window).scrollTop();
        $html.addClass('noscroll').css('top', '-' + curScrollTop + 'px');

    } else {

        var newScrollPos = Math.abs(parseInt($html.css('top')));

        $html.removeClass('noscroll');
        $html.css('top', 'auto');
        $(window).scrollTop(newScrollPos);
    }
}

function paPopupOpen(open) {

    var $popup = $('.pa-popup-wrap');

    if(open) {

        // if(window.screenSize < 768) {
        //
        //     console.log('mobile open');
        //
        //     $('body').css('height', window.innerHeight + 'px');
        // }

        $popup.addClass('active');
        setTimeout(function () {
            $popup.addClass('show');
            scrollPrevent(true);
        }, 100);

    } else {

        // if(window.screenSize < 768) {
        //
        //     console.log('mobile close');
        //
        //     $('body').css('height', '100%');
        // }

        $popup.removeClass('show');
        setTimeout(function () {
            $popup.removeClass('active');
            scrollPrevent(false);
        }, 600);
    }
}

// covid banner close handler

$(document).ready(function () {

    var $close = $('.about-covid-test .popup-close, .about-covid-test .link-close');

    if($close.length) {

        $close.on('click', function(e) {
            $('.about-covid-test, .about-covid-test-overlay').hide();
        });
    }
});

$(document).ready(function () {

    // check if speech recognition api available

    var speechApi = window.speechRecognition || window.webkitSpeechRecognition;
    window.voiceInputAvailable = !!speechApi;


    // hidden info show/hide

    $('.showInfoIcon').off('click').on('click', function (e) {

        let infoData = $(this).data('show')

        if(!infoData.length) {
            return false
        }

        let $target = $('[data-info="' + infoData + '"]')

        if($target.length) {

            if($target.is(':visible')) {
                $target.hide()
            } else {
                $target.show()
            }
        }
    })
});

function closePupup()
{
    $('.popup_bg').remove();
    $('.popup').remove();
}

$(document).ready(function () {

    // quick filters open-close

    let $quickFilters = $('.quickFilters'),
        $filterBtn = $('.filterBtn')

    if($quickFilters.length > 0 && $filterBtn.length > 0) {

        $filterBtn.on('click', function () {

            if($quickFilters.hasClass('open')) {
                $quickFilters.removeClass('open')
                $filterBtn.removeClass('open')
            } else {
                $quickFilters.addClass('open')
                $filterBtn.addClass('open')
            }
        })
    }

    // profile menu open-close

    let $profileMenu = $('.profileTopLine .profileMenu')

    if($profileMenu.length) {

        let $profileMenuTrigger = $profileMenu.find('.triggerMenu')

        $(document).on('click', function (e) {

            let $target = $(e.target)

            if($target.hasClass('triggerMenu') || $target.parents('.triggerMenu').length) {

                $profileMenu.toggleClass('open')

            } else if($profileMenu.hasClass('open') && !$target.parents('.profileMenu').length) {

                $profileMenu.removeClass('open')
            }
        })
    }

    let $acceptCookiesBlock = $('.acceptCookiesBlock');

    if($acceptCookiesBlock.length) {

        let $acceptCookiesBtn = $('#accept_pa_cookie_btn');
        let $consentCheckboxes = $('.consent_checkbox');
        let $showCookiePolicyLink = $('#showCookiePolicyLink')
        let $cookiePolicyPopup = $('.cookiePolicyPopup')
        let $cookiePolicyPopupClose = $('.cookiePolicyPopupClose')

        $acceptCookiesBtn.attr('disabled', true);

        $consentCheckboxes.on('change', function (e) {

            let $main = $('input[name="consent_main"]')
            let $add = $('input[name="consent_additional"]')

            if(this.name === 'consent_main' && !$main.is(':checked') && $add.is(':checked')) {
                $add.attr('checked', false).parents('label').removeClass('checked')
            }

            if(this.name === 'consent_additional' && $add.is(':checked') && !$main.is(':checked')) {
                // $main.attr('checked', true).parents('label').addClass('checked')
                $main.click()
            }

            $acceptCookiesBtn.attr('disabled', !$main.is(':checked'))
        })

        $showCookiePolicyLink.on('click', function() {

            //
            $cookiePolicyPopup.show()

            let $cookiePolicyContent = $cookiePolicyPopup.find('.cookiePolicyPopupContent')

            console.log($cookiePolicyContent)

            let sendData = {
                pageUrl: '/sikdatnu-politika/',
                pageLang: $cookiePolicyPopup.find('#pageLang').val(),
            }

            ajaxRequest('/content/getPageContent/', sendData, function(data) {

                console.log('getPageContent result')
                console.log(data)

                if(data.success) {

                    console.log(data.content)
                    console.log(data.success)

                    $cookiePolicyPopup.find('.cookiePolicyPopupContent').html(data.content)

                } else {

                    $cookiePolicyPopup.find('.cookiePolicyPopupContent').html('<h2>No content...</h2>')
                }
            });
        })

        $cookiePolicyPopupClose.on('click', function () {
            $cookiePolicyPopup.hide()
        })
    }

    // menu lang at top handler
    let $langArrow = document.getElementById('langArrow')
    let $langShow = document.getElementById('langShow')

    if($langArrow && $langShow) {

        document.addEventListener("click", function (e) {

            if(e.target.id === 'langArrow') {

                if ($langShow.style.display === 'none') {
                    $langShow.style.display = 'block';
                } else {
                    $langShow.style.display = 'none';
                }
            } else if (e.target.id !== 'langShow_popup') {

                if ($langShow.style.display === 'block') {
                    $langShow.style.display = 'none';
                }
            }
        });

        // menu lang in popup handler
        let $langArrow_popup = document.getElementById('langArrow_popup')
        let $langShow_popup = document.getElementById('langShow_popup')

        if($langArrow_popup && $langShow_popup) {

            document.addEventListener("click", function (e) {

                if(e.target.id === 'langArrow_popup') {

                    if ($langShow_popup.style.display === 'none') {
                        $langShow_popup.style.display = 'block';
                    } else {
                        $langShow_popup.style.display = 'none';
                    }
                } else if (e.target.id !== 'langShow_popup') {

                    if ($langShow_popup.style.display === 'block') {
                        $langShow_popup.style.display = 'none';
                    }
                }
            });
        }
    }


    let dmssAuthLink = document.querySelector('.dmssAuthLink');

    if(dmssAuthLink) {

        dmssAuthLink.addEventListener('click', function (e) {

            e.stopImmediatePropagation();
            e.preventDefault();

            let sendData = {
                url: this.href,
            }

            ajaxRequest('/content/dmssLink/', sendData, function(data) {

                if(data.success && data.location) {

                    location.href = data.location

                } else {

                    console.log('Error')
                    console.log(data)
                }
            });
        })
    }
})


    /*
    *
    *  Moving inline handlers to js file (due to CSP we shouldn't use the inline handlers as 'onclick', 'onchange' etc.)
    *
    * */

    $(document).on('click', '.openRes', function(e) {

        e.preventDefault()

        let resId = $(this).data().resid

        if(resId) {

            profile.openReservation(resId)
        }
    });


    $(document).on('change', '.filterRes', function (e) {
        $('#resForm').submit()
    })

    $(document).on('click', '.pdfReservation', function (e) {

        e.preventDefault()

        let resId = $(this).data().resid

        if(resId) {

            profile.pdfReservation(resId)
        }
    })

    $(document).on('click', '.cancelReservationPopup', function (e) {

        e.preventDefault()

        let resId = $(this).data().resid

        if(resId) {

            profile.cancelReservationPopup(resId)
        }
    })

    $(document).on('click', '.cancelReservation', function (e) {

        e.preventDefault()

        let resId = $(this).data().resid

        if(resId) {

            profile.cancelReservation(resId)
        }
    })

    $(document).on('click', '.deleteMultiMessages', function (e) {
        e.preventDefault()
        profile.deleteMultiMessages();
    })

    $(document).on('click', '.openMessage', function (e) {

        e.preventDefault()

        let mesId = $(this).data().mesid

        if(mesId) {

            profile.openMessage(mesId)
        }
    })

    $(document).on('click', '.deleteMessage', function (e) {

        e.preventDefault()

        let mesId = $(this).data().mesid

        if(mesId) {

            profile.deleteMessage(mesId)
        }
    })

    $(document).on('click', '.profileLogin', function (e) {
        e.preventDefault()
        profile.profileLogin()
    })

    $(document).on('click', '.setLang', function (e) {
        e.preventDefault()

        setLang(this)
    })

    $(document).on('click', '.confirmDeleteMessages', function (e) {
        e.preventDefault()
        profile.deleteMessages()
    })

    $(document).on('click', '.confirmDeleteMessagesCancel', function (e) {
        e.preventDefault()
        profile.deleteMessagesCancel()
    })

    $(document).on('click', '.deletePersonConfirm', function (e) {
        e.preventDefault()
        profile.deletePersonConfirm()
    })

    $(document).on('click', '.deletePersonCancel', function (e) {
        e.preventDefault()
        profile.deletePersonCancel()
    })

    $(document).on('click', '.closePopup', function (e) {
        e.preventDefault()
        closePupup()
    })

    $(document).on('click', '.deleteProfileConfirm', function (e) {
        e.preventDefault()
        profile.deleteProfileConfirm()
    })

    $(document).on('click', '.accreditationShowPopup', function (e) {
        e.preventDefault()
        profile.accreditationShowPopup(this)
    })

    $(document).on('click', '.profileClosePopup', function (e) {
        e.preventDefault()
        profile.closePopup()
    })

    $(document).on('click', '.accreditationContinue', function (e) {
        e.preventDefault()
        profile.accreditationContinue()
    })

    $(document).on('click', '.accreditationCancel', function (e) {
        e.preventDefault()
        profile.accreditationCancel()
    })

    $(document).on('click', '.deleteProfile', function (e) {
        e.preventDefault()
        profile.deleteProfile()
    })

    $(document).on('click', '.sessionTimeoutConfirmTrue', function (e) {
        e.preventDefault()
        profile.sessionTimeoutConfirm(true)
    })

    $(document).on('click', '.sessionTimeoutConfirmFalse', function (e) {
        e.preventDefault()
        profile.sessionTimeoutConfirm(false)
    })

    $(document).on('click', '.profileRegistrationCancel', function (e) {

        e.preventDefault()

        let userId = $(this).data().userid

        if(userId) {

            profile.profileRegistrationCancel(userId)
        }
    })

    $(document).on('click', '.registrationCancelConfirm', function (e) {

        e.preventDefault()

        let userId = $(this).data().userid

        if(userId) {

            profile.registrationCancelConfirm(userId)
        }
    })

    $(document).on('click', '.profileRegister', function (e) {
        e.preventDefault()
        profile.profileRegister(this)
    })

    $(document).on('click', '.addPerson', function (e) {
        e.preventDefault()
        profile.addPerson()
    })

    $(document).on('click', '.deletePerson', function (e) {

        e.preventDefault()

        let persId = $(this).data().persid

        if(persId) {

            profile.deletePerson(persId)
        }
    })

    $(document).on('click', '.passwordRecovery', function (e) {
        e.preventDefault()
        profile.passwordRecovery()
    })

    $(document).on('change', '.orderFilterFormSubmit', function (e) {
        $('#orderFilterForm').submit()
    })

    $(document).on('click', '.openOrder', function (e) {

        e.preventDefault()

        let ordId = $(this).data().ordid

        if(ordId) {

            profile.openOrder(ordId)
        }
    })

    $(document).on('click', '.openOrder', function (e) {

        e.preventDefault()

        let ordId = $(this).data().ordid

        if(ordId) {

            profile.openOrder(ordId)
        }
    })

    $(document).on('click', '.openInvoice', function (e) {

        e.preventDefault()

        let ordId = $(this).data().ordid

        if(ordId) {

            profile.openInvoice(ordId)
        }
    })

    $(document).on('click', '.openCons', function (e) {

        e.preventDefault()

        let consId = $(this).data().consid

        if(consId) {

            profile.openConsultation(consId)
        }
    })

    $(document).on('click', '.openInvoicePdf', function (e) {

        e.preventDefault()

        let ordId = $(this).data().ord

        if(ordId) {

            profile.openInvoicePdf(ordId)
        }
    })

    $(document).on('click', '.saveProfile', function (e) {
        e.preventDefault()
        profile.saveProfile()
    })

    $(document).on('click', '.removeDoctor', function (e) {

        e.preventDefault()

        let docId = $(this).data().docid
        let clId = $(this).data().clid

        if(docId && clId) {

            profile.removeDoctor(docId, clId)
        }
    })

    $(document).on('click', '.setNewPassword', function (e) {
        e.preventDefault()
        profile.setNewPassword()
    })

    $(document).on('click', '.showConfigurePopup', function (e) {
        e.preventDefault()
        tfa.showConfigurePopup()
    })

    $(document).on('click', '.cancelAddReservation', function (e) {
        e.preventDefault()
        profile.cancelAddReservation()
    })

    $(document).on('click', '.resendActivationLink', function (e) {
        e.preventDefault()
        profile.resendActivationLink()
    })

    $(document).on('click', '.spSearch', function (e) {

        e.preventDefault()

        let url = $(this).data().url

        if(url) {

            spSearch(url)
        }
    })

    $(document).on('click', '.pdfCoupon', function (e) {

        e.preventDefault()

        let coupId = $(this).data().coupid

        if(coupId) {

            profile.pdfCoupon(coupId)
        }
    })

    $(document).on('click', '.filterReservationCalendar', function (e) {

        e.preventDefault()

        let date = $(this).data().date
        let action = $(this).data().action

        if(!action) {
            action = '';
        }

        if(date) {

            profile.filterReservationCalendar(action, date)
        }
    })

    $(document).on('click', '.reloadCaptcha', function (e) {

        e.preventDefault()

        document.getElementById('captcha').src = '/securimage/securimage_show.php?' + Math.random()
        return false
    })

    $(document).on('click', '.paPopupOpenTrue', function (e) {

        e.preventDefault()

        paPopupOpen(true)
    })

    $(document).on('click', '.paPopupOpenFalse', function (e) {

        e.preventDefault()

        paPopupOpen(false)
    })
