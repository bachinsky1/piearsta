$(function () {
    initDoctors();
});

function initDoctors() {
	var doctors = ajaxList;
    doctors.init({
		filters: {
		    //fast: '.controls',
		    main: '.search'
		},
		content: '.table_body .list',
		result_count: '.doctors-result-count',
		showMore: '.doctors-show-more',
		search: '.search-btn',
		fav_cont: '.fav_cont, .fav',
		list_type: 'doctors',
		currentPage: $('.doctors-show-more').attr('rel'),
    });

    if(doctors.config.filters.main) {

    	$(document).on('ready', function () {

    		var $acInputs = $(doctors.config.filters.main).find('.collapsible .ui-autocomplete-input'),
				$serviceInput = $acInputs.filter('.service-autocomplete'),
				$specialtyInput = $acInputs.filter('.specialty-autocomplete');

			$(doctors.config.filters.main).find('.collapsible .ui-autocomplete-input').on('keyup', function () {

				var $this = $(this);

				if($this.val() !== $this.data('current')) {
					$this.data('current', $this.val());
					$this.data('acfilled', '0');
				}
			});

			// construct acresults object
			window.acresults = {
				specialty: [],
				service: [],
			};

			if($specialtyInput.val() === '' || $specialtyInput.val() === 'false') {
				$specialtyInput.val('').trigger('focus').trigger('blur');
			}

			if($serviceInput.val() === '' || $serviceInput.val() === 'false') {
				$serviceInput.val('').trigger('focus').trigger('blur');
			}

			if($specialtyInput.data('acfilled') && $specialtyInput.val()) {
				window.acresults.specialty.push($specialtyInput.val().toLowerCase());
			}

			if($serviceInput.data('acfilled') && $serviceInput.val()) {
				window.acresults.specialty.push($serviceInput.val().toLowerCase());
			}

			$(window).on('acresults_change', function () {

				var serVal = $serviceInput.val().trim().toLowerCase(),
					spVal = $specialtyInput.val().trim().toLowerCase();

				if(window.acresults.service.length > 0 && serVal !== '') {

					if(window.acresults.service.indexOf(serVal) > -1) {
						$serviceInput.data('acfilled', '1');
					}
				}

				if(window.acresults.specialty.length > 0 && spVal !== '') {

					if(window.acresults.specialty.indexOf(spVal) > -1) {
						$specialtyInput.data('acfilled', '1');
					}
				}
			});
		});
	}
}