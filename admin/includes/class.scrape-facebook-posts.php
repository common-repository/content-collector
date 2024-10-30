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
	//&xpath_post_url=//h3[@class='entry-title td-module-title']/a
	//&post_limit=100
	//&posts_per_page=29
	//&paginate_url=https://wuzzup.ru/animals/page/2

	//$site_url = Yii::$app->request->post('site_url');
	//$page_slug = Yii::$app->request->post('page_slug');
	//$post_limit = Yii::$app->request->post('post_limit', 3);
	//$view_post_limit = Yii::$app->request->post('view_post_limit', 10);

	require_once WSCR_PLUGIN_DIR . '/admin/includes/class.scrape-posts.php';
	require_once WSCR_PLUGIN_DIR . '/admin/includes/class.extract-facebook-post.php';

	class WSC_ScrapeFacebookPosts extends WSC_ScrapePosts {

		protected $scrape_type = 'facebook_feed';
		protected $page_slug;
		protected $posts_limit;
		protected $view_posts_limit;
		protected $site_url;
		protected $use_date_filter;
		protected $date_filter_from;
		protected $date_filter_to;

		private $errors = array();

		/**
		 * @param int $task_id
		 * @throws Exception
		 */
		public function __construct($task_id)
		{
			parent::__construct($task_id);

			$this->page_slug = get_post_meta($this->task_id, 'wbcr_scrapes_facebook_group_id', true);
			$this->posts_limit = get_post_meta($this->task_id, 'wbcr_scrapes_posts_limit', true);
			$this->view_posts_limit = get_post_meta($this->task_id, 'wbcr_scrapes_load_posts_limit', true);
			$this->site_url = get_post_meta($this->task_id, 'wbcr_scrapes_site_url', true);

			if( empty($this->page_slug) || empty($this->site_url) ) {
				throw new Exception('Атрибуты {page_slug, site_url} не могут быть пустыми.');
			}

			$parse_site_url = parse_url($this->site_url);
			$this->site_url = $parse_site_url['scheme'] . "://" . $parse_site_url['host'];

            $this->use_date_filter = get_post_meta($this->task_id, 'wbcr_scrapes_facebook_date_range_filter', true);
            $this->date_filter_from = get_post_meta($this->task_id, 'wbcr_scrapes_facebook_start_date_filter__range_1', true);
            $this->date_filter_to = get_post_meta($this->task_id, 'wbcr_scrapes_facebook_start_date_filter__range_2', true);
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
				'page_slug' => $this->page_slug,
				'posts_limit' => $this->posts_limit,
				'view_posts_limit' => $this->view_posts_limit
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

		/**
		 * @param array $args
		 * @return WSC_ExtractFacebookPost[]
		 * @throws Exception
		 */
		public function execute(array $args = array())
		{
			return parent::execute($args);
		}

		/**
		 * @param $post
		 * @return WSC_ExtractPost
		 */
		protected function extractPost($post)
		{
			return new WSC_ExtractFacebookPost($this->task_id, $post);
		}


		public function getOptions()
        {
            $options = parent::getOptions();
            $options['page_slug'] = $this->page_slug;
            $options['posts_limit'] = $this->posts_limit;
            $options['view_posts_limit'] = $this->view_posts_limit;
            $options['site_url'] = $this->site_url;
            $options['use_date_filter'] = $this->use_date_filter;
            $options['date_filter_from'] = $this->date_filter_from;
            $options['date_filter_to'] = $this->date_filter_to;

            return $options;
        }
    }