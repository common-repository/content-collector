/**
 * Скрипты для страницы запланированных записей
 * Используется только в панели администратора
 *
 * @author Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright (c) 17.02.2018, Webcraftic
 * @version 1.0
 */


(function($) {
	'use strict';

	$(document).ready(function() {
		$('.wbcr-autoposter-publish-status.publish-error, .wbcr-autoposter-publish-status.complete-publish-error').click(function() {
			var error_message = $(this).closest('.wbcr-autoposter-error-contanier').find('.wbcr-autoposter-error-message').text();
			alert(error_message);
			return false;
		});

		$(document).on('click', '.wbcr-autoposter-expand-action', function() {
			var spoilerWrap = $(this).prev();

			if( spoilerWrap.hasClass('wbcr-hide') ) {
				spoilerWrap.addClass('wbcr-show');
				spoilerWrap.removeClass('wbcr-hide');
				$(this).text('-Свернуть');
			} else if( spoilerWrap.hasClass('wbcr-show') ) {
				spoilerWrap.addClass('wbcr-hide');
				spoilerWrap.removeClass('wbcr-show');
				$(this).text('+Весь список');
			}

			return false;
		});

		$('.wbcr-autoposter-spoiler').each(function() {
			var spoiler = $(this);
			var spoilerWrap = spoiler.parent();
			var height = spoiler.outerHeight(true);

			if( height > 80 ) {
				spoilerWrap.addClass('wbcr-hide');
				spoilerWrap.after('<div class="wbcr-autoposter-expand-action">+Весь список</div>');
			}
		});
	});

})(jQuery);
