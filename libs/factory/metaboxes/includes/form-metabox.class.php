<?php
	/**
	 * The file contains a class for creating metaboxes with forms based on the Factory Forms module.
	 *
	 * @author Alex Kovalev <alex.kovalevv@gmail.com>
	 * @copyright (c) 2018, Webcraftic Ltd
	 *
	 * @package factory-metaboxes
	 * @since 1.0.0
	 */

	// Exit if accessed directly
	if( !defined('ABSPATH') ) {
		exit;
	}

	if( !class_exists('Wbcr_FactoryMetaboxes401_FormMetabox') ) {

		/**
		 * A class extending FactoryMetaboxes_Metabox and adding ability to create and save forms.
		 *
		 * @since 1.0.0
		 */
		abstract class Wbcr_FactoryMetaboxes401_FormMetabox extends Wbcr_FactoryMetaboxes401_Metabox {

			/**
			 * A scope of metadata. By default the current class name used.
			 *
			 * @since 1.0.0
			 * @var string
			 */
			public $scope;

			/**
			 * CSS class that addes to the form.
			 *
			 * @since 3.0.6
			 * @var string
			 */
			public $css_class;

			/**
			 * @var Wbcr_FactoryForms401_MetaValueProvider
			 */
			protected $provider;

			/**
			 * @param Wbcr_Factory401_Plugin $plugin
			 */
			public function __construct(Wbcr_Factory401_Plugin $plugin)
			{
				parent::__construct($plugin);

				$this->scope = (!$this->scope)
					? $this->formatCamelCase(get_class($this))
					: $this->scope;
			}

			/**
			 * @param null $post_id
			 * @return Wbcr_FactoryForms401_Form
			 */
			private function getForm($post_id = null)
			{
				// creating a value provider
				$this->provider = new Wbcr_FactoryForms401_MetaValueProvider(array(
					'scope' => $this->scope
				));

				$this->provider->init($post_id);

				// creating a form
				$form = new Wbcr_FactoryForms401_Form(array(
					'scope' => $this->scope,
					'name' => $this->id
				), $this->plugin);

				$form->setProvider($this->provider);

				$this->form($form);

				return $form;
			}

			/**
			 * Renders a form.
			 */
			public function html()
			{

				$form = $this->getForm();

				echo '<div class="factory-form-metabox factory-bootstrap-401">';
				$this->beforeForm($form);
				$form->html(array(
					'css_class' => $this->css_class
				));
				$this->afterForm($form);
				echo '</div>';
			}

			public function save($post_id)
			{
				$form = $this->getForm($post_id);
				$this->onSavingForm($post_id);

				$form->save();
			}

			/**
			 * Extra custom actions after the form is saved.
			 */
			public function onSavingForm($post_id)
			{
				return;
			}

			/**
			 * Form method that must be overridden in the derived classes.
			 */
			public abstract function form($form);

			/**
			 * Method executed before rendering the form.
			 */
			public function beforeForm(Wbcr_FactoryForms401_Form $form)
			{
				return;
			}

			/**
			 * Method executed after rendering the form.
			 */
			public function afterForm(Wbcr_FactoryForms401_Form $form)
			{
				return;
			}

			private function formatCamelCase($string)
			{
				$output = "";
				foreach(str_split($string) as $char) {
					strtoupper($char) == $char and $output and $output .= "_";
					$output .= $char;
				}
				$output = strtolower($output);

				return $output;
			}
		}
	}