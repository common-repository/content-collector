/**
 * Feed scripts
 * @author Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright (c) 10.11.2017, Webcraftic
 * @version 1.0
 */


(function($) {
	'use strict';

	$(function() {

		$('body').on( 'click', '.wbcr-scrapes-remove-feed-item',function() {
			var self = this;

			var postId = $(this).data('post-id');
			if( !postId ) {
				alert('[Error]: post id is empty');
				return
			}

			var req = $.ajax({
				url: ajaxurl,
				type: 'post',
				dataType: 'json',
				data: {
					action: 'scrapes_ajax_remove_post',
					post_id: postId
				},
				success: function(data, textStatus, jqXHR) {
					if( !data || data.error ) {
						data.error && console.log(data.error);
						return false;
					}

					self.closest('.wbcr-scrapes-feed-item').remove();
				}
			});

			return false;
		});
	});

})(jQuery);
