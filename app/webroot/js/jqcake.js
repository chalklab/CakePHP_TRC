// Used to get the site path of this CakePHP install location without having to hand code it in this file
var scripts = document.getElementsByTagName('script');
var src = scripts[scripts.length-1].src;
var arr = src.split("/js/");
var sitepath = arr[0];

jQuery.expr[':'].iContains = function(a, i, m) {
	return jQuery(a).text().toUpperCase()
			.indexOf(m[3].toUpperCase()) >= 0;
};// adds a case insensitive contains message

function jq( myid ) {

	return myid.replace( /(\(|:|\.|\[|\]|,|\?)/g, "\\$1" );

}

function toggle(e)
{
	$(document).ready(function()
	{
		$("#"+e).slideToggle();
	})
}

function load(e,url)
{
	$(document).ready(function()
	{
		$("#"+e).load(url, function() { MathJax.Hub.Queue(["Typeset",MathJax.Hub,"right"]);if(jQuery.isFunction("loadScript")) { loadScript(); } });
	})
}

function getSnippet(id)
{
	var path = "ruletemplates/view/" + id;
	return ajacks(path);
}


function getTemplate(id)
{
	var url = "https://chalk.coas.unf.edu/trc/ruletemplates/view/" + id;
	$.getJSON(url,function(data) {
		return data;
	});



}

function ajacks(path,cback)
{
	$.ajax({
		dataType: "json",
		type: "GET",
		url: "https://chalk.coas.unf.edu/trc/" + path,
		cache: false,
		success:function(data) {
			//alert(JSON.stringify(data));
			//alert(data[0].regex);
			cback(data);
		}
	});
}

function down(e)
{
	$(document).ready(function()
	{
		$("#"+e).toggle();
	})
}

function append(e,url)
{
	$(document).ready(function()
	{
		$.get(url,function(data) {
			$("#"+e).append(data);
		});
	})
}

function list(e,url)
{
	$(document).ready(function()
	{
		$.getJSON(sitepath+url, function(data) {
			var select = $("#"+e);
			if($(select).children('option').size()==1) {
				$.each(data, function(key,value) {
					select.append("<option value=" + key + ">" + value + "</option>");
				});
			}
		});
	})
}

function clone(e,parent,rels)
{
	$(document).ready(function()
	{
		if(rels===undefined) { rels=false; }
		var oldindex = Number(document.getElementById(e+"index").value);
		var newindex = oldindex+1;
		$("#"+e+oldindex).clone().attr('id',e+newindex).insertAfter("#"+e+oldindex);
		var children=$("#"+e+newindex).children();
		children.each(function() {
			var oldid=$(this).attr('id');var newid;
			if(rels) {
				newid=oldid.replace('Rels'+oldindex,'Rels'+newindex);
			} else {
				newid=oldid.replace(ucfirst(e)+oldindex,ucfirst(e)+newindex);
			}
			$(this).attr('id',newid);
			var oldname=$(this).attr('name');var newname;
			if(rels) {
				newname=oldname.replace("[rels]["+oldindex+"]","[rels]["+newindex+"]");
			} else {
				newname=oldname.replace("["+e+"]["+oldindex+"]","["+e+"]["+newindex+"]");
			}
			$(this).attr('name',newname);
			if($(this).attr('type')!='hidden') { $(this).val(''); }
		});
		$("#"+e+"index").val(newindex);
	})
}

function livesearch(search,results,searchurl,junk1,junk2)
{
	$(document).ready(function()
	{
		function search()
		{
			var query_value = $("#"+search).val();
			if(query_value !== ''){
				$.ajax({
					type: "GET",
					url: searchurl,
					cache: false,
					success: function(html){
						$("#"+results).html(html);
					}
				});
			}
			return false;
		}
		
		$("#"+search).live("keyup", function(e) {
			// Set Timeout
			clearTimeout($.data(this, 'timer'));
			// Set Search String
			var search_string = $(this).val();
			// Do Search
			if (search_string == '') {
				$("#"+results).fadeOut();
			}else{
				$("#"+results).fadeIn();
				$(this).data('timer', setTimeout(search, 100));
			}
		});
	})
}

function addrel(type,inputId,inputNamePrefix)
{
	$(document).ready(function()
	{
        var inputid = $("#"+inputId);
        var typeindex = $("#"+type+"index");
        var term = inputid.val();
		if(term) { if(term.length<3) { return; } }
		var index=typeindex.val();
		index++;
		// Clear input field
		inputid.val("");
		// Create the new term button
		$("#"+type+"div").append("<div id='"+type+index+"'></div>");
		var attrs1= { "class" : "inputbutton", "onclick" : "remove(this.id);return false;", "title" : "Click to remove" };
        typeindex.attr(attrs1);
        typeindex.text(term);
		// Create the literal for the new term (inside div above)
        typeindex.append("<input id='"+type+index+"Literal'/>");
		var attrs2= { "type" : "hidden", "name" : "data" + inputNamePrefix + "[" + index + "][literal]", "value" : term };
		$("#"+type+index+"Literal").attr(attrs2);
		// Create the predicate for the new term
        typeindex.append("<input id='"+type+index+"Predicate'/>");
		var attrs3= { "type" : "hidden", "name" : "data" + inputNamePrefix + "[" + index + "][predicate]", "value" : "has"+ucfirst(type) };
		$("#"+type+index+"Predicate").attr(attrs3);
		// Update the index
        typeindex.val(index);
	})
}

function remove(e)
{
	$(document).ready(function()
	{
		$("#"+e).remove();
	})
}

function copydiv(from,to)
{
	from = $('#'+from).html();
	$('#'+to).html(from);
}

function showletter(letter)
{
	$('.letter').hide();
	$('.letter ul li').show();
	$('#'+jq(letter)).show();
}

function ucfirst(str)
{
	return str.charAt(0).toUpperCase() + str.slice(1);
}
$( document ).ready(function() {
	if ($("#letterSearch").length != 0) { //if we have a search bar on the page
		$("#letterSearch").on('input', function () { //attach to the search bar input event
			if ($(".systemReports").length != 0) { //if you are typing somethere and there are reports on the page, hide them
				$(".systemReports").hide();
			}
			if ($(this).val() == "") { //if the bar has nothing in it hide everything
				$('.letter').hide();
			} else {
				$('.letter').show(); //else show all letters
				$('.letter ul li').hide(); //hide the individual elements
				$('.letter ul li:iContains("' + $(this).val() + '")').show(); //show the ones that match the search string
			}
		})
	}
	$(".showReports").click(function(e){
		e.preventDefault();
		if($(this).parents("li").find(".systemReports").css("display")!="block") {
			$(".systemReports").hide();
			$(this).parents("li").find(".systemReports").css("display", "block");
		}else{
			$(".systemReports").hide();
		}
	})
});