var ajaxList = function () {

    var currentPage = 0,
        currentFilters = {},
        resetingFilters = false;

    var _config = {
        filters: {
            fast: '.controls',
            main: '.search'
        },
        content: '.table_body .list',
        result_count: '.result-count',
        showMore: '.show-more',
        search: '.search-btn',
        clear: '.collapsible .reset',
        list_type: '',
        ajaxUrl: false,
        currentPage: 0,
    };

    var config = {};

    var _load = function (_page, _filters) {

        var page = _page || 0,
            filters = _getFilterValues(),
            data = {},
            url,
            newUrl = url = window.location.href.replace(window.location.search, ''),
            search = [];

        currentFilters = filters;
        $(config.showMore).hide();

        if(page > 0) {
            search.push(_getDataFieldName('page') + '=' + page);
        }

        $.each(filters, function (index, el) {
            $.each(el, function (filter_name, filter_value) {

                // add to query string contents params only (i.e. searched text, not ids, false, 0 or empty strings)
                if(typeof filter_value === 'string' && filter_value.match(/\D/) != null && filter_value !== 'false' && filter_value !== '') {

                    search.push(filter_name + '=' + filter_value);
                }
            });
        });

        if (search.length) {
            newUrl += '?' + search.join('&');
        }

        data['ajax_search'] = true;
        data[_getDataFieldName('page')] = page;
        data[_getDataFieldName('filters')] = filters;

        if (typeof (profile.reservationsFilter) != 'undefined') {
            $.each(profile.reservationsFilter, function (index, value) {
                data[index] = value;
            });
        }

        // we add anyway remote_services filter
        // data['remote_services'] = $('[name="remote_services"]').is(':checked');

        // add subscription filter
        data['subscription'] = $('[name="subscription"]').is(':checked');
        // add dcDoctors filter
        data['dcDoctors'] = $('[name="dcDoctors"]').is(':checked');

        data['queryString'] = encodeURIComponent(search.join('&'));

        $.ajax({
            url: newUrl,
            data: data,
            dataType: 'json',
            method: 'POST',
            error: function (jqXHR, statusText) {
            },
            success: function (ajaxData) {

                profile.hideSpinner('calendar');

                if (!page || page === 0) {
                    $(config.content).html(ajaxData.content);
                    $(config.result_count).html(ajaxData.total);
                } else {
                    $(ajaxData.content).appendTo($(config.content));
                }

                if (ajaxData.show_more) {
                    $(config.showMore).show();
                }
                if (ajaxData.total === 0) {
                    $('.table_head').hide();
                } else {
                    $(config.filters.fast).show();
                    $('.table_head').show();
                }

                config.currentPage = page;
                ajaxData.content = $(config.content).html();
                window.history.pushState({data: data, ajax: ajaxData}, '', newUrl);

                doctors_list_arrows();
                doctors_list_line();
                doctors_table_head_fixed();
            }
        });
    };

    var _getDataFieldName = function (name) {
        return config.list_type && config.list_type !== '' && config.list_type + '_' + name || name;
    };

    var _loadNextPage = function () {

        var queryStr = location.search,
            currPage = 0;

        if(queryStr) {
            if(getParameterByName('doctors_page')) {
                currPage = parseInt(getParameterByName('doctors_page'));
            } else if (getParameterByName('clinics_page')) {
                currPage = parseInt(getParameterByName('clinics_page'));
            }
        }

        _load(++currPage, currentFilters);
    };

    var _getFilterValues = function () {

        var _filters = {};

        if (config.filters) {
            for (var i in config.filters) {
                _filters[i] = _getFilterValuesBySelector(config.filters[i]);
            }
        }

        return _filters;
    };

    var _getFilterValuesBySelector = function (selector) {

        if (!selector) {
            return {};
        }

        var _filters = {},
            hiddenElem = $(selector).find(':input[id=doctors_filter_constant_clinic]');

        $(selector).find(':input')/*.filter(':visible')*/.each(function () {

            if ($(this).hasClass(config.search.replace('.', ''))) {
                return true;
            }

            var value = $(this).val() !== $(this).data('default') && $(this).val() || false;

            if ($(this).attr('type') === 'checkbox' || $(this).attr('type') === 'radio') {
                value = $(this).prop('checked');
            }

            let filterName = $(this).attr('id') ?  $(this).attr('id') : $(this).attr('name')

            _filters[filterName] = value;
        });

        if (hiddenElem.length) {
            _filters['doctors_filter_clinic'] = hiddenElem.val();
        }

        return _filters;
    };

    var _addToFavorites = function (docId, clinicId, faved) {

        if (!signedIn) {
            window.location.href = signInPage;
        }

        if (null == docId || null == clinicId) return false;

        url = '/doctors/AddFav/';

        $.ajax({
            url: url,
            data: {
                add_to_fav: true,
                doc_id: docId,
                clinic_id: clinicId,
                faved: faved
            },
            // dataType: 'json',
            method: 'POST',
            success: function (data) {

                if (data.location) {
                    window.location = data.location;
                }
            },
            error: function (jqXHR, statusText) {
                console.log('FAILED ! ' + statusText, jqXHR);
            }
        });
    };

    var _setFiltersContent = function (filters) {

        //console.log('_setFiltersContent', filters);
        if (!filters) return;

        var mainFilterCount = 0,
            $mainFilter = $(config.filters.main),
            allFilters = _getFilterValues();

        for (var i in filters['main']) {

            if (filters['main'].hasOwnProperty(i)) {
                mainFilterCount++;
            }

            if (mainFilterCount > 1) break;
        }

        if (mainFilterCount > 1) {

            if (!$mainFilter.find('.advanced').hasClass('on')) {

                $mainFilter.find('.advanced').addClass('on');
                $mainFilter.find('.collapsible').show();
            }

        } else {

            $mainFilter.find('.advanced').removeClass('on');
            $mainFilter.find('.collapsible').hide();
        }

        $.each(allFilters, function (type, el) {

            $.each(el, function (filter_name, filter_value) {

                var $element = $(config.filters[type] + ' #' + filter_name);

                var value = filters[type][filter_name] && filters[type][filter_name] !== 'false' && filters[type][filter_name] || $element.data('default');

                if ($element.attr('type') === 'text') {

                    $element.val(value);

                } else if ($element.attr('type') === 'checkbox') {

                    if (value) {

                        $element.prop('checked', true);
                        $element.parents('label').addClass('checked');

                    } else {

                        $element.prop('checked', false);
                        $element.parents('label').removeClass('checked');
                    }

                } else if ($element.prop('tagName').toLowerCase() === 'select') {

                    $element.val(value);

                    if ($element.find('option:selected').text()) {

                        $element.parents('.cselect').find('.textouter .text').html($element.find('option:selected').text());

                    } else {

                        $element.parents('.cselect').find('.textouter .text').html($element.data('default'));
                    }
                }
            });
        });
    };

    var _clearFilters = function () {

        // var newUrl = url = window.location.href.replace(window.location.search, '');
        // mb pass isAdvanced as POST param?
        // window.location.href = newUrl + '?advanced';

        var url;

        window.location.href = url = window.location.href.replace(window.location.search, '');

        /*resetingFilters = true;
        if (config.filters) {
            for (var i in config.filters) {
            $(config.filters[i]).find(':input').each(function (index, element) {
                if ($(element).attr('type') === 'checkbox') {
                if ($(element).data('default') === true) {
                    $(element).prop('checked', true);
                } else {
                    $(element).prop('checked', false);
                }
                } else {
                $(element).val('');
                }
                $(element).trigger('change');
                $(element).trigger('blur');
            });
            }
        }
        resetingFilters = false;
        _load(0);*/
    };

    var _setControlsEvents = function () {

        if (config.filters.fast) {

            $(config.filters.fast).find(':input').change(function () {

                if (!resetingFilters) {
                    profile.showSpinner(messages.wait_pleaseWait, 'calendar');
                    _load(0);
                }
            });
        }

        if (config.showMore) {
            $(config.showMore).click(function (e) {
                _loadNextPage();
            });
        }

        if (config.fav_cont) {
            var favClasses = config.fav_cont.split(', ');
            $.each(favClasses, function (i, v) {
                var selector = v + ' a';
                $(document).on('click', selector, function (e) {
                    _addToFavorites($(this).data('id'), $(this).data('clinic'), !($(this).parent().hasClass('faved') || $(this).hasClass('added')));
                });
            });
        }

        if (config.clear) {
            $(config.clear + ' a').click(function () {
                _clearFilters();
            });
        }

        if (config.filters.main) {

            var $form = $('form' + config.filters.main);

            $form.submit(function (e) {
                e.preventDefault();

                profile.showSpinner(messages.wait_pleaseWait, 'calendar');

                var $acFields = $form.find('.ui-autocomplete-input');

                $acFields.each(function (i, el) {

                    if($(el).data().hasOwnProperty('acfilled') && $(el).data('acfilled') === '0') {
                        $(el).val('').trigger('focus').trigger('blur');
                    }
                });

                _load(0);
            });
        }

        window.onpopstate = function (e) {

            if(!e.state && !location.search) {
                location.reload();
            }

            if (e.state) {

                e.preventDefault();

                $(config.content).html(e.state.ajax.content);
                $(config.result_count).html(e.state.ajax.total);

                if (e.state.ajax.show_more) {

                    $(config.showMore).show();

                } else {

                    $(config.showMore).hide();
                }

                if (e.state.data.doctors_filters) {

                    _setFiltersContent(e.state.data.doctors_filters);
                }

            }
        };
    };

    var _init = function (config_) {

        $.extend(config, _config, config_ || {});
        _setControlsEvents();
    };

    return {
        config: config,
        init: _init,
        load: _load,
        nextPage: _loadNextPage,
        addToFavorites: _addToFavorites,
        setFilterValues: _setFiltersContent
    };

}();
