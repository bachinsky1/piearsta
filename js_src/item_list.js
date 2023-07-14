;
/**
 *
 * PIEARSTA LV - Profile items list loads data by ajax
 *
 * @package		Piearsta.lv
 * @author		Andrey Voroshnin <andrejs.vorosnins@bb-tech.eu>
 * @copyright	Copyright (c) 2020, BBTech.
 * @version		1
 *
 */

/**
 * List object constructor
 * @constructor
 */
var List = function (entity, url) {

    this.ajaxUrl = url;
    this.entity = entity;
    this.itemCount = $('.item-count span');
    this.cont = $('.item_list_cont');
    this.more = $('.more');
    this.currentPage = 0;
    this.params = null;

    let context = this;

    let $count = context.itemCount;
    let $cont = context.cont;
    let $more = context.more;

    this.manageControlCheckboxes = function () {

        let $checkBoxes = $('input.messages'),
            $messageAll = $('input.messagesAll');

        let checkState = function () {

            if($checkBoxes.filter('input:checked').length) {
                if($checkBoxes.filter('input:checked').length === $checkBoxes.length) {
                    $messageAll.prop('checked', true);
                    $messageAll.closest('.item').removeClass('indetermined').addClass('checked');
                } else {
                    $messageAll.prop('checked', false);
                    $messageAll.closest('.item').removeClass('checked').addClass('indetermined');
                }
            } else {
                $messageAll.prop('checked', false);
                $messageAll.closest('.item').removeClass('checked indetermined');
            }
        };

        $checkBoxes.off('change').on('change', function () {
            checkState();
        });

        $messageAll.off('change').on('change', function () {

            let $this = $(this);

            setTimeout(function () {

                if($this.closest('.item').hasClass('checked') || $this.closest('.item').hasClass('indetermined')) {
                    $checkBoxes.each(function (box) {
                        let $box = $($checkBoxes.eq(box));
                        $box.prop('checked', true);
                        $box.closest('.item').addClass('checked');
                    });
                    $this.prop('checked', true);
                    $this.closest('.item').removeClass('indetermined').addClass('checked');
                } else {
                    $checkBoxes.each(function (box) {
                        let $box = $($checkBoxes.eq(box));
                        $box.prop('checked', false);
                        $box.closest('.item').removeClass('checked');
                    });
                    $this.prop('checked', false);
                    $this.closest('.item').removeClass('checked indetermined');
                }

            }, 1);
        });

        // init
        checkState();
    };

    this.load = function () {

        context.currentPage++;

        let params = context.params,
            person_id = params['person_id'] ? params['person_id'] : 'all',
            status = params['status'] ? parseInt(params['status']) : 2;

        sendData = {
            ajax: '1',
            page: context.currentPage,
            person_id: person_id,
            status: status,
        };

        ajaxRequest(context.ajaxUrl, sendData, function(data) {

            if(data.html) {

                let addHeight = 0;

                $(data.html).appendTo($cont);
                $more.toggle(data.showMore);
                $count.html(data.count);

                // $.each($cont.find('.table_line'), function (n, el) {
                //     addHeight += $(el).outerHeight();
                // });
                //
                // $cont.css('height', addHeight + 'px');

                if(context.currentPage > 1) {

                    setTimeout(function () {

                        let scr = $cont.find('#r_' + data.items[0].id).offset().top;
                        $('body, html').animate({scrollTop: scr}, 300);

                    }, 300);
                }

                context.manageControlCheckboxes();

                $cont.find('.darbibas').off('click').on('click', function(e) {

                    e.stopPropagation();
                    profile.initConsultationActionLink(this);
                });
            }
        });
    };

    function init() {

        $more.on('click', function () {
            context.load();
        });

        // load first portion of data on init
        context.params = parseQuery(location.search);
        context.load();
    }

    init();
};

$(document).on('ready', function () {

    let $content = $('#innerContent');
    let entity = null;
    let url = null;

    if($content.length) {
        entity = $content.data('type');
        url = $content.data('url');
    }

    if(entity && url) {
        if(!window.paObjects) {
            window.paObjects = {};
        }

        if(!window.paObjects.lists) {
            window.paObjects.lists = {};
        }

        window.paObjects.lists[entity] = new List(entity, url);
    }
});
