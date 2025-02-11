(function ($) {
	$(document).ajaxComplete(function (event, xhr, settings) {
		if (!settings.data || !settings.data.includes("action=save-attachment")) return;

		// Extract attachment ID from request data
		let attachmentId = new URLSearchParams(settings.data).get("id");
		if (!attachmentId) return;

		// Fetch the updated attachment metadata
		wp.media.model.Attachment.get(attachmentId).fetch({
			success: function (attachment) {
				let altTextFields = $('#attachment-details-alt-text, #attachment-details-two-column-alt-text');
				if (altTextFields.length) {
					altTextFields.val(attachment.get("alt")).trigger("change");
				}
			}
		});
	});
})(jQuery);
