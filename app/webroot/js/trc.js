$(document).ready(function() {
	// search and show/hide terms in a list
	$("#listsrc").on('keyup',function(){
		let val=$(this).val().toLowerCase().trim();
		let links=$('.links a');
		links.parent().removeClass('hidden');
		if(val!=='') { links.not('[title*="' + val + '"]').parent().addClass('hidden'); }
		// update panel counts
		let sections = $(".sections")
		sections.each(function() {
			let section = $(this);
			section.removeClass('hidden');
			let cnt = section.find(".list-group > li").not('.hidden').length;
			let sort = section.find(".panel-heading").attr('data-sort');
			section.find(".panel-heading > p").text(sort + ' (' + cnt + ')');
			if(cnt===0) { section.addClass('hidden'); }
		});
	});
});
