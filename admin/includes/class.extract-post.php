<?php

	/**
	 * Class execute post
	 * @author Webcraftic <wordpress.webraftic@gmail.com>
	 * @copyright (c) 12.11.2017, Webcraftic
	 * @version 1.0
	 */
	class WSC_ExtractPost {


		/**
		 * ID записи, если она уже добавлена
		 *
		 * @var int
		 */
		public $post_id;

		/**
		 * ID выполняемой задачи
		 *
		 * @var int
		 */
		public $task_id;

		/**
		 * Оригинальный url страницы с которой была извлечена текущая запись
		 *
		 * @var string
		 */
		public $origin_url;

		/**
		 * Заголовок текущей записи
		 *
		 * @var string заголовок текущей записи
		 */
		public $page_title;

		/**
		 * Краткое описание текущей записи
		 *
		 * @var string
		 */
		public $page_description;

		/**
		 * Содержимое текущей записи
		 *
		 * @var string
		 */
		public $page_content;

		/**
		 * true если это новоя запись
		 *
		 * @var bool
		 */
		protected $is_new_post = false;

		public $remove_external_links;
		public $save_source_link;
		public $save_social_links;

		/**
		 * @param int $task_id
		 * @param array $post_data
		 */
		public function __construct($task_id, array $post_data)
		{
			if( empty($task_id) || empty($post_data) ) {
				throw new Exception('Атрибут {task_id,post_data} не может быть пустым.');
			}

			$this->task_id = (int)$task_id;

			foreach($post_data as $key => $value) {
				if( property_exists($this, $key) ) {
					$this->$key = $value;
				}
			}

			if( !empty($this->origin_url) ) {
				$this->post_id = WSCR_Helper::getPostIdBySrapedUrl($this->origin_url);
				$this->is_new_post = empty($this->post_id);
			} else {
				throw new Exception('Атрибут {origin_url} не может быть пустым.');
			}

            $this->remove_external_links = (bool)get_post_meta($task_id, 'wbcr_scrapes_remove_external_links', true);
            $this->save_source_link = (bool)get_post_meta($task_id, 'wbcr_scrapes_save_source_link', true);
            $this->save_social_links = (bool)get_post_meta($task_id, 'wbcr_scrapes_save_social_links', true);
		}

		/**
		 * @return bool
		 */
		public function isNewPost()
		{
			return $this->is_new_post;
		}

		/**
		 * @return int|null
		 */
		public function getPostId()
		{
			return $this->post_id;
		}

		/**
		 * @param int $post_id
		 */
		/*public function setPostId($post_id)
		{
			if( $this->isNewPost() ) {
				$this->post_id = (int)$post_id;
			}
		}*/

		/**
		 * @return int|string
		 */
		public function getPostDate()
		{
			return current_time('mysql');
		}

		/**
		 * Получить заголовок записи
		 *
		 * @param bool $raw - false не фильтровать содержимое
		 * @return string возвращает отфильрованное содержимое записи
		 */
		public function getPostTitle($raw = false)
		{
			$post_title = $this->page_title;

			if( !$raw ) {
				$post_title = trim(wp_strip_all_tags($this->page_title));
			}

			return $post_title;
		}

		/**
		 * Получить содержимое записи
		 *
		 * @param bool $raw - false не фильтровать содержимое
		 * @return string возвращает отфильрованное содержимое записи
		 */
		public function getPostContent($raw = false)
		{
			$post_content = $this->page_content;

			if( !$raw ) {
				$post_content = $this->filterHtml($this->page_content);
			}

			return $post_content;
		}

		/**
		 * Получить короткое описание для записи
		 *
		 * @return string
		 */
		public function getPostDescription()
		{
			return $this->page_description;
		}

		/**
		 * Вырезает все html теги, кроме тегов прописанных в исключении
		 *
		 * @param string $content
		 * @return string возвращает отфильрованную строку
		 */
		protected function filterHtml($content)
		{
			$content = wp_kses($content, array(
				'p' => array(
					'style' => array()
				),
				'strong' => array(),
				'br' => array(),
				'b' => array(),
				'em',
				'ul' => array(),
				'li' => array(),
				'a' => array(
					'href' => array(),
					'title' => array(),
					'target' => array()
				),
				'img' => array(
					'src' => array(),
					'alt' => array(),
					'title' => array(),
					'style' => array(),
					'class' => array(),
					'data-src' => array()
				),
				'span' => array(
					'style' => array()
				),
				'blockqoute' => array(
					'style' => array()
				),
				'iframe' => array(
					'src' => array(),
					'width' => array(),
					'height' => array(),
					'frameborder' => array(),
					'allowfullscreen' => array()
				)
			));

			if($this->remove_external_links){
                // strip all links, exclude source|источник
                $doc = new DOMDocument();
                @$doc->loadHTML('<?xml encoding="utf-8" ?><div>' . $content . '</div>');
                $links = $doc->getElementsByTagName('a');

                $social_domains = array('facebook.com', 'fb.com','vk.com', 'vkontakte.ru', 'instagram.com', 'youtube.com', 'youtu.be', 'linkedin.com', 'livejournal.com', 'ok.ru', 'odnoklassniki.ru', 'odnoklassniki.com', 'wikipedia.org');
                $pattern = '/(http:|https:)?\/\/(.*\.)?('.implode('|', $social_domains).')/';

                if($links->length){
                    for($i = $links->length; $i > 0; $i--){
                        $link = $links->item($i-1);
                        $innerText = '';
                        foreach ($link->childNodes as $node){
                            if($node->nodeType != XML_TEXT_NODE){
                                continue;
                            }
                            $innerText .= ' '.$node->nodeValue;

                        }

                        $url = $link->getAttribute('href');
                        $extension = strtolower(pathinfo(parse_url($url)['path'], PATHINFO_EXTENSION));
                        if(in_array($extension, array(
                            'jpeg',
                            'jpg',
                            'bmp',
                            'gif',
                            'png',
                            'svg',
                        ))){
                            $new_div = $doc->createElement('div');
                            $childs = [];
                            foreach ($link->childNodes as $childNode) {
                                $childs[] = $childNode;
                            }
                            foreach ($childs as $child) {
                                $new_div->appendChild($child);

                            }
                            $link->parentNode->replaceChild($new_div, $link);

                            continue;
                        }



                        if(
                            (!$this->save_source_link or !preg_match('/.*(Источник|source).*/miu', $innerText))
                            and
                            (!$this->save_social_links or !preg_match($pattern, $link->getAttribute('href')))
                        ){
                            $link->parentNode->removeChild($link);
                        }
                    }
                }

                $content = $doc->saveHTML();
            }


			return $content;
		}

		/**
		 * @return int|WP_Error
		 * @throws Exception
		 */
		public function savePost()
		{
			global $wpdb;

			WSCR_Helper::writeLog("Задача[$this->task_id] начало сохранения записи...");

			// Запись уже добавленаы
			if( !$this->isNewPost() ) {
				WSCR_Helper::writeLog("Задача[$this->task_id] запись [" . $this->post_id . "] уже добавлена.");
				WSCR_Helper::writeLog("Задача[$this->task_id] завершена...");

				return $this->post_id;
			}

			// Начало выполнения задачи
			$post_origin_content = $this->getPostContent(true);

			$post_status = WSCR_Helper::getMetaOption($this->task_id, 'post_status', 'draft');

			unset($body_preg);

			$post_arr = array(
				'post_date' => date("Y-m-d H:i:s", strtotime($this->getPostDate())),
				'post_content' => $this->getPostContent(),
				'post_title' => $this->getPostTitle(),
				'post_status' => $post_status,
				'post_type' => 'post',
				'ping_status' => 'closed',
				'post_excerpt' => $this->getPostDescription()
			);

			kses_remove_filters();
			$new_id = wp_insert_post($post_arr, true);
			kses_init_filters();

			if( is_wp_error($new_id) ) {
				WSCR_Helper::writeLog("Задача[$this->task_id]" . 'Ошибка при добавлении записи!' . PHP_EOL . var_export($new_id->get_error_messages(), true) . PHP_EOL . var_export($post_arr, true));

				return null;
			}

			$this->post_id = $new_id;

			WSCR_Helper::writeLog("Задача[$this->task_id] запись успешно добавлена. ID записи[$new_id]...");

			$cat_ids_string = WSCR_Helper::getMetaOption($this->task_id, 'categories');

			// Устанавливаем категории
			if( !empty($cat_ids_string) ) {
				WSCR_Helper::writeLog("Задача[$this->task_id] процес установки категорий для записи ID[$new_id]");

				$cat_ids = array_map('intval', explode(',', $cat_ids_string));
				$cat_ids = array_unique($cat_ids);

				$term_taxonomy_ids = wp_set_object_terms($new_id, $cat_ids, 'category');

				if( is_wp_error($term_taxonomy_ids) ) {
					WSCR_Helper::writeLog("Задача[$this->task_id] Невозможно добавить категории [$cat_ids_string] для записи ID[$new_id]...");
				} else {
					WSCR_Helper::writeLog("Задача[$this->task_id] процес установки категорий для записи ID[$new_id] успешно завершен!");
				}
			}

			WSCR_Helper::updateMetaOption($new_id, 'scrape_task_id', $this->task_id);
			WSCR_Helper::updateMetaOption($new_id, 'original_url', $this->origin_url);
			WSCR_Helper::updateMetaOption($new_id, 'original_post_content', $post_origin_content);

			//if( !WSCR_Helper::getMetaOption($this->task_id, 'upload_images_to_local_storage') ) {
			//WSCR_Helper::writeLog("Задача[$this->task_id] завершена...");
			//} else {

			//$post_resourses = new WSC_PostResourses($new_id, $this);
			//$post_resourses->saveImages();

			/*$wpdb->insert($wpdb->prefix . "wbcr_scrapes_upload_shedules", array(
				'post_id' => $new_id,
				'task_id' => $this->task_id,
				'created_at' => time()
			), array('%d', '%d', '%d'));*/
			//}

			$this->saveImages($new_id, $this->getPostContent());

			WSCR_Helper::writeLog("Задача[$this->task_id] завершена...");

			return $new_id;
		}

		/**
		 * @param string $post_content
		 * @param int $post_id
		 * @throws Exception
		 */
		public function saveImages($post_id, $content)
		{
			if( empty($content) ) {
				throw new Exception(__('Не удалось получить контент спарсенной записи.', 'wbcr-scrapes') . 'Post[' . $this->task_id . '] ');
			}

			$min_width = WSCR_Helper::getMetaOption($this->task_id, 'min_width_updaload_images', 300);
			$min_height = WSCR_Helper::getMetaOption($this->task_id, 'min_height_updaload_images', 100);

			$doc = new DOMDocument();
			@$doc->loadHTML('<?xml encoding="utf-8" ?><div>' . $content . '</div>');
			$imgs = $doc->getElementsByTagName('img');
			$upload_images = array();
			$remove_imgs = array();

			if( $imgs->length ) {
				foreach($imgs as $item) {

					$image_url = $item->getAttribute('src');

					if( !empty($image_url) && substr($image_url, 0, 11) != 'data:image/' && substr($image_url, 0, 10) != 'image/gif;' ) {
						list($width, $height) = getimagesize($image_url);

						if( $min_width > $width || $min_height > $height ) {
							$remove_imgs[] = $item;
						} else {

							if( WSCR_Helper::getMetaOption($this->task_id, 'upload_images_to_local_storage', true) ) {
								global $wpdb;
								$query = "SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE '" . md5($image_url) . "%' and post_type ='attachment' and post_parent = $post_id";
								$count = $wpdb->get_var($query);

								WSCR_Helper::writeLog("download image id for post $post_id is " . $count);

								if( empty($count) ) {
									$attach_id = WSCR_Helper::generateFeaturedImage($image_url, $post_id, false);

									$item->setAttribute('src', wp_get_attachment_url($attach_id));
									$item->removeAttribute('srcset');
									$item->removeAttribute('sizes');
								} else {
									$item->setAttribute('src', wp_get_attachment_url($count));
									$item->removeAttribute('srcset');
									$item->removeAttribute('sizes');
								}
							}

							$upload_images[] = $image_url;
						}

						unset($image_url);
					}
				}

				if( !empty($remove_imgs) ) {
					foreach($remove_imgs as $img) {
						$img->parentNode->removeChild($img);
					}
				}
			}
			$doc->removeChild($doc->doctype);
			$doc->removeChild($doc->firstChild);
			$doc->replaceChild($doc->firstChild->firstChild->firstChild, $doc->firstChild);

			$content = $doc->saveHTML();
			unset($doc);

			$content = preg_replace('/<\/?div>/i', '', $content);

			/*if( empty($result_upload) ) {
				$this->setStatusWaiting();
				throw new Exception(__('Loading images failed.', 'wbcr-scrapes'));
			}*/

			$image_url = $this->getFeatureImageUrl(array(
				'upload_images' => $upload_images,
				'updated_html' => $content
			));

			if( !empty($image_url) ) {
				WSCR_Helper::generateFeaturedImage($image_url, $post_id, true);
			}

			$content = $this->uploadImagesBeforeUpdatePostContentFilter($content, $image_url);

			kses_remove_filters();
			$new_id = wp_update_post(array(
				'ID' => $post_id,
				'post_content' => $content
			));
			kses_init_filters();

			if( is_wp_error($new_id) ) {
				//$this->setStatusWaiting();
				throw new Exception(__('Can not update post. Uncertain error.', 'wbcr-scrapes') . 'Post[' . $this->task_id . '] ' . PHP_EOL . var_export($new_id->get_error_messages()));
			}
		}

		/**
		 * @param $result_upload
		 * @return null
		 */
		protected function getFeatureImageUrl($result_upload)
		{
			$image_url = null;

			if( sizeof($result_upload['upload_images']) > 1 ) {
				$image_url = $result_upload['upload_images'][1];
			}

			return $image_url;
		}

		/**
		 * Фильтр выполняет перед обновления записи, после загрузки изображений
		 *
		 * @param $content
		 * @return mixed
		 */
		protected function uploadImagesBeforeUpdatePostContentFilter($content, $image_url)
		{
			return $content;
		}
	}