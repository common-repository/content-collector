<?php
	// Exit if accessed directly
	if( !defined('ABSPATH') ) {
		exit;
	}
	
	class WSCR_SignInPage extends Wbcr_FactoryPages402_AdminPage {

		/**
		 * The id of the page in the admin menu.
		 *
		 * Mainly used to navigate between pages.
		 * @see FactoryPages402_AdminPage
		 *
		 * @since 1.0.0
		 * @var string
		 */
		public $id = "signin";

        public $internal = true;

		/**
		 * @param Wbcr_Factory401_Plugin $plugin
		 */
		public function __construct(Wbcr_Factory401_Plugin $plugin)
		{
			$this->menu_post_type = WSCR_SCRAPES_POST_TYPE;
			$this->menu_title = __('Активация', 'wbcr-scrapes');
			$this->menu_icon = "\f226";
			$this->capabilitiy = "manage_options";

			parent::__construct($plugin);

			$this->plugin = $plugin;
		}

		public function assets($scripts, $styles)
		{
			//$this->styles->add(WSCR_PLUGIN_URL . '/admin/assets/css/feed.css');
			//$this->scripts->add(WSCR_PLUGIN_URL . '/admin/assets/js/feed.js');
		}

		public function indexAction()
		{
		    if(get_option('wbcr_scr_reged')){ echo 'forbidden'; return;}
		    $user = wp_get_current_user();
		    $email = $user->user_email;
		    //wp nonce


			?>
			<h2><?=__("Активация", "wbcr-scrapes");?></h2>
            <div class="wscr-act-step1">
                <p><?=__("Плагин установлен!", "wbcr-scrapes");?></p>
                <p><?=__("Прежде чем начать пользоваться, нужно войти в систему", "wbcr-scrapes");?></p>
                <p><?=sprintf(__('Пройдите регистрацию в 1 клик, нажав кнопку "быстрая регистрация" и вы будете зарегистрированы как %s бесплатно', "wbcr-scrapes"), "<b>".$email."</b>");?></p>
                <p><?=__("На указанную почту придет письмо с доступом к личному кабинету", "wbcr-scrapes");?></p>

                <p>
                    <form action="<?= $this->getActionUrl('sign-in') ?>" method="post">
                        <input type="hidden" name="wscr_email" value="<?=$email?>">
                        <button type="submit" class="button button-default"><?=__("Быстрая регистрация", "wbcr-scrapes");?></button>
                    </form>

                </p>

                <p><?=__("или", "wbcr-scrapes");?> <a href="<?=WSCR_API_SERVER_URL.'/admin/sign-in/signup'?>"><?=__("Зарегистрироваться на сайте", "wbcr-scrapes");?></a></p>
                <p> &nbsp; </p>
                <p><?=__("В сутки можно спарсить ограниченное количество постов", "wbcr-scrapes");?></p>
                <p><?=sprintf(__("В %sличном кабинете%s Вы можете выбрать премиум тариф с более широкими возможностями для парсинга", "wbcr-scrapes"), '<a href="'.WSCR_API_SERVER_URL.'/admin/'.'" target="_blank">', '</a>');?></p>


            </div>

		<?php
		}

		public function signInAction()
		{
            if(get_option('wbcr_scr_reged')){ echo 'forbidden'; return;}
			$email = $_POST['wscr_email'];
			$domain = get_option('home');
			if(!$domain) $domain = 'http'.(($_SERVER['HTTPS'])?'s':'').'://'.$_SERVER['SERVER_NAME'];

            $default_args = array(
                'headers' => array(
                    'Accept' => 'application/json',
                ),
                'method' => 'POST',
                'timeout' => 10,
                'sslverify' => false,
                'body' => array(
                    'domain' => $domain,
                    'email' => $email
                )
            );

//            $request = wp_remote_request(WSCR_API_SERVER_URL . '/api/v1/parser/reg', $default_args);


            $success = false;
            if( !is_wp_error($request) ) {

                $body = wp_remote_retrieve_body($request);
                $body = trim($body);

                if( !empty($body) ) {
                    $new_ki = @json_decode($body, ARRAY_A);
                    if( !isset($new_ki['error']) and isset($new_ki['ki'])) {
                        $ki = $new_ki['ki'];
                        update_option('wbcr_scr_ki',$ki);
                        update_option('wbcr_scr_reged',1);
                        delete_option('wbcr_scr_cache_options');
                        $success = true;
                    }
                }
            }



			if($success){
                $this->redirectToAction('success');
            }else{
                $this->redirectToAction('fail');
            }



		}

		public function successAction(){

		    ?>
            <h2><?=__("Активация", "wbcr-scrapes");?></h2>
            <div class="wscr-act-success">
                <p><?=__("Все готово!", "wbcr-scrapes");?></p>
                <p><a href="/wp-admin/edit.php?post_type=wbcr-scrapes"><?=__("Начать пользоваться", "wbcr-scrapes");?></a></p>
            </div>
            <?php
        }

        public function failAction(){
		    ?>
            <h2><?=__("Активация", "wbcr-scrapes");?></h2>
            <p><?=__("Не удалось пройти быструю регистрацию.", "wbcr-scrapes");?></p>
            <p><?=__("Вы можете зарегистрироваться вручную", "wbcr-scrapes");?></p>
            <a href="<?=WSCR_API_SERVER_URL.'/admin/sign-in/signup'?>"><?=__("Зарегистрироваться на сайте", "wbcr-scrapes");?></a>
            <?php

        }
	}
