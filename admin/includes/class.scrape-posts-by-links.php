<?php

	/**
	 * Class execute post
	 * @author Webcraftic <wordpress.webraftic@gmail.com>
	 * @copyright (c) 12.11.2017, Webcraftic
	 * @version 1.0
	 */

	require_once WSCR_PLUGIN_DIR . '/admin/includes/class.scrape-posts.php';

	class WSC_ScrapePostsByLinks extends WSC_ScrapePosts {

		protected $scrape_type = 'default';
		protected $links;

		private $errors = array();

		/**
		 * @param int $task_id
		 * @throws Exception
		 */
		public function __construct($task_id)
		{
			parent::__construct($task_id);

			$links = WSCR_Helper::getMetaOption($this->task_id, 'collected_links');
			$this->links = explode(PHP_EOL, $links);

			if( empty($this->links) ) {
				throw new Exception('Атрибут {links} не может быть пустым.');
			}

			$this->links = array_map(array($this, 'cleanLinks'), $this->links);
		}

		public function cleanLinks($link)
		{
			return trim(rtrim($link));
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
				'links' => $this->links
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
        $options['links'] = $this->links;
        return $options;
    }
}