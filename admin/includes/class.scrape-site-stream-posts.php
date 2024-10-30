<?php

	/**
	 * Class execute post
	 * @author Webcraftic <wordpress.webraftic@gmail.com>
	 * @copyright (c) 12.11.2017, Webcraftic
	 * @version 1.0
	 */

	//&scrape_type=site_stream
	//&site_url=https://buff.ly&page_slug=wuzzupfeed
	//&xpath_post_title=//h1[contains(@class, 'entry-title')]
	//&xpath_post_content=//div[contains(@class, 'td-post-content') and contains(@class, 'td-pb-padding-side')]
	//&xpath_post_title_url=//h3[@class='entry-title td-module-title']/a
	//&post_limit=100
	//&posts_per_page=29
	//&paginate_url=https://wuzzup.ru/animals/page/2

	//$site_url = Yii::$app->request->post('site_url');
	//$page_slug = Yii::$app->request->post('page_slug');
	//$post_limit = Yii::$app->request->post('post_limit', 3);
	//$view_post_limit = Yii::$app->request->post('view_post_limit', 10);

	require_once WSCR_PLUGIN_DIR . '/admin/includes/class.scrape-posts.php';

	class WSC_ScrapeSiteSreamPosts extends WSC_ScrapePosts {

		protected $scrape_type = 'site_stream';
		protected $paginate_url;
		protected $xpath_post_title_url;
		protected $posts_limit;
		protected $posts_per_page;
		protected $site_url;

		private $errors = array();

		/**
		 * @param int $task_id
		 * @throws Exception
		 */
		public function __construct($task_id)
		{
			parent::__construct($task_id);
			//post_per_page
			
			$this->paginate_url = WSCR_Helper::getMetaOption($this->task_id, 'paginate_url');
			$this->xpath_post_title_url = WSCR_Helper::getMetaOption($this->task_id, 'xpath_post_title_url');
			$this->posts_limit = WSCR_Helper::getMetaOption($this->task_id, 'posts_limit', 3);
			$this->posts_per_page = WSCR_Helper::getMetaOption($this->task_id, 'post_per_page', 10);
			$this->site_url = WSCR_Helper::getMetaOption($this->task_id, 'site_url');

			if( empty($this->paginate_url) || empty($this->site_url) ) {
				throw new Exception('Атрибуты {paginate_url, site_url} не могут быть пустыми.');
			}

			$this->site_url = WSCR_Helper::getDomainByUrl($this->site_url);
		}

		/**
		 * @return WSC_ExtractFacebookPost[]
		 * @throws Exception
		 */
		public function run()
		{

			// start task

			$args = array(
				'scrape_type' => $this->scrape_type,
				'site_url' => $this->site_url,
				'paginate_url' => $this->paginate_url,
				'xpath_post_url' => $this->xpath_post_title_url,
				'posts_limit' => $this->posts_limit,
				'posts_per_page' => $this->posts_per_page
			);

			$posts = $this->execute($args);

			if( empty($posts) ) {
				throw new Exception('Нет доступных записей для парсинга!');
			}

			$success_saved_posts = array();

			foreach((array)$posts as $post) {
				$post_id = $post->savePost();

				if( empty($post_id) ) {
					$this->errors[] = 'Неудалось сохранить запись!';
				} else {
					$success_saved_posts[] = $post;
				}
			}

			// end taks

			return $success_saved_posts;
		}

        public function getOptions()
        {
            $options = parent::getOptions();
            $options['paginate_url'] = $this->paginate_url;
            $options['xpath_post_url'] = $this->xpath_post_title_url;
            $options['posts_limit'] = $this->posts_limit;
            $options['posts_per_page'] = $this->posts_per_page;
            $options['site_url'] = $this->site_url;
            return $options;
        }
	}