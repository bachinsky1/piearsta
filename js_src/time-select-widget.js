;
/**
 *
 * PIEARSTA LV - TimeSelect Widget
 *
 * @package		Piearsta.lv
 * @author		Andrey Voroshnin <andrejs.vorosnins@bb-tech.eu>
 * @copyright	Copyright (c) 2020, BBTech.
 * @version		1
 *
 */

/**
 * TS widget constructor
 *
 * @param el
 * @param clinicId
 * @param doctorId
 * @param startDate
 * @param services
 * @param selServiceId
 * @returns {*[]}
 * @constructor
 */
var TimeSelect = function (el, clinicId, doctorId, startDate = null, services = null, selServiceId = null) {

    // widget cannot be initialized without DOM-node
    if(!el) {
        return [];
    }

    // Unique widget instance ID

    this.id = 'ts_' + Math.floor(Math.random() * 100) + '_' + Date.now();

    // Register widget instance to global site object storage

    if(!window.paObjects) {
        window.paObjects = {};
    }

    window.paObjects[this.id] = {
        type: 'timeSelectWidget',
        element: el,
        initTime: Date.now(),
    };

    // set instance ID to root element data attribute

    el.dataset.tsId = this.id;

    // if(typeof services === 'string') {
    //
    // }
    //
    // if( services && ( typeof services !== 'object' || !services.hasOwnProperty('length') || services.length <= 0) ) {
    //     services = null;
    // }

    // set widget container position to relative

    $(el).css({position: 'relative'});



    /*   ******   PROPERTIES     ******   */

    this.element = el;
    this.startDate = startDate || new Date().toISOString();
    this.clinicId = clinicId;
    this.doctorId = doctorId;
    this.services = services;
    this.timeSelected = null;
    this.widgetHeight = null;

    // default options
    this.opts = {
        title: 'Time select widget',
        days: 7,
        times: 5,
        multiple: false, // if true -- widget works as part of group (several doctors)
        showSpinnerWhenLoading: true,
        showEmptyDates: false,
        showMonth: true,
        showDatepicker: true,
        increment: 4,
        animationTime: 300,
        dataUrl: '/doctors/getCalendarData/',
    };

    if(window.site && window.site.timeSelectWidget && window.site.timeSelectWidget.header) {
        this.opts.title = window.site.timeSelectWidget.header;
    }

    // latvian abrr. of weekdays
    this.weekDays = [
        'Svēt.',
        'Prm.',
        'Otr.',
        'Tr.',
        'Cet.',
        'Pkt.',
        'Sest.',
    ];

    // latvian month names
    this.months = [
        'Janvāris',
        'Februāris',
        'Marts',
        'Aprīlis',
        'Maijs',
        'Jūnijs',
        'Jūlijs',
        'Augusts',
        'Septembris',
        'Oktobris',
        'Novembris',
        'Decembris'
    ];

    // latvian abbr. month names
    this.monthsAbbr = [
        'Jan',
        'Feb',
        'Mar',
        'Apr',
        'Mai',
        'Jūn',
        'Jūl',
        'Aug',
        'Sep',
        'Okt',
        'Nov',
        'Dec'
    ];


    // data object ( like: {string day '' : array of available times}} )
    this.data = null;


    /*   ******   Local props shortcuts     ******   */

    let context = this;
    let element = context.element;
    let opts = context.opts;
    let data = context.data;
    let schedule = null;
    let wd = context.weekDays;
    let months = context.months;


    /*   ******   Local vars     ******   */

    let localStartDate = this.startDate;
    let requestInProgress = false;
    let spinnerShown = false;
    let selectedTimeVisible = false;



    /*   ******   PUBLIC METHODS     ******   */

    /**
     * Set new options, override defaults
     *
     * @param newOpts
     * @returns {boolean}
     */
    this.setOptions = function (newOpts) {

        if(typeof newOpts !== 'object') {
            console.warn('Options should be an object.');
            return false;
        }

        for ( let opt in newOpts ) {

            if(newOpts.hasOwnProperty(opt)) {

                opts[opt] = newOpts[opt];
            }
        }
    };

    /**
     * Set passed services, get new data and render timetable
     *
     * @param services
     * @returns {boolean}
     */
    this.setServices = function (services) {

        if(!services) {
            return false;
        }

        if(typeof services === 'object' && services.length) {
            context.services = services;
        } else {
            context.services = [services];
        }

        getData();
    };

    /**
     * Create widget html structure after init
     */
    this.createStructure = function () {

        let topBlock = '';

        if(opts.showDatepicker || opts.title) {

            let title = '';

            if(opts.title) {
                title = "<span class='ts-title'>" + opts.title + "</span>";
            }

            let dp = '';

            if(opts.showDatepicker) {
                dp = "<a href='javascript:;' class='btnw ts-calendar-trigger calendar-trigger'>" +
                    "<i class='fa fa-calendar'></i><span class='text'>Kalendārs</span>" +
                    "</a><input type='text' class='ts-jqui-calendar' readonly>";
            }

            topBlock = "<div class='ts-top-block'>"+ title + dp +
                "</div>";
        }

        let top = "<div class='ts-container'>" + topBlock +
            "<div class='ts-prev'><i class='fa fa-angle-left'></i></div><div class='ts-inner'><div class='ts-inner-timetable'>";

        // add spinner template
        let spinnerTmpl = '<div class="ts-spinner-container-calendar">' +
            '<div class="ts-spinner-content">' +
            '<div class="ts-spinner-content-inner">' +
            '<div class="ts-spinner-msg">Lūdzu uzgaidiet …</div>' +
            '<div class="ts-loader"></div>' +
            '</div>' +
            '</div>' +
            '</div>';

        let bottom = "</div>" + spinnerTmpl + "</div><div class='ts-next'><i class='fa fa-angle-right'></i></div>";

        let infoMessage = '';

        bottom = infoMessage + bottom;

        // empty template of day
        let middle = '';
        // construct days
        middle += "<div class='ts-day' data-day='' data-month=''>";
        middle += "<div class='ts-day-cell empty'><span class='ts-day-date'><span>&nbsp;</span></span><span class='ts-day-weekday'>&nbsp;</span></div>";
        middle += "<div class='ts-time-container'><div class='ts-arrow-up disabled'><i class='fa fa-angle-up'></i></div>";

        // construct times
        middle += "<div class='ts-times'><div class='ts-times-inner'>";
        middle += "<div class='ts-timeslot ts-invisible' data-time=''>&nbsp;</div>";
        middle += "</div></div>";

        // days close
        middle += "<div class='ts-arrow-down disabled'><i class='fa fa-angle-down'></i></div></div>";
        middle += "</div>";

        $(element).html('');
        $(element).html(top + middle + bottom);

        let timeslotH = Math.floor($(element).find('.ts-timeslot').outerHeight(true)) - Math.floor(parseInt($(element).find('.ts-timeslot').css('margin-top')));
        let  timesH = opts.times * timeslotH + Math.floor(parseInt($(element).find('.ts-timeslot').css('margin-top')));
        let  dayW = $(element).find('.ts-inner').innerWidth() / opts.days;
        let  $days = $(element).find('.ts-day');
        let dayHeaderHeight = $(element).find('.ts-day-cell').outerHeight(true);

        $(element).find('.ts-day').css('width', dayW + 'px');
        $(element).find('.ts-times').height(timesH);
        $(element).find('.ts-inner').height($days.outerHeight());
        $(element).find('.ts-prev, .ts-next').height($(element).find('.ts-day-cell').outerHeight(true));
        $(element).find('.ts-info').css('margin-top', dayHeaderHeight/2 + 'px');

        let topBlockHeight = $(element).find('.ts-top-block').outerHeight(true);
        context.widgetHeight = $(element).find('.ts-inner').outerHeight(true);
        $(element).find('.ts-container').css( 'height', (context.widgetHeight + topBlockHeight) + 'px' );
    };

    /**
     * init widget
     */
    this.init = function () {
        context.createStructure();
        getData();
    };

    /**
     * Render widget timetable
     *
     * @param callback
     */
    this.render = function (callback = null) {

        selectedTimeVisible = false;

        let currDate = new Date();

        let month = '' + (currDate.getUTCMonth() + 1);
        let currDay = '' + currDate.getDate();
        month = month.length === 1 ? '0' + month : month;
        currDay = currDay.length === 1 ? '0' + currDay : currDay;
        let today = new Date().getFullYear() + '-' + month + '-' + currDay;

        let middle = '';

        let i = 0;
        let monthNumbers = {};
        let prevMonth = null;

        for ( let day in schedule ) {

            if(schedule.hasOwnProperty(day)) {

                i++;

                let dateObj = new Date(day);
                let date = dateObj.getDate();
                let month = months[dateObj.getMonth()];
                let weekDay = wd[dateObj.getDay()];
                let weekendClass = (dateObj.getDay() === 0 || dateObj.getDay() === 6) ? ' weekend ' : '';

                let newMonth = prevMonth ? month !== prevMonth : true;
                prevMonth = month;

                let monthStartClass = newMonth ? ' ts-month-start ' : '';

                if(newMonth) {
                    monthNumbers[dateObj.getMonth()] = 1;
                } else {
                    monthNumbers[dateObj.getMonth()]++;
                }

                let todayClass = day === today ? ' today ' : '';

                // construct days
                middle += "<div class='ts-day" + monthStartClass + weekendClass + todayClass + "' data-day='" + day + "' data-month='" + dateObj.getMonth() + "'>";
                middle += "<div class='ts-day-cell'><span class='ts-day-date'>" + date + ".<span>" + context.monthsAbbr[dateObj.getMonth()] + "</span></span><span class='ts-day-weekday'>" + weekDay + "</span></div>";
                middle += "<div class='ts-time-container'><div class='ts-arrow-up disabled'><i class='fa fa-angle-up'></i></div>";

                // construct times
                middle += "<div class='ts-times'><div class='ts-times-inner'>";

                let times = Object.values(schedule[day]);
                let disabledDownArrow = (times.length <= opts.times) ? ' disabled ' : '';

                for (let j in times) {

                    if(times.hasOwnProperty(j)) {

                        let selectedClass = '';

                        let scheduleId = times[j]['id'];
                        scheduleId = scheduleId ? ' data-schedule-id="' + scheduleId + '" ' : '';

                        if(context.timeSelected) {

                            let tString = day + ' ' + times[j]['time_start'];

                            if(tString === context.timeSelected) {

                                selectedClass =  ' ts-selected ';
                                selectedTimeVisible = true;

                            } else {

                                selectedClass =  '';
                            }
                        }

                        let paymentTypeClass = times[j]['payment_type'] === '1' ? ' green ' :  ' blue ';

                        let price = '';

                        if(times[j]['servicesPriceInfo'] && times[j]['servicesPriceInfo'][selServiceId]) {
                            price = ' data-price="' + times[j]['servicesPriceInfo'][selServiceId] + '"';
                        }

                        middle += "<div class='ts-timeslot" + selectedClass + paymentTypeClass + "' data-time='" +
                            times[j]['time_start'] + "'" + scheduleId + price + ">" + times[j]['time_start'] + "</div>";
                    }
                }

                if(times.length < 1) {
                    middle += "<div class='ts-timeslot ts-invisible' data-time=''>&nbsp;</div>";
                }

                middle += "</div></div>";

                // days close
                middle += "<div class='ts-arrow-down" + disabledDownArrow + "'><i class='fa fa-angle-down'></i></div></div>";
                middle += "</div>";
            }
        }

        // add empty days if not enough data
        if(i < opts.days) {

            let difference = opts.days - i;

            for(let j = 0; j < difference; j++) {

                // construct days
                middle += "<div class='ts-day' data-day='' data-month=''>";
                middle += "<div class='ts-day-cell empty'><span class='ts-day-date'><span>&nbsp;</span></span><span class='ts-day-weekday'>&nbsp;</span></div>";
                middle += "<div class='ts-time-container'><div class='ts-arrow-up disabled'><i class='fa fa-angle-up'></i></div>";

                // construct times
                middle += "<div class='ts-times'><div class='ts-times-inner'>";
                middle += "<div class='ts-timeslot ts-invisible' data-time=''>&nbsp;</div>";
                middle += "</div></div>";

                // days close
                middle += "<div class='ts-arrow-down disabled'><i class='fa fa-angle-down'></i></div></div>";
                middle += "</div>";
            }
        }

        if(data.nearest || schedule.length < 1) {

            // set info message

            // construct message

            let messageHtml = '<p>' + messages.calendarMessages.empty + '</p>';

            if(messages && messages.calendarMessages && data.nearest.status) {

                switch (data.nearest.status) {
                    case 'empty':
                        messageHtml = '<p>' + messages.calendarMessages.empty + '</p>';
                        break;
                    case 'busy':
                        messageHtml = '<p>' + messages.calendarMessages.busy + '</p>';
                        break;
                    case 'later':
                        messageHtml = '<p>' + messages.calendarMessages.later['1'];
                        messageHtml += ' ' + data.nearest.displayDate + ' ';
                        messageHtml += messages.calendarMessages.later['2'] + '</p>';
                        break;
                    case 'nearest':
                        messageHtml = '<p>' + messages.calendarMessages.nearest['1'] + ' ' + data.nearest.displayDate + '</p>';
                        messageHtml += '<p><a class="ts-nearest-link" data-date="' + data.nearest.date + '">' + messages.calendarMessages.nearest['2'] + '</a></p>';
                        break;
                    default:
                        messageHtml = '<p>' + messages.calendarMessages.empty + '</p>';
                        break;
                }
            }

            messageHtml = '<div class="ts-info"><div class="ts-info-inner">' + messageHtml + '</div></div>';

            middle += messageHtml;
        }

        $('.ts-inner-timetable').html(middle);

        // set sizes

        let timeslotH = Math.floor($(element).find('.ts-timeslot').outerHeight(true)) - Math.floor(parseInt($(element).find('.ts-timeslot').css('margin-top')));
        let  timesH = opts.times * timeslotH + Math.floor(parseInt($(element).find('.ts-timeslot').css('margin-top')));
        let  dayW = $(element).find('.ts-inner').innerWidth() / opts.days;
        let  $days = $(element).find('.ts-day');
        let dayHeaderHeight = $(element).find('.ts-day-cell').outerHeight(true);

        $(element).find('.ts-day').css('width', dayW + 'px');
        $(element).find('.ts-times').height(timesH);
        $(element).find('.ts-inner').height($days.outerHeight());
        $(element).find('.ts-prev, .ts-next').height($(element).find('.ts-day-cell').outerHeight(true));
        $(element).find('.ts-info').css('margin-top', dayHeaderHeight/2 + 'px');


        // disable/enable arrows

        $(element).find('.ts-prev').toggleClass('disabled', !data.prev);
        $(element).find('.ts-next').toggleClass('disabled', !data.next);

        // init datepicker if set to true

        if(opts.showDatepicker) {

            let dpEl = $(element).find('.ts-jqui-calendar');

            setDatapicker(dpEl, function (dateSelected) {
                context.goto(dateSelected);
            });

            if(data.lastDate) {
                dpEl.datepicker( 'option', 'maxDate', new Date(data.lastDate) );
            }

            $(element).find('.ts-calendar-trigger').off('click').on('click', function (e) {

                e.preventDefault()
                dpEl.datepicker('show');

                $(window).on('scroll', function () {
                    dpEl.datepicker('hide');
                });
            });
        }

        // re-init all widget events

        setEvents();

        if(selectedTimeVisible) {
            scrollToSelectedTime();
        }

        if(callback && typeof callback === 'function') {
            callback();
        }
    };

    /**
     * Go to next interval
     *
     * @returns {boolean}
     */
    this.next = function () {

        if(!data.next) {
            return false;
        }

        let lastDate = Object.keys(schedule)[Object.keys(schedule).length - 1];
        let d = new Date(lastDate);
        let newStartDate = new Date(d.getTime() + (24 * 60 * 60 * 1000));
        context.startDate = newStartDate.toISOString();
        getData('next');
    };

    /**
     * Go to previous interval
     *
     * @returns {boolean}
     */
    this.prev = function () {

        if(!data.prev) {
            return false;
        }

        let firstDate = Object.keys(schedule)[0];
        let d = new Date(firstDate);
        let newStartDate = new Date(d.getTime() - (24 * 60 * 60 * 1000));
        context.startDate = newStartDate.toISOString();
        getData('prev');
    };

    /**
     * Go to passed date
     *
     * @param date
     */
    this.goto = function (date) {
        let d = new Date(date);
        context.startDate = d.toISOString();
        getData('goto');
    };

    /**
     * Unselects time and re-renders timetable
     */
    this.unselect = function () {

        if(context.timeSelected) {
            context.timeSelected = null;
            selectedTimeVisible = false;
            context.render();
            $(element).trigger('ts.unselect');
        }
    };

    /**
     * Selects passed time and re-renders timetable
     */
    this.select = function (date_time) {
        context.timeSelected = date_time;

        let date = date_time.split(' ')[0];

        selectedTimeVisible = false;
        context.goto(date);

        setTimeout(function () {

            triggerSelectEvent($(element).find('.ts-selected'), date_time);

        }, 600);
    };


    /*   ******   PRIVATE METHODS     ******   */

    /**
     * Set event handlers
     */
    function setEvents() {

        // nearest link
        $(element).find('.ts-nearest-link').off('click').on('click', function (e) {
            e.preventDefault();
            let date = $(this).data('date');
            context.goto(date);
        });

        // prev button
        $(element).find('.ts-prev').off('click').on('click', function () {
            context.prev();
        });

        // next button
        $(element).find('.ts-next').off('click').on('click', function () {
            context.next();
        });

        // Up arrow
        $(element).find('.ts-arrow-up').off('click').on('click', function () {

            let $this = $(this);

            if($this.hasClass('disabled')) {
                return false;
            }

            let $timesInner = $this.parents('.ts-time-container').find('.ts-times-inner');

            $timesInner.stop(true, true);

            setTimeout(function () {

                let timeslotH = $timesInner.find('.ts-timeslot').outerHeight(true);
                let currTop = parseInt($timesInner.css('margin-top'));
                let increment = opts.increment * ( timeslotH - parseInt($timesInner.find('.ts-timeslot').css('margin-top')) );

                $timesInner.animate({
                    marginTop: (currTop + increment) + 'px',
                }, opts.animationTime);

                $this.parents('.ts-time-container').find('.ts-arrow-down').removeClass('disabled');

                if( (currTop + increment) >= 0 ) {
                    $this.addClass('disabled');
                }

            }, 50);


        });

        // Down arrow
        $(element).find('.ts-arrow-down').off('click').on('click', function () {

            let $this = $(this);

            if($this.hasClass('disabled')) {
                return false;
            }

            let $timesInner = $this.parents('.ts-time-container').find('.ts-times-inner');

            $timesInner.stop(true, true);

            setTimeout(function () {

                let timesInnerH = $timesInner.outerHeight();
                let timesH = $this.parents('.ts-time-container').find('.ts-times').outerHeight();
                let timeslotH = $timesInner.find('.ts-timeslot').outerHeight(true);
                let currTop = parseInt($timesInner.css('margin-top'));
                let increment = opts.increment * ( timeslotH - parseInt($timesInner.find('.ts-timeslot').css('margin-top')) );
                let maxDownShift = (timesInnerH - timesH) +
                    parseInt($timesInner.find('.ts-timeslot').css('margin-top')) +
                    parseInt($timesInner.find('.ts-timeslot').css('margin-bottom'));

                $timesInner.animate({
                    marginTop: (currTop - increment) + 'px',
                }, opts.animationTime);

                $this.parents('.ts-time-container').find('.ts-arrow-up').removeClass('disabled');

                if( Math.abs(parseInt($timesInner.css('margin-top'))) >= (timesInnerH - timesH - increment) ) {

                    $this.addClass('disabled');
                }

            }, 50);

        });

        // time select handler
        $(element).find('.ts-timeslot').off('click').on('click', function () {

            if($(this).hasClass('ts-invisible')) {
                return false;
            }

            let $this = $(this),
                time = $this.text(),
                price = $this.data('price') ? $this.data('price') : null,
                fullDT = $this.parents('.ts-day').data('day') + ' ' + time;

            // UNSELECT !
            if($this.hasClass('ts-selected')) {

                $this.removeClass('ts-selected');
                context.timeSelected = null;

                // trigger event

                $(element).trigger('ts.unselect');


            // SELECT !
            } else {

                $(element).find('.ts-selected').removeClass('ts-selected');
                $this.addClass('ts-selected');
                context.timeSelected = fullDT;

                triggerSelectEvent($this, fullDT, price ? price : null);
            }
        });
    }

    /**
     * * Trigger ts.select event
     *
     * @param $slot
     * @param fullDT
     * @param price
     */
    function triggerSelectEvent($slot, fullDT, price = null) {

        let dtObj = new Date(fullDT);

        let selectedTime = {
            scheduleId: $slot.data('scheduleId'),
            string: fullDT.substring(0, 10),
            date: dtObj.getDate(),
            day: dtObj.getDay(),
            dayStr: context.weekDays[dtObj.getDay()],
            month: dtObj.getMonth(),
            monthStr: context.months[dtObj.getMonth()],
            year: dtObj.getFullYear(),
            time: {
                string: dtObj.toTimeString().substring(0, 5),
                timestamp: dtObj.getTime(),
                hours: dtObj.getHours(),
                minutes: dtObj.getMinutes(),
                seconds: dtObj.getSeconds(),
            }
        };

        let evObj = {
            selectedTime: selectedTime,
        };

        if(price) {
            evObj.price = price;
        }

        $(element).trigger('ts.select', evObj);
    }

    /**
     * Get data from backend and render new timetable
     *
     * @param action
     */
    function getData(action = null) {

        if(!opts.multiple) {
            requestInProgress = true;
            showSpinner(true);
        }

        action = action || 'goto';

        let sendData = {};
        sendData['action'] = action;
        sendData['doctorId'] = doctorId;
        sendData['clinicId'] = clinicId;

        if(services) {
            sendData['doctors_filters'] = {
                main: {
                    doctors_filter_services: context.services,
                }
            };
        }

        let d = new Date(context.startDate);

        sendData['startDate'] = d.toISOString();
        sendData['days'] = opts.days;
        sendData['showEmptyDates'] = opts.showEmptyDates ? '1' : "0";

        ajaxRequest(opts.dataUrl, sendData, function( result ) {

             data = result;
             schedule = result.data;

             if(data.length < opts.days) {
                 opts.days = data.length;
             }

             context.render();

            if(!opts.multiple) {
                requestInProgress = false;
                showSpinner(false);
            }
        });
    }

    /**
     * Show / hide spinner
     *
     * @param on
     */
    function showSpinner(on) {

        if(on) {

            setTimeout(function () {

                if(requestInProgress) {
                    spinnerShown = true;
                    $(element).find('.ts-spinner-container-calendar').fadeIn(400);
                }

            }, 1000);

        } else {

            if(spinnerShown) {
                spinnerShown = false;
                $(element).find('.ts-spinner-container-calendar').fadeOut(400);
            }
        }
    }

    /**
     *  Scrolls times in day container to make selected time visible
     */
    function scrollToSelectedTime() {

        if(!selectedTimeVisible) {
            return false;
        }

        let selTimeObj = context.timeSelected.split(' ');
        let $selTS = $(element).find('.ts-selected');
        let $timesTimes = $selTS.parents('.ts-times');
        let $timesInner = $selTS.parents('.ts-times-inner');
        let topOfTimes = $timesTimes.offset().top;
        let heightOfTimes = $timesTimes.innerHeight();
        let bottomOfTimes = topOfTimes + heightOfTimes;
        let topOfTS = $selTS.offset().top;
        let bottomOfTS = topOfTS + $selTS.outerHeight(true);
        let isVisible = (topOfTS >= topOfTimes && bottomOfTS <= bottomOfTimes);

        if(!isVisible) {
            $timesInner.css('margin-top', ( bottomOfTimes - bottomOfTS ) + 'px' );
            $selTS.parents('.ts-day').find('.ts-arrow-up').removeClass('disabled');
        }
    }


    /** Exec on creating widget instance **/

    // GO!
    context.init();
};