<?php
	/**
	 * Класс WSC_ExtractFacebookPost наследует WSC_ExtractPost
	 * данные извелеченной записи с помощью соц. сети фейсбук,
	 * заполняют стандартную схему с учетом полей, соц. сети фейсбук.
	 *
	 * @author Webcraftic <wordpress.webraftic@gmail.com>
	 * @copyright (c) 12.11.2017, Webcraftic
	 * @version 1.0
	 */

	require_once WSCR_PLUGIN_DIR . '/admin/includes/class.extract-post.php';

	/**
	 * Class WSC_ExtractFacebookPost
	 */
	class WSC_ExtractFacebookPost extends WSC_ExtractPost {

		public $facebook_image;
		public $facebook_title;
		public $facebook_description;
		public $facebook_emotions;
		public $facebook_permalink;

		/**
		 * @see WSC_ExtractPost __construct($task_id, array $post_data = array())
		 * @param int $task_id
		 * @param array $post_data
		 */
		public function __construct($task_id, array $post_data = array())
		{
			parent::__construct($task_id, $post_data);
		}

		/**
		 * Получить описание страницы
		 * @see WSC_ExtractPost getPostDescription()
		 * @return string
		 */
		public function getPostDescription()
		{
			$post_description_mode = WSCR_Helper::getMetaOption($this->task_id, 'post_excerpt', 'og_description');

			if( $post_description_mode == 'facebook_feed_description' ) {
				return sanitize_text_field($this->facebook_description);
			} else {
				return $this->page_description;
			}
		}

		/**
		 * Получение ссылки на превью изображения записи
		 * @see WSC_ExtractPost getFeatureImageUrl($result_upload)
		 * @param $result_upload
		 * @return null
		 */
		/*protected function getFeatureImageUrl($result_upload)
		{
			if( $this->getOption('post_feature_image', 'facebook_feed') == 'facebook_feed' ) {
				return $this->full_picture;
			} else {
				return parent::getFeatureImageUrl($result_upload);
			}
		}*/

		/**
		 * Фильтр выполняет перед публикацией записи. Используете для обработки содержания записи.         *
		 * @see WSC_ExtractPost uploadImagesBeforeUpdatePostContentFilter($content, $image_url)
		 * @param string $content
		 * @param string $image_url
		 * @return string
		 */
		protected function uploadImagesBeforeUpdatePostContentFilter($content, $image_url)
		{
			return '<p style="text-align: center;"><img class="alignnone size-full" src="' . esc_url($this->facebook_image) . '" alt="" /></p>' . PHP_EOL . $content;
		}
	}