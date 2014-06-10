
$(document).ready(function()
{
	// Fix input element click problem
	$('.dropdown input, .dropdown label').click(function(e) {
		e.stopPropagation();
	});
	
	$('.btn-with-tooltip').tooltip();	
});
