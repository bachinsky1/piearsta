
var moduleTable = {
	
	id : "modulePath",
	moduleName : "",
	mainUrl : "/admin/",
	ajaxAction : "moduleTable",
	usePaging : true,
	totalRecords : 0,
	from : 0,
	sortField : "",
	sortOrder : "",
	itemsOnPage : 25,
	additionalParms: "",
	callFunction: "",
	refreshTree: true,

	updateModule : function(callF) {
		
		if (callF) {
			this.callFunction = callF;
		}
		
		$.post(this.getRequestUrl() + this.ajaxAction + "/", this.getRequestParameters(),
				function(data) {moduleTable.drawAll(data);}, "json");
	},
	
	updateContentModule : function(callF) {
		
		if (callF) {
			this.callFunction = callF;
		}

		$.post(this.getRequestUrl() + this.ajaxAction + "/", this.getRequestParameters(),
				function(data) {moduleTable.drawAllContent(data);}, "json");
	},
	
	drawAllContent : function(data) {

		var returnHtml = data["html"],
			recCount = data["rCounts"],
			extraField = data["filterLang"];

		if (extraField != null && extraField != '') {
			$('#language').html(extraField);
		}
		
		if (recCount != null) {
			this.totalRecords = recCount;
		}

		$('#' + this.id).html(returnHtml);
		 	
		if (moduleTable.refreshTree) {
			if (this.callFunction) {

				eval(this.callFunction());
			}
			//moduleTable.refreshTree = false;
		} else {
			$('#' + this.id).jstree('refresh', -1);
		}	
		
	},
	
	drawAll : function(data) {

		var returnHtml = data["html"],
			recCount = data["rCounts"];
		
		if (recCount != null) {
			this.totalRecords = recCount;
		}

		$('#' + this.id).html(returnHtml);
		
		if (this.usePaging) {
			this.makePaginator();
		}
		
		if (this.callFunction) {
			var $ff = this.callFunction;
			eval($ff(data));
		}
		
	},
	
	updateLanguages : function (data) {
		//alert(data.langs);
	},
	
	sort : function (field) {
		if (field == this.sortField) {
			this.sortOrder = this.sortOrder == "ASC" ? "DESC" : "ASC";
		} 
		else {
			this.sortField = field;
			this.sortOrder = "ASC";
		}	
		
		this.from = 0;
		this.updateModule();
	},
	
	getRequestParameters : function() {
		return "itemsFrom=" + this.from + "&itemsShow=" + this.itemsOnPage + "&sortField=" + this.sortField + "&sortOrder=" + this.sortOrder + this.additionalParms;
	},
	
	getRequestParameters_ : function() {
		return "itemsFrom=" + this.from + "&itemsShow=" + this.itemsOnPage + "&sortField=" + this.sortField + "&sortOrder=" + this.sortOrder + this.additionalParms;
	},
	
	getRequestUrl : function() {
		return this.mainUrl + this.moduleName + "/";
	},
    
    makePaginator : function() {
    	var pages = Math.ceil(this.totalRecords / this.itemsOnPage)-1;
    	pages = pages < 0 ? 0 : pages;
    	var currentPage = Math.ceil(this.from / this.itemsOnPage);
   		var html = '<ul class="paging">';
   		var from;

   		//back
   		if (currentPage > 0) {
   			from = (currentPage - 1) * this.itemsOnPage;
   			html += '<li class="prev"><a href="javascript:moduleTable.rewind(' + from + ');" class="left"><span>Previous</span></a></li>';
   		} 
   		else {
   			html += "";
   		}
   		
   		var start = this.from + 1;
    	var end = start + this.itemsOnPage -1;
    	
    	if (end > this.totalRecords) {
    		end = this.totalRecords;
    	}	
    	
    	if (start > this.totalRecords) {
    		start = this.totalRecords;
    	}	
    	html += "<li>" + start + "-" + end + " / " + this.totalRecords + "</li>";

   		//fwd
   		if (currentPage < pages) {
   			from = (currentPage + 1) * this.itemsOnPage;
   			html += '<li class="next"><a href="javascript:moduleTable.rewind(' + from + ');"><span>Next</span></a></li>';
   		} else {
   			html += "";
   		}
   		
   		html += '</ul>';
   		
   		$('#pagerTop').html(html); 
   		$('#pagerBottom').html(html);
    },
    
    rewind : function(no) {
    	this.from = no;
    	this.additionalParms += "&itemsRewind=1";
    	this.updateModule();
    }
};
