$(function () {
    var clinics = ajaxList;
    clinics.init({
		filters: {
		    fast: '.controls',
		    main: '.search'
		},
		content: '.cont .list',
		result_count: '.clinics-result-count',
		showMore: '.clinics-show-more',
		search: '.search-btn',
		list_type: 'clinics',
		currentPage: $('.clinics-show-more').attr('rel'),
    });
});