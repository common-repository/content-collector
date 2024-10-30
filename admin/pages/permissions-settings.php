<?php
	// Exit if accessed directly
	if( !defined('ABSPATH') ) {
		exit;
	}

	class WSCR_ParserPermissionsSettings extends Wbcr_FactoryPages402_AdminPage {
		
		public $id = 'parser_permissions';
		
		/**
		 * @param Wbcr_Factory401_Plugin $plugin
		 */
		public function __construct(Wbcr_Factory401_Plugin $plugin)
		{
			global $wp_roles;

			$this->menu_post_type = WSCR_SCRAPES_POST_TYPE;
			$this->menu_title = __('Настройка доступа', 'wbcr-scrapes');
			$this->menu_icon = "\f226";
			$this->capabilitiy = "manage_options";

			parent::__construct($plugin);

			$this->plugin = $plugin;
			
			$this->wp_roles = $wp_roles;
			
			if( !isset($wp_roles) ) {
				$this->wp_roles = new WP_Roles();
			}
			
			$this->roles = $this->wp_roles->get_names();
		}

		/**
		 * Requests assets (js and css) for the page.
		 *
		 * @see FactoryPages402_AdminPage
		 *
		 * @since 1.0.0
		 * @return void
		 */
		public function assets($scripts, $styles)
		{

			$this->scripts->request('jquery');

			$this->scripts->request(array(
				'control.checkbox',
				'control.dropdown',
				'plugin.ddslick',
				'holder.more-link'
			), 'bootstrap');

			$this->styles->request(array(
				'bootstrap.core',
				'bootstrap.form-group',
				'bootstrap.separator',
				'control.dropdown',
				'control.checkbox',
				'holder.more-link'
			), 'bootstrap');
		}


		/**
		 * Returns options for the Basic Settings screen.
		 * @return array
		 */
		public function getOptions()
		{
			
			$options = array();
			
			$options[] = array(
				'type' => 'separator'
			);
			
			foreach($this->roles as $role_value => $role_name) {
				if( $role_value == 'administrator' ) {
					continue;
				}
				
				$options[] = array(
					'type' => 'checkbox',
					'way' => 'buttons',
					'name' => 'user_role_' . $role_value,
					'title' => sprintf(__('Роль %s', 'wbcr-scrapes'), $role_name),
					'default' => false,
					'hint' => sprintf(__('Предоставляет доступ для пользователей с ролью %s.', 'wbcr-scrapes'), $role_name)
				);
			}

			$options[] = array(
			        'type' => 'separator'
            );


            $options[] = array(
                'type' => 'textbox',
                'way' => 'buttons',
                'name' => 'ki',
                'title' => __('Ключ активации', 'wbcr-scrapes'),
                'default' => '',
//                'hint' => __('', 'wbcr-scrapes')
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

				foreach($this->roles as $role_value => $role_name) {
					if( $role_value == 'administrator' ) {
						continue;
					}

					$this->editCapabilityOption($role_value, 'edit');
					$form->save();
				}
			}

			?>
			<div class="wrap ">
				<div class="factory-bootstrap-401 factory-fontawesome-321">
					<form method="post" class="form-horizontal">
						<?php if( isset($_GET['wbcr_scrapes_saved']) ) { ?>
							<div id="message" class="alert alert-success">
								<p><?php _e('The settings have been updated successfully!', 'wbcr-scrapes') ?></p>
							</div>
						<?php } ?>

						<div style="padding-top: 10px;">
							<?php $form->html(); ?>
						</div>
						<div class="form-group form-horizontal">
							<label class="col-sm-2 control-label"> </label>

							<div class="control-group controls col-sm-10">
								<input name="wbcr_scrapes_saved" class="btn btn-primary" type="submit" value="<?php _e('Save Changes', 'wbcr-scrapes') ?>"/>
							</div>
						</div>
					</form>
				</div>
			</div>
		<?php
		}

		public function editCapabilityOption($role_name, $capabilityPrefix)
		{
            if(isset($_POST['wbcr_scrapes_ki'])){
                $new_k = trim($_POST['wbcr_scrapes_ki']);
                delete_option('wsc_tasks');
            }


			$role = $GLOBALS ['wp_roles']->role_objects[$role_name];

			if( isset($_POST['wbcr_scrapes_user_role_' . $role_name]) && !empty($_POST['wbcr_scrapes_user_role_' . $role_name]) ) {
				
				//if( $capabilityPrefix != 'edit' ) {
				$this->wp_roles->add_cap($role_name, 'manage_wbcr-scrapes_' . $capabilityPrefix);
				//} else {
				$this->wp_roles->add_cap($role_name, 'read_wbcr-scrapes');
				$this->wp_roles->add_cap($role_name, 'read_private_wbcr-scrapess');
				$this->wp_roles->add_cap($role_name, 'delete_wbcr-scrapes');
				$this->wp_roles->add_cap($role_name, 'delete_wbcr-scrapess');
				$this->wp_roles->add_cap($role_name, 'edit_wbcr-scrapes');
				$this->wp_roles->add_cap($role_name, 'edit_wbcr-scrapess');
				$this->wp_roles->add_cap($role_name, 'edit_others_wbcr-scrapess');
				$this->wp_roles->add_cap($role_name, 'publish_wbcr-scrapess');
				//}
			} else {

				//if( $role->has_cap('manage_wbcr-scrapes_' . $capabilityPrefix) && $capabilityPrefix != 'edit' ) {
				//$role->remove_cap('manage_wbcr-scrapes_' . $capabilityPrefix);
				//} else if( $capabilityPrefix == 'edit' ) {
				if( $role->has_cap('read_wbcr-scrapes') ) {
					$this->wp_roles->remove_cap($role_name, 'read_wbcr-scrapes');
				}
				if( $role->has_cap('read_private_wbcr-scrapess') ) {
					$this->wp_roles->remove_cap($role_name, 'read_private_wbcr-scrapess');
				}
				if( $role->has_cap('delete_wbcr-scrapes') ) {
					$this->wp_roles->remove_cap($role_name, 'delete_wbcr-scrapes');
				}
				if( $role->has_cap('delete_wbcr-scrapess') ) {
					$this->wp_roles->remove_cap($role_name, 'delete_wbcr-scrapess');
				}
				if( $role->has_cap('edit_wbcr-scrapes') ) {
					$this->wp_roles->remove_cap($role_name, 'edit_wbcr-scrapes');
				}
				if( $role->has_cap('edit_wbcr-scrapess') ) {
					$this->wp_roles->remove_cap($role_name, 'edit_wbcr-scrapess');
				}
				if( $role->has_cap('edit_others_wbcr-scrapess') ) {
					$this->wp_roles->remove_cap($role_name, 'edit_others_wbcr-scrapess');
				}
				if( $role->has_cap('publish_wbcr-scrapess') ) {
					$this->wp_roles->remove_cap($role_name, 'publish_wbcr-scrapess');
				}
				//}
			}
		}
	}
