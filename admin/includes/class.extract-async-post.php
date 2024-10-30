<?php
require_once WSCR_PLUGIN_DIR . '/admin/includes/class.extract-post.php';


class WSC_ExtractAsyncPost extends WSC_ExtractPost{

    /**
      save post without images
     */
    function savePostWithContent($hide=false){
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

        if($hide){
            $post_status = 'wbcr_loading';
        }

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



        return $new_id;
    }


    static function downloadImage($image_url, $post_id){
        $attach_id = WSCR_Helper::generateFeaturedImage($image_url, $post_id, false);
        return wp_get_attachment_url($attach_id);
    }

    function savePostWithImages($post_id, $content, $imageReplace){
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
                                //$attach_id = WSCR_Helper::generateFeaturedImage($image_url, $post_id, false);
                                $new_url = null;
                                foreach ($imageReplace as $replace){
                                    if($image_url == $replace['original_url']){
                                        $new_url = $replace['new_url'];
                                        break;
                                    }
                                }
                                if($new_url !== null){
                                    $item->setAttribute('src', $new_url);
                                    $item->removeAttribute('srcset');
                                    $item->removeAttribute('sizes');
                                }else{
//                                    $remove_imgs[] = $item;
                                }

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

        /*$image_url = $this->getFeatureImageUrl(array(
            'upload_images' => $upload_images,
            'updated_html' => $content
        ));*/



        if( count($imageReplace) >= 1) {
            $image_url =  $imageReplace[0]['new_url'];
            WSCR_Helper::generateFeaturedImage($image_url, $post_id, true);
            //TODO
        }

        $content = $this->uploadImagesBeforeUpdatePostContentFilter($content, $image_url);

        $post_status = WSCR_Helper::getMetaOption($this->task_id, 'post_status', 'draft');

        kses_remove_filters();
        $new_id = wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $content,
            'post_status' => $post_status
        ));
        kses_init_filters();

        if( is_wp_error($new_id) ) {
            //$this->setStatusWaiting();
            throw new Exception(__('Can not update post. Uncertain error.', 'wbcr-scrapes') . 'Post[' . $this->task_id . '] ' . PHP_EOL . var_export($new_id->get_error_messages()));
        }


    }

}