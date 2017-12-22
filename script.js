function filter_a() {
	var state = $('.sel_state_a option:selected').attr('value');
	$('#og_a > option').each(function() {
		$(this).attr('style', '');
		var state2 = $(this).attr('data-state');
		if (state == state2 || state == "all") {
		} else {
			$(this).attr('style', 'display: none');
		}
	});
}
function filter_b() {
	var state = $('.sel_state_b option:selected').attr('value');
	$('#og_b > option').each(function( index ) {
		$(this).attr('style', '');
		var state2 = $(this).attr('data-state');
		if (state == state2 || state == "all") {
		} else {
			$(this).attr('style', 'display: none');
		}
	});
}
function same_id() {
	var selected_a = $('.select_a option:selected').attr('value');
	var selected_b = $('.select_b option:selected').attr('value');
	if(selected_a == selected_b) {
		$("#compare-submit").attr('disabled', '');
		$("#compare-submit").attr('title', 'The same member is selected twice.');
	} else {
		$("#compare-submit").removeAttr('disabled');
		$("#compare-submit").removeAttr('title');
	}
}
var selected_a = "";
var selected_b = "";
var state_a = $('.select_a option:selected').attr('data-state');
var state_b = $('.select_b option:selected').attr('data-state');
if(state_a == "default") {
} else {
	$('.sel_state_a option[value="' + state_a + '"]').attr("selected", "selected");
	filter_a();
}
if(state_b == "default") {
} else {
	$('.sel_state_b option[value="' + state_b + '"]').attr("selected", "selected");
	filter_b();
}
$('.select_a').change(function() {
	var selected_a = $('.select_a option:selected').attr('id');
	$('.select_a').attr('id', selected_a);
	same_id();
});
$('.select_b').change(function() {
	var selected_b = $('.select_b option:selected').attr('id');
	$('.select_b').attr('id', selected_b);
	same_id();
});
$('.sel_state_a').change(function() {
	filter_a();
});
$('.sel_state_b').change(function() {
	filter_b();
});
$('#per').click(function(){
    $('.details').slideToggle(350);
});