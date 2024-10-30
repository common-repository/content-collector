<?php
	
	/**
	 * Helpers tools
	 * @author Webcraftic <wordpress.webraftic@gmail.com>
	 * @copyright (c) 09.11.2017, Webcraftic
	 * @version 1.0
	 */
	class WSCR_Helper {

		const DB_PREFIX = 'wbcr_scrapes_';

		private static $meta_options = array();

		public static function getDomainByUrl($url)
		{
			$parse_site_url = parse_url(esc_url($url));

			return $parse_site_url['scheme'] . "://" . $parse_site_url['host'];
		}

		/**
		 * Получает мета опцию
		 *
		 * @param int $post_id
		 * @param string $option_name
		 * @param mixed $default
		 * @return bool|int
		 */
		public static function getMetaOption($post_id, $option_name, $default = null)
		{
			if( !isset(self::$meta_options[$post_id]) || empty(self::$meta_options[$post_id]) ) {
				$meta_vals = get_post_meta($post_id, '', true);

				foreach((array)$meta_vals as $name => $val) {
					self::$meta_options[$post_id][$name] = $val[0];
				}
			}

			return isset(self::$meta_options[$post_id][self::DB_PREFIX . $option_name])
				? self::$meta_options[$post_id][self::DB_PREFIX . $option_name]
				: $default;
		}

		/**
		 * Обновляет мета опцию
		 *
		 * @param int $post_id
		 * @param string $option_name
		 * @param mixed $option_value
		 * @return bool|int
		 */
		public static function updateMetaOption($post_id, $option_name, $option_value)
		{
			return update_post_meta($post_id, self::DB_PREFIX . $option_name, $option_value);
		}

		/**
		 * Удаляет мета опцию
		 *
		 * @param int $post_id
		 * @param string $option_name
		 * @return bool|int
		 */
		public static function removeMetaOption($post_id, $option_name)
		{
			return delete_post_meta($post_id, self::DB_PREFIX . $option_name);
		}

		public static function getPostIdBySrapedUrl($url)
		{
			global $wpdb;

			$unique_check_sql = $wpdb->prepare("SELECT ID " . "FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta pm ON pm.post_id = p.ID " . "WHERE pm.meta_value = %s AND pm.meta_key = 'wbcr_scrapes_original_url' AND p.post_type = %s AND p.post_status <> 'trash'", esc_url($url), 'post');

			return $wpdb->get_var($unique_check_sql);
		}

		public static function writeLog($message, $is_error = false)
		{
			$folder = WSCR_PLUGIN_DIR . "/logs";

			if( !file_exists($folder) ) {
				mkdir($folder, 0777, true);
			}

			$handle = fopen($folder . DIRECTORY_SEPARATOR . "logs.txt", "a");
			if( is_object($message) || is_array($message) || is_bool($message) ) {
				$message = json_encode($message);
			}

			fwrite($handle, current_time('mysql') . " - PID: " . getmypid() . " - RAM: " . (round(memory_get_usage() / (1024 * 1024), 2)) . "MB - " . $message . PHP_EOL);
			if( (filesize($folder . DIRECTORY_SEPARATOR . "logs.txt") / 1024 / 1024) >= 2 ) {
				fclose($handle);
				unlink($folder . DIRECTORY_SEPARATOR . "logs.txt");
				$handle = fopen($folder . DIRECTORY_SEPARATOR . "logs.txt", "a");
				fwrite($handle, current_time('mysql') . " - " . getmypid() . " - " . self::systemInfo() . PHP_EOL);
			}
			fclose($handle);
		}

		public static function systemInfo()
		{
			global $wpdb;

			if( !function_exists('get_plugins') ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$system_info = "";
			$system_info .= "Website Name: " . get_bloginfo() . PHP_EOL;
			$system_info .= "Wordpress URL: " . site_url() . PHP_EOL;
			$system_info .= "Site URL: " . home_url() . PHP_EOL;
			$system_info .= "Wordpress Version: " . get_bloginfo('version') . PHP_EOL;
			$system_info .= "Multisite: " . (is_multisite()
					? "yes"
					: "no") . PHP_EOL;
			$system_info .= "Theme: " . wp_get_theme() . PHP_EOL;
			$system_info .= "PHP Version: " . phpversion() . PHP_EOL;
			$system_info .= "PHP Extensions: " . json_encode(get_loaded_extensions()) . PHP_EOL;
			$system_info .= "MySQL Version: " . $wpdb->db_version() . PHP_EOL;
			$system_info .= "Server Info: " . $_SERVER['SERVER_SOFTWARE'] . PHP_EOL;
			$system_info .= "WP Memory Limit: " . WP_MEMORY_LIMIT . PHP_EOL;
			$system_info .= "WP Admin Memory Limit: " . WP_MAX_MEMORY_LIMIT . PHP_EOL;
			$system_info .= "PHP Memory Limit: " . ini_get('memory_limit') . PHP_EOL;
			//$system_info .= "Wordpress Plugins: " . json_encode(get_plugins()) . PHP_EOL;
			//$system_info .= "Wordpress Active Plugins: " . json_encode(get_option('active_plugins')) . PHP_EOL;

			return $system_info;
		}

		/**
		 * @param $post_id
		 */
		public static function deletePostAttachments($post_id)
		{
			global $wpdb;

			$args = array(
				'post_type' => 'attachment',
				'post_status' => 'any',
				'posts_per_page' => -1,
				'post_parent' => $post_id
			);
			$attachments = new WP_Query($args);
			$attachment_ids = array();
			if( $attachments->have_posts() ) : while( $attachments->have_posts() ) : $attachments->the_post();
				$attachment_ids[] = get_the_id();
			endwhile;
			endif;
			wp_reset_postdata();

			if( !empty($attachment_ids) ) :
				$delete_attachments_query = $wpdb->prepare('DELETE FROM %1$s WHERE %1$s.ID IN (%2$s)', $wpdb->posts, join(',', $attachment_ids));
				$wpdb->query($delete_attachments_query);
			endif;
		}

		/**
		 * @param $str
		 * @return mixed
		 */
		public function translateMonths($str)
		{
			$languages = array(
				"en" => array(
					"January",
					"February",
					"March",
					"April",
					"May",
					"June",
					"July",
					"August",
					"September",
					"October",
					"November",
					"December"
				),
				"de" => array(
					"Januar",
					"Februar",
					"März",
					"April",
					"Mai",
					"Juni",
					"Juli",
					"August",
					"September",
					"Oktober",
					"November",
					"Dezember"
				),
				"fr" => array(
					"Janvier",
					"Février",
					"Mars",
					"Avril",
					"Mai",
					"Juin",
					"Juillet",
					"Août",
					"Septembre",
					"Octobre",
					"Novembre",
					"Décembre"
				),
				"tr" => array(
					"Ocak",
					"Şubat",
					"Mart",
					"Nisan",
					"Mayıs",
					"Haziran",
					"Temmuz",
					"Ağustos",
					"Eylül",
					"Ekim",
					"Kasım",
					"Aralık"
				),
				"nl" => array(
					"Januari",
					"Februari",
					"Maart",
					"April",
					"Mei",
					"Juni",
					"Juli",
					"Augustus",
					"September",
					"Oktober",
					"November",
					"December"
				)
			);
			
			$languages_abbr = $languages;
			
			foreach($languages_abbr as $locale => $months) {
				$languages_abbr[$locale] = array_map(array($this, 'monthAbbr'), $months);
			}
			
			foreach($languages as $locale => $months) {
				$str = str_ireplace($months, $languages["en"], $str);
			}
			foreach($languages_abbr as $locale => $months) {
				$str = str_ireplace($months, $languages_abbr["en"], $str);
			}
			
			return $str;
		}

		/**
		 * @param $month
		 * @return string
		 */
		public static function monthAbbr($month)
		{
			return mb_substr($month, 0, 3);
		}

		/**
		 * @param $start_time
		 * @param $modify_time
		 * @param $post_id
		 * @return bool
		 */
		public static function checkTerminate($start_time, $modify_time, $post_id)
		{
			clean_post_cache($post_id);
			
			if( $start_time != get_post_meta($post_id, "scrape_start_time", true) && get_post_meta($post_id, 'scrape_stillworking', true) == 'terminate' ) {
				
				return true;
			}
			
			if( get_post_status($post_id) == 'trash' || get_post_status($post_id) === false ) {
				
				return true;
			}
			
			$check_modify_time = get_post_modified_time('U', null, $post_id);
			if( $modify_time != $check_modify_time && $check_modify_time !== false ) {
				
				return true;
			}
			
			return false;
		}

		/**
		 * @param $html_string
		 * @param $base_url
		 * @param $html_base_url
		 * @return string
		 */
		public static function convertHtmlLinks($html_string, $base_url, $html_base_url)
		{
			if( empty($html_string) ) {
				return "";
			}
			$doc = new DOMDocument();
			@$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html_string);
			$imgs = $doc->getElementsByTagName('img');
			if( $imgs->length ) {
				foreach($imgs as $item) {
					$data_src = $item->getAttribute('data-src');
					$src = trim($item->getAttribute('src'));
					if( !empty($data_src) && (substr($src, 0, 11) == 'data:image/' || substr($src, 0, 10) == 'image/gif;') ) {
						$item->setAttribute('src', self::createAbsoluteUrl($data_src, $base_url, $html_base_url));
					} else {
						$item->setAttribute('src', self::createAbsoluteUrl($src, $base_url, $html_base_url));
					}
				}
			}
			$a = $doc->getElementsByTagName('a');
			if( $a->length ) {
				foreach($a as $item) {
					$item->setAttribute('href', self::createAbsoluteUrl($item->getAttribute('href'), $base_url, $html_base_url));
				}
			}
			$doc->removeChild($doc->doctype);
			$doc->removeChild($doc->firstChild);
			$doc->replaceChild($doc->firstChild->firstChild->firstChild, $doc->firstChild);
			
			return $doc->saveHTML();
		}

		public static function convertReadableHtml($html_string)
		{
			
			require_once WSCR_PLUGIN_DIR . "/libs/class-readability.php";
			
			$readability = new Wbcr_Readability($html_string);
			$readability->debug = false;
			$readability->convertLinksToFootnotes = false;
			$result = $readability->init();
			if( $result ) {
				$content = $readability->getContent()->innerHTML;
				if( function_exists('tidy_parse_string') ) {
					$tidy = tidy_parse_string($content, array('indent' => true, 'show-body-only' => true), 'UTF8');
					$tidy->cleanRepair();
					$content = $tidy->value;
				}
				
				return $content;
			} else {
				return '';
			}
		}
		
		public static function requirementsCheck()
		{
			$min_wp = '3.5';
			$min_php = '5.2.4';
			$exts = array('dom', 'mbstring', 'iconv', 'json', 'simplexml');

			$errors = array();

			if( version_compare(get_bloginfo('version'), $min_wp, '<') ) {
				$errors[] = __("Your WordPress version is below 3.5. Please update.", "ol-scrapes");
			}

			if( version_compare(PHP_VERSION, $min_php, '<') ) {
				$errors[] = __("PHP version is below 5.2.4. Please update.", "ol-scrapes");
			}

			foreach($exts as $ext) {
				if( !extension_loaded($ext) ) {
					$errors[] = sprintf(__("PHP extension %s is not loaded. Please contact your server administrator or visit http://php.net/manual/en/%s.installation.php for installation.", "ol-scrapes"), $ext, $ext);
				}
			}

			$folder = plugin_dir_path(__FILE__) . "../logs";

			if( !is_dir($folder) && mkdir($folder, 0755) === false ) {
				$errors[] = sprintf(__("%s folder is not writable. Please update permissions for this folder to chmod 755.", "ol-scrapes"), $folder);
			}

			if( fopen($folder . DIRECTORY_SEPARATOR . "logs.txt", "a") === false ) {
				$errors[] = sprintf(__("%s folder is not writable therefore logs.txt file could not be created. Please update permissions for this folder to chmod 755.", "ol-scrapes"), $folder);
			}
			
			return $errors;
		}
		
		public static function createAbsoluteUrl($rel, $base, $html_base)
		{
			
			if( substr($rel, 0, 11) == 'data:image/' ) {
				return $rel;
			}
			
			if( !is_null($html_base) ) {
				$base = $html_base;
			}
			
			return WP_Http::make_absolute_url($rel, $base);
		}

		public static function generateFeaturedImage($image_url, $post_id, $featured = true)
		{
			self::writeLog($image_url . " thumbnail controls");
			$meta_vals = get_post_meta($post_id);
			$upload_dir = wp_upload_dir();

			$filename = md5($image_url);

			global $wpdb;
			$query = "SELECT ID FROM {$wpdb->posts} WHERE post_title LIKE '" . $filename . "%' and post_type ='attachment' and post_parent = $post_id";
			$image_id = $wpdb->get_var($query);

			self::writeLog("found image id for $post_id : " . $image_id);

			if( empty($image_id) ) {
				if( wp_mkdir_p($upload_dir['path']) ) {
					$file = $upload_dir['path'] . '/' . $filename;
				} else {
					$file = $upload_dir['basedir'] . '/' . $filename;
				}

				if( substr($image_url, 0, 11) == 'data:image/' ) {
					$image_data = array(
						'body' => base64_decode(substr($image_url, strpos($image_url, 'base64') + 7))
					);
				} else {
					$args = array(
						'timeout' => 30,
						'sslverify' => false,
						'user-agent' => get_option('scrape_user_agent')
					);

					if( !empty($meta_vals['scrape_cookie_names']) ) {
						$args['cookies'] = array_combine(array_values(unserialize($meta_vals['scrape_cookie_names'][0])), array_values(unserialize($meta_vals['scrape_cookie_values'][0])));
					}

					$image_data = wp_remote_get($image_url, $args);
					if( is_wp_error($image_data) ) {
						self::writeLog("http error in " . $image_url . " " . $image_data->get_error_message(), true);

						return;
					}
				}

				$mimetype = getimagesizefromstring($image_data['body']);
				if( $mimetype === false ) {
					self::writeLog("mime type of image can not be found");

					return;
				}

				$mimetype = $mimetype["mime"];
				$extension = substr($mimetype, strpos($mimetype, "/") + 1);
				$file .= ".$extension";

				file_put_contents($file, $image_data['body']);

				$attachment = array(
					'post_mime_type' => $mimetype,
					'post_title' => $filename . ".$extension",
					'post_content' => '',
					'post_status' => 'inherit'
				);

				$attach_id = wp_insert_attachment($attachment, $file, $post_id);

				self::writeLog("attachment id : " . $attach_id . " mime type: " . $mimetype . " added to media library.");

				require_once(ABSPATH . 'wp-admin/includes/image.php');
				$attach_data = wp_generate_attachment_metadata($attach_id, $file);
				wp_update_attachment_metadata($attach_id, $attach_data);
				if( $featured ) {
					set_post_thumbnail($post_id, $attach_id);
				}

				unset($attach_data);
				unset($image_data);
				unset($mimetype);

				return $attach_id;
			} else if( $featured ) {
				self::writeLog("image already exists set thumbnail for post " . $post_id . " to " . $image_id);
				set_post_thumbnail($post_id, $image_id);
			}

			return $image_id;
		}
		
		public static function detectHtmlEncodingAndReplace($header, &$body)
		{
			$charset_regex = preg_match("/<meta(?!\s*(?:name|value)\s*=)(?:[^>]*?content\s*=[\s\"']*)?([^>]*?)[\s\"';]*charset\s*=[\s\"']*([^\s\"'\/>]*)[\s\"']*\/?>/i", $body, $matches);
			if( empty($header) ) {
				$charset_header = false;
			} else {
				$charset_header = explode(";", $header);
				if( count($charset_header) == 2 ) {
					$charset_header = $charset_header[1];
					$charset_header = explode("=", $charset_header);
					$charset_header = strtolower(trim($charset_header[1]));
				} else {
					$charset_header = false;
				}
			}
			if( $charset_regex ) {
				$charset_meta = strtolower($matches[2]);
				if( $charset_meta != "utf-8" ) {
					$body = str_replace($matches[0], "<meta charset='utf-8'>", $body);
				}
			} else {
				$charset_meta = false;
			}

			$charset_php = strtolower(mb_detect_encoding($body, mb_list_encodings(), false));

			if( $charset_header && $charset_meta ) {
				return $charset_header;
			}

			if( !$charset_header && !$charset_meta ) {
				return $charset_php;
			} else {
				return !empty($charset_meta)
					? $charset_meta
					: $charset_header;
			}
		}
	}

	if( !function_exists('getimagesizefromstring') ) {
		function getimagesizefromstring($string_data)
		{
			$uri = 'data://application/octet-stream;base64,' . base64_encode($string_data);

			return getimagesize($uri);
		}
	}