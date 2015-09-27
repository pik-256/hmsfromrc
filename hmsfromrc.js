$(document).ready(function () {
    $('#ztp_ar_ae_date').datepicker({
        dateFormat: "yy-mm-dd",
		minDate: "+1",
        constrainInput: true,
        autoSize: true,
        showButtonPanel: true
    });
	
	$("#ztp_ar_ae_enabled").click(function() {
		$("#ztp_ar_ae_date").prop('disabled', !this.checked)
	}).triggerHandler('click');
	
	$("#ztp_ar_enabled").click(function() {
		var elems = $(this).parents("table");
		$("input,textarea", elems).not(this).prop('disabled', !this.checked)
	}).triggerHandler('click');
	
});