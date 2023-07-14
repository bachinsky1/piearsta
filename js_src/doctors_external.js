$(function () {
    initDoctors();
});

function initDoctors() {
	var doctors = ajaxList;
	doctors.init({
		filters: {
		    fast: '.header .field',
		    main: ''
		},
		content: '.table_body .list',
		result_count: '.doctors-result-count',
		showMore: '.doctors-show-more',
		search: '.search-btn',
		fav_cont: '.fav_cont, .fav',
		list_type: 'doctors',
		ajaxUrl: $('#doctors_list').attr('rel')
    });

	if(doctors.config.filters.fast) {
		$(doctors.config.filters.fast).find(':input').on('keyup', function () {
			var $this = $(this);
			if($this.val() === '') {
				$this.trigger('change');
			}
		});
	}
}