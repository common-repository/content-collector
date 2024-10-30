/**
 * General scripts
 * @author Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright (c) 08.11.2017, Webcraftic
 * @version 1.0
 */


(function($) {
	'use strict';

	var general = {
		inputCurrent: null,
		iframe: null,
		modal: null,
		errors: {},
		init: function() {
			var self = this;

			this.addXpathContanier('#wbcr_scrapes_xpath_post_title_url');
			this.addXpathContanier('#wbcr_scrapes_post_title');
			this.addXpathContanier('#wbcr_scrapes_post_content');
			this.addXpathContanier('.wbcr-scrapes-html-filters');

			this.registerEvents();
		},

		addXpathContanier: function(selector) {
			var xpathFields;
			if( typeof selector === 'string' ) {
				xpathFields = $(selector);
			} else if( typeof selector === 'object' ) {
				xpathFields = selector;
			} else {
				throw new Error('Unknow type of selector');
				return;
			}

			var xpathContanier = $('<div class="wbcr-scrapes-control-xpath"></div>');
			xpathFields.wrap(xpathContanier);
			xpathFields.after('<button class="btn btn-default btn-small wbcr-scrapes-xpath-target-button"><i class="fa fa-bullseye" aria-hidden="true"></i></button>');

		},

		registerEvents: function() {
			var self = this;

			$('.factory-mtextbox-add-item', '.factory-control-html_filters').on('click', function(e) {
				e.preventDefault();
				self.addXpathContanier('.factory-mtextbox-item:last-child > .wbcr-scrapes-html-filters');
				return false;
			});

			$(document).on('click', '.wbcr-scrapes-xpath-target-button', function(e) {
				var $this = $(this);

				self.inputCurrent = $this.parent().find('.factory-from-control-textbox, .factory-from-control-multiple-textbox');

				if( !self.modal ) {
					self.modal = $('body').append('<div class="factory-bootstrap-401"><div id="wbcr-scrapes-iframe" class="modal fade">' +
					'<div class="modal-dialog">' +
					'<div class="modal-content">' +
					'<div class="modal-body">' +
					'<iframe id="wbcr-scrapes-iframe-render" frameborder="0"></iframe>' +
					'</div>' +
					'</div>' +
					'</div>' +
					'</div></div>');

					self.iframe = self.modal.find('#wbcr-scrapes-iframe-render');
				}
				var pageUrl;
				var sourceChannel;

				if( window.wbcrScrapesSourceChannel !== undefined || window.wbcrScrapesSourceChannel != '' ) {
					sourceChannel = window.wbcrScrapesSourceChannel;
				}

				sourceChannel = sourceChannel ? sourceChannel : $('#wbcr_scrapes_source_channel').val();

				var isPostTitleUrlControl = $(e.target).closest('.wbcr-scrapes-control-xpath').find('.factory-textbox').attr('id') == 'wbcr_scrapes_xpath_post_title_url';

				if( isPostTitleUrlControl && sourceChannel == 'site_stream' ) {
					pageUrl = $('#wbcr_scrapes_paginate_url').val();
					if( !pageUrl ) {
						alert('Пожалуйста, установите ссылку пагинации, чтобы открыть страницу для разметки шаблона.');

						return false;
					}
				} else {
					if( sourceChannel == 'default' ) {
						var collectedLinks = $('#wbcr_scrapes_collected_links').val().split(/\r?\n/);
						if( collectedLinks[0] ) {
							pageUrl = collectedLinks[0];
						}
					} else {
						pageUrl = $('#wbcr_scrapes_site_url').val();
					}

					if( !pageUrl ) {
						alert('Пожалуйста, выберите адрес сайта для разметки шаблона.');
						return false;
					}
				}

				if( self.inputCurrent.hasClass('wbcr-scrapes-html-filters') && $('#wbcr_scrapes_post_content').val() == '' ) {
					alert('Пожалуйста, установите шаблон для извлечения содержания записи.');
					return false;
				}

				var iframeUrl = ajaxurl + '?action=scrapes_ajax_url_load&address=' + encodeURIComponent(pageUrl);

				if( self.iframe.attr('src') != iframeUrl ) {
					self.iframe.attr('src', iframeUrl);
				}

				self.setColorsForChangeSections();

				$('#wbcr-scrapes-iframe').factoryBootstrap401_modal();

				self.iframe.on('load', function() {
					var iframeCurrent = $(this);

					$(this).contents().find('head').append(
						$('<link/>', {
							rel: 'stylesheet',
							type: 'text/css',
							href: wbcrScrapesPluginPath + '/assets/css/iframe.css',
							id: 'wbcr-scrapes-inspector'
						})
					);

					self.setColorsForChangeSections();

					$(this).contents()
						.on('mouseover', function(event) {
							if( self.inputCurrent.hasClass('wbcr-scrapes-html-filters') && !$(event.target).closest('.wbcr-scrapes-picked-content-section, .wbcr-scrapes-picked-title-section').length ) {
								return;
							}

							$(event.target).addClass('wbcr-scrapes-inspector');
						})
						.on('mouseout', function(event) {
							if( self.inputCurrent.hasClass('wbcr-scrapes-html-filters') && !$(event.target).closest('.wbcr-scrapes-picked-content-section, .wbcr-scrapes-picked-title-section').length ) {
								return;
							}

							$(event.target).removeClass('wbcr-scrapes-inspector');
						})
						.on('click', function(event) {
							event.preventDefault();

							var xpath = self.getXpath(event.target);
                            xpath = xpath.replace(/\[[0-9]+\]/g, '');

							if( self.inputCurrent.hasClass('wbcr-scrapes-html-filters') ) {
								if( !$(event.target).closest('.wbcr-scrapes-picked-content-section, .wbcr-scrapes-picked-title-section').length ) {
									$('#wbcr-scrapes-iframe').factoryBootstrap401_modal('hide');
									return;
								}
							}

							// SITE STREAM CHANNEL
							if( isPostTitleUrlControl && sourceChannel == 'site_stream' ) {
								var siteUrl, countPosts;

								if( $(event.target).find('a').length ) {
									siteUrl = $(event.target).find('a').attr('href');
								} else {
									var nest = 0, isFindATag,
										elTarget = $(event.target);

									while( nest <= 5 && !siteUrl ) {
										isFindATag = elTarget.prop("tagName") == 'A';
										if( isFindATag ) {
											siteUrl = elTarget.attr('href');
										} else {
											elTarget = elTarget.parent();
										}
										nest++;
									}
								}

								//console.log(siteUrl);
                                if(self.inputCurrent.attr('id') === 'wbcr_scrapes_xpath_post_title_url') {
                                    $('#wbcr_scrapes_site_url').val(siteUrl);
                                }

								if( self.iframe && self.iframe.contents() ) {
									var xPathPostsUrl = xpath.replace(/\[\d+\]/g, '');
									countPosts = self.convertXpathToJquery(self.iframe, xPathPostsUrl).length;
								}

								if( !countPosts || countPosts < 2 ) {
									countPosts = 10;
								}
                                if(self.inputCurrent.attr('id') === 'wbcr_scrapes_xpath_post_title_url') {
									$('#wbcr_scrapes_post_per_page').val(countPosts);
								}
							}

							if( iframeCurrent.attr('id') == self.iframe.attr('id') ) {
								self.inputCurrent.val(xpath);
								$('#wbcr-scrapes-iframe').factoryBootstrap401_modal('hide');

							}
						});
					return false;
				});

				return false;
			});

		},

		setColorsForChangeSections: function() {
			var self = this;
			if( self.iframe && self.iframe.contents() ) {
				var postTitleValue = $('#wbcr_scrapes_post_title').val(),
					postContentValue = $('#wbcr_scrapes_post_content').val();

				self.iframe.contents()
					.find('.wbcr-scrapes-picked-title-section, .wbcr-scrapes-picked-content-section, .wbcr-scrapes-picked-filter-section')
					.removeClass('wbcr-scrapes-picked-title-section')
					.removeClass('wbcr-scrapes-picked-content-section')
					.removeClass('wbcr-scrapes-picked-filter-section');

				if( postTitleValue ) {
					self.convertXpathToJquery(self.iframe, postTitleValue).addClass('wbcr-scrapes-picked-title-section');
				}
				if( postContentValue ) {
					self.convertXpathToJquery(self.iframe, postContentValue).addClass('wbcr-scrapes-picked-content-section');
				}

				$('.wbcr-scrapes-html-filters').each(function() {
					var value = $(this).val().split('{|}');

					if( value[0] ) {
						self.convertXpathToJquery(self.iframe, value[0]).addClass('wbcr-scrapes-picked-filter-section');
					}
				});
			}
		},

		checkElement: function(type, element) {
			if( element.is(type) ) {
				return true;
			} else {
				if( element.find(type + ':first').is(type) ) {
					return element.find(type + ':first').get(0);
				} else {
					if( element.parents().find(type + ':last').is(type) ) {
						return element.parents().find(type + ':last').get(0);
					} else {
						return false;
					}
				}
			}
		},
		getXpath: function(element) {
			var self = this;

			var result = [];
			var parent_index = 0;

			$($(element).parents().addBack().get().reverse()).each(function() {
				var name_tag = this.nodeName.toLowerCase();
				var name_node = name_tag;
				var non_digits, $elements, xpathForClasses, non_digits_parts;

				if( name_node == 'body' ) {
					return false;
				}

				if( $(this).hasClass('wbcr-scrapes-inspector') ) {
					$(this).removeClass('wbcr-scrapes-inspector');
				}

				if( $(this).attr('id') ) {
					non_digits = $(this).attr('id').split(/\s+/).filter(function(c) {
						return !/\d/.test(c);
					}).join(' ');
					if( non_digits != "" ) {
						name_tag += '[@id="' + non_digits + '"]';
						result.push(name_tag);
						return false;
					}
				}

				if( $(this).siblings(name_node).length > 0 ) {
					name_tag += "[" + ($(this).prevAll(name_tag).length + 1) + "]";
				}

				if( $(this).attr('class') ) {
					non_digits = $(this).attr('class').split(/\s+/).filter(function(c) {
						return !/(\d|tag\-|category\-|format\-|has\-post\-|status\-|wbcr-scrapes\-)/.test(c);
					}).join(' ');

					if( non_digits != "" ) {
						non_digits = non_digits.trim().replace(/\s+/g, ' ');
						non_digits_parts = non_digits.split(' ');

						xpathForClasses = "[contains(@class, '" + non_digits + "')]";

						if( non_digits_parts.length > 1 ) {
							xpathForClasses = "[contains(@class, '" + non_digits_parts[0] + "')";
							for( var i = 1; i < non_digits_parts.length; i++ ) {
								xpathForClasses += " and contains(@class, '" + non_digits_parts[i] + "')";
							}
							xpathForClasses += "]";
						}
						name_tag += xpathForClasses;
						$elements = self.convertXpathToJquery(self.iframe, "//" + name_node + xpathForClasses);

						if( $elements.length == 1 && parent_index == 0 ) {
							result = [];
							result.push(name_node + xpathForClasses);
							return false;
						}
					}
				}
				parent_index++;
				result.push(name_tag);
			});

			if( parent_index == 0 ) {
				return '//' + result.reverse().join('/');
			} else {
				return '//' + result.reverse().join('/') + ' | ' + self.getAbsoluteXpath(element);
			}

		},
		getAbsoluteXpath: function(a) {
			var b = [];
			$($(a).parents().addBack().get().reverse()).each(function() {
				var a = this.nodeName.toLowerCase(), c = a;
				0 < $(this).siblings(c).length && (a += "[" + ($(this).prevAll(a).length + 1) + "]");
				b.push(a);
			});
			return "//" + b.reverse().join("/");
		},

		convertXpathToJquery: function(container, xpath) {
			var item;
			var result = [];
			var doc = container[0].contentWindow.document;
			var xpaths = doc.evaluate(xpath, doc, null, XPathResult.ORDERED_NODE_ITERATOR_TYPE, null);

			while( item = xpaths.iterateNext() ) {
				result.push(item);
			}

			return $([]).pushStack(result);
		}

	};

	$(function() {
		general.init();
	});
})(jQuery);
