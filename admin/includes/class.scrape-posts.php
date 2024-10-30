<?php

	/**
	 * Class execute post
	 * @author Webcraftic <wordpress.webraftic@gmail.com>
	 * @copyright (c) 12.11.2017, Webcraftic
	 * @version 1.0
	 */

	require_once WSCR_PLUGIN_DIR . '/admin/includes/class.extract-post.php';

	abstract class WSC_ScrapePosts {

		/**
		 * @var int id выполняемой задачи
		 */
		protected $task_id;

		/**
		 * @var string xpath путь к заголовку записи на извлекаемой странице
		 */
		protected $xpath_post_title;

		/**
		 * @var string xpath путь к содержанию записи на извлекаемой странице
		 */
		protected $xpath_post_content;

		/**
		 * @var array xpath филтры содержания записи на извлекаемой странице
		 */
		protected $xpath_post_filters;


		public function __construct($task_id)
		{
			if( empty($task_id) ) {
				throw new Exception('Attributes {task_id} is empty.');
			}

			$this->task_id = (int)$task_id;

			clean_post_cache($this->task_id);

			$this->xpath_post_title = get_post_meta($this->task_id, 'wbcr_scrapes_post_title', true);
			$this->xpath_post_content = get_post_meta($this->task_id, 'wbcr_scrapes_post_content', true);

			// array
			$this->xpath_post_filters = get_post_meta($this->task_id, 'wbcr_scrapes_html_filters', true);
		}


		abstract function run();


		/**
		 * @param array $args
		 * @return WSC_ExtractPost[]
		 * @throws Exception
		 */
		public function execute(array $args = array())
		{

		    $filters = explode('{%spr%}',$this->xpath_post_filters);

			$default_args = array(
				'headers' => array(
					'Accept' => 'application/json',
				),
				'method' => 'POST',
				'timeout' => 10,
                'sslverify' => false,
				'body' => array_merge(array(
					'xpath_post_title' => $this->xpath_post_title,
					'xpath_post_content' => $this->xpath_post_content,
					'xpath_content_filters' => $filters
				), $args)
			);
            if(defined('WSC_KI')) $default_args['body']['ki'] = WSC_KI;

			$request = wp_remote_request(WSCR_API_SERVER_URL . '/api/v1/parser/scrape-site-content', $default_args);

			if( is_wp_error($request) ) {
				throw new Exception($request->get_error_message());
			}

			$body = wp_remote_retrieve_body($request);
			$body = trim($body);

			if( empty($body) ) {
				throw new Exception($request->get_error_message());
			}


			$posts = @json_decode($body, ARRAY_A);

			if( isset($posts['error'])) {
			    if($posts['code'] == 1202){
                    throw new Exception(wbcr_scrapes_msg_by_code(1202), 1202);


                }else{
                    throw new Exception($posts['error']);
                }

			}
            if( @$posts['status'] == 500){
                throw new Exception('Server: '.$posts['status'] .' '.$posts['name']);
            }

			$collection = array();
			foreach((array)$posts as $post) {
				$collection[] = $this->extractPost($post);
			}

			return $collection;
		}

		/**
		 * @param array $post
		 * @return WSC_ExtractPost
		 */
		protected function extractPost($post)
		{
			return new WSC_ExtractPost($this->task_id, $post);
		}

		public function getOptions(){
            $filters = explode('{%spr%}', $this->xpath_post_filters);
		    return [
		        'xpath_post_title' => $this->xpath_post_title,
		        'xpath_post_content' => $this->xpath_post_content,
		        'xpath_content_filters' => $filters,
            ];
        }
	}