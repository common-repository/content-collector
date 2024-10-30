<?php
	/**
	 * The file contains a short help info.
	 *
	 * @author Alex Kovalev <alex.kovalevv@gmail.com>
	 * @copyright (c) 2018, OnePress Ltd
	 *s
	 * @package core
	 * @since 1.0.0
	 */

	// Exit if accessed directly
	if( !defined('ABSPATH') ) {
		exit;
	}

	/**
	 * Common Settings
	 */
	class WSCR_SettingsPage extends Wbcr_FactoryPages402_AdminPage {

		/**
		 * @param Wbcr_Factory401_Plugin $plugin
		 */
		public function __construct(Wbcr_Factory401_Plugin $plugin)
		{
			$this->menu_post_type = WSCR_SCRAPES_POST_TYPE;

			/*if( !current_user_can('administrator') ) {
				$this->capabilitiy = "read_wbcr-scrapes";
			}*/

			$this->id = "scrapes_settings";
			$this->menu_title = __('Общие настройки', 'wbcr-scrapes');

			parent::__construct($plugin);

			$this->plugin = $plugin;
		}

		public function assets($scripts, $styles)
		{
			$this->scripts->request('jquery');

			$this->scripts->request(array(
				'control.checkbox',
				'control.dropdown'
			), 'bootstrap');

			$this->styles->request(array(
				'bootstrap.core',
				'bootstrap.form-group',
				'bootstrap.separator',
				'control.dropdown',
				'control.checkbox',
			), 'bootstrap');
		}

		/**
		 * Returns options for the Basic Settings screen.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public function getOptions()
		{

			$options = array();

			$options[] = array(
				'type' => 'separator'
			);

			$options[] = array(
				'type' => 'html',
				'html' => array($this, 'step1')
			);

			$options[] = array(
				'type' => 'textbox',
				'way' => 'buttons',
				'name' => 'parser_app_id',
				'title' => __('ID приложения', 'wbcr-scrapes'),
				'default' => false,
				'hint' => __('Введите ID приложения в Facebook.', 'wbcr-scrapes')
			);

			$options[] = array(
				'type' => 'textbox',
				'way' => 'buttons',
				'name' => 'parser_app_secret',
				'title' => __('Секретный ключ приложения', 'wbcr-scrapes'),
				'default' => false,
				'hint' => __('Введите секретный ключ приложения в Facebook.', 'wbcr-scrapes')
			);

			$options[] = array(
				'type' => 'html',
				'html' => array($this, 'step2')
			);

			$options[] = array(
				'type' => 'html',
				'html' => array($this, 'authorizeButton')
			);

			$options[] = array(
				'type' => 'separator'
			);

			return $options;
		}


		public function indexAction()
		{

			// creating a form
			$form = new Wbcr_FactoryForms401_Form(array(
				'scope' => 'wbcr_scrapes',
				'name' => 'setting'
			), $this->plugin);

			$form->setProvider(new Wbcr_FactoryForms401_OptionsValueProvider($this->plugin));

			$form->add($this->getOptions());

			if( isset($_POST['wbcr_scrapes_saved']) ) {
				$form->save();
			}

			?>
			<div class="wrap ">
				<div class="factory-bootstrap-401 factory-fontawesome-321">
					<h3><?=__("Общие настройки парсера", "wbcr-scrapes");?></h3>

					<form method="post" class="form-horizontal">
						<?php if( isset($_GET['wbcr_scrapes_saved']) ) { ?>
							<div id="message" class="alert alert-success">
								<p><?php _e('The settings have been updated successfully!', 'wbcr-scrapes') ?></p>
							</div>
						<?php } ?>


						<?php if( isset($_GET['wbcr-oauth-error']) || isset($_GET['wbcr-empty-list-accounts']) ): ?>
							<div id="message" class="alert alert-danger">
								<?php if( isset($_GET['wbcr-oauth-error']) ): ?>
									<p><?php _e('Ошибка авторизации! Произошла неизвестная ошибка при авторизации приложения.', 'wbcr-scrapes') ?></p>
								<?php endif; ?>
								<?php if( isset($_GET['wbcr-empty-list-accounts']) ): ?>
									<p><?php _e('Ошибка! У приложения нет подключенных facebook страниц.', 'wbcr-scrapes') ?></p>
								<?php endif; ?>
							</div>
						<?php endif; ?>
						
						<div style="padding-top: 10px;">
							<?php $form->html(); ?>
						</div>
						<div class="form-group form-horizontal">
							<label class="col-sm-2 control-label"> </label>

							<div class="control-group controls col-sm-10">
								<input name="wbcr_scrapes_saved" class="btn btn-primary" type="submit" value="<?php _e('Сохранить настройки', 'wbcr-scrapes') ?>"/>
							</div>
						</div>
					</form>
				</div>
			</div>
		<?php
		}

		/*public function activateAction()
		{
			global $wpdb;

			$app_id = $this->request->get('app_id', null, 'intval');
			$support_tor = $this->request->get('support_tor', false, 'intval');

			check_admin_referer('activate_app_' . $app_id);

			$get_app = $wpdb->get_results($wpdb->prepare("
						SELECT *
						FROM {$wpdb->prefix}wbcr_autoposter_apps
						WHERE app_id = '%s'", $app_id));

			if( !empty($get_app) ) {
				if( $get_app[0]->status ) {
					$update_result = $wpdb->update("{$wpdb->prefix}wbcr_autoposter_apps", array(
						'access_token' => '',
						'status' => 0,
						'pages' => '',
						'error_message' => ''
					), array('app_id' => $app_id), array('%s', '%d', '%s'), array('%d'));

					if( !$update_result ) {
						$this->redirectToAction('index', array('wbcr-deactivate-error' => 1));
					}
				} else {
					$redirect_args = array(
						'app_id' => $get_app[0]->app_id,
						'app_secret' => $get_app[0]->secret
					);

					if( $support_tor ) {
						$redirect_args['support_tor'] = 1;
					}

					$this->redirectToAction('authorize', $redirect_args);
				}
			}

			$this->redirectToAction('index');
		}*/

		public function authorizeAction()
		{
			global $wpdb;

			$app_id = $this->request->get('app_id', null, 'intval');
			$app_secret = $this->request->get('app_secret', null, true);

			$redirecturl = admin_url($this->getActionUrl('authorize'));
			$redirecturl = add_query_arg(array('app_id' => $app_id, 'app_secret' => $app_secret), $redirecturl);

			$request_code = $this->request->request('code', null, true);
			$request_state = $this->request->request('state', null, true);

			if( empty($app_id) || empty($app_secret) ) {
				$this->redirectToAction('index', array('wbcr-oauth-error' => 1));
			}

			$fb = new Facebook\Facebook(array(
				'app_id' => (int)$app_id,
				'app_secret' => $app_secret,
				'cookie' => true
			));

			$client = $fb->getOAuth2Client();

			if( !$request_code ) {

				$session_state = wp_create_nonce('oauth_facebook_app_' . $app_id . '_secret' . $app_secret);

				$authorization_url = $client->getAuthorizationUrl($redirecturl, $session_state, array(
					'manage_pages'
				));

				wp_redirect($authorization_url);
			} else {

				if( wp_verify_nonce($request_state, 'oauth_facebook_app_' . $app_id . '_secret' . $app_secret) ) {

					try {
						$request_token = $client->getAccessTokenFromCode($request_code, $redirecturl);

						if( !empty($request_token) ) {
							$access_token = $request_token->getValue();

							// Получаем аккаунты подключенные к приложению
							$accounts = $fb->sendRequest('GET', 'me/accounts', array('limit' => 100), $access_token);
							$accounts_decode_body = $accounts->getDecodedBody();

							if( $accounts->getHttpStatusCode() == 200 && isset($accounts_decode_body['data']) ) {
								if( empty($accounts_decode_body['data']) ) {
									$this->redirectToAction('index', array('wbcr-empty-list-accounts' => 1));
								}

								update_option('wbcr_scrapes_parser_access_token', $access_token);
							} else {
								$this->redirectToAction('index', array('wbcr-oauth-error' => 1));
							}
						}
					} catch( Facebook\Exceptions\FacebookResponseException $e ) {
						if( $e->getCode() == 100 ) {
							echo $redirecturl . '<br>';
						}

						echo $e->getMessage();
						exit;
					}
				}

				$this->redirectToAction('index');
			}
		}

		public function step1()
		{
			?>
			<h4 style="margin-left:30px">
				<span style="display: inline-block;padding: 13px 17px;background: #2ea2cc;border-radius: 100%;color: #fff;">1</span>
				<?=__("Заполните обязательные данные по вашему приложению", "wbcr-scrapes");?>
			</h4>

		<?php
		}

		public function step2()
		{
			?>
			<h4 style="margin-left:30px">
				<span style="display: inline-block;padding: 13px 17px;background: #2ea2cc;border-radius: 100%;color: #fff;">2</span>
				<?=__("Авторизуйтесь, чтобы получить маркер доступа", "wbcr-scrapes");?>
			</h4>
		<?php
		}

		public function authorizeButton()
		{
			$app_id = get_option('wbcr_scrapes_parser_app_id');
			$app_secret = get_option('wbcr_scrapes_parser_app_secret');
			$access_token = get_option('wbcr_scrapes_parser_access_token');

			$button_disabled = '';

			if( empty($access_token) ) {
				$action_url = wp_nonce_url($this->getActionUrl('authorize', array(
					'app_id' => $app_id,
					'app_secret' => $app_secret
				)), $this->getResultId() . '_' . 'authorize');
			} else {
				$action_url = wp_nonce_url($this->getActionUrl('app-logout'), $this->getResultId() . '_' . 'logout');
			}

			if( empty($app_id) || empty($app_secret) ) {
				$button_disabled = ' disabled';
				$action_url = '';
			}
			?>
			<div class="form-group form-group-checkbox factory-control-authorize">
				<label for="wbcr_scrapes_authorize" class="col-sm-2 control-label"></label>

				<div class="control-group col-sm-10">
					<a href="<?= esc_attr($action_url) ?>" class="button button-default<?= $button_disabled ?>">
						<?php if( empty($access_token) ): ?>
							<?php _e('Авторизоваться', 'wbcr-scrapes'); ?>
						<?php else: ?>
							<?php _e('Сбросить настройки приложения', 'wbcr-scrapes'); ?>
						<?php endif; ?>
					</a>
				</div>
			</div>
		<?php
		}

		public function appLogoutAction()
		{
			check_admin_referer($this->getResultId() . '_' . 'logout');

			delete_option('wbcr_scrapes_parser_app_id');
			delete_option('wbcr_scrapes_parser_app_secret');
			delete_option('wbcr_scrapes_parser_access_token');

			$this->redirectToAction('index');
		}
	}
