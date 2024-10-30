<?php

	/**
	 * Textbox Control
	 *
	 * Main options:
	 *  name            => a name of the control
	 *  value           => a value to show in the control
	 *  default         => a default value of the control if the "value" option is not specified
	 *  maxLength       => set the max length of text in the input control
	 *  placeholder     => a placeholder text for the control when the control value is empty
	 *
	 *  Использование datepicker в текстовом поле (необходимо подключить bootstrap.datepicker)
	 * 'htmlAttrs' => array(
	 * 'data-provide' => 'datepicker-inline',
	 * 'data-date-language' => 'ru',
	 * 'data-date-autoclose' => 'true'
	 * )
	 *
	 * @author Alex Kovalev <alex.kovalevv@gmail.com>
	 * @copyright (c) 2018, Webcraftic Ltd
	 *
	 * @package factory-forms
	 * @since 1.0.0
	 */

	// Exit if accessed directly
	if( !defined('ABSPATH') ) {
		exit;
	}

	if( !class_exists('Wbcr_FactoryForms401_TextboxControl') ) {

		class Wbcr_FactoryForms401_TextboxControl extends Wbcr_FactoryForms401_Control {

			public $type = 'textbox';

			/**
			 * Preparing html attributes before rendering html of the control.
			 *
			 * @since 1.0.0
			 * @return void
			 */
			protected function beforeHtml()
			{
				$value = esc_attr($this->getValue());
				$name_on_form = $this->getNameOnForm();

				if( $this->getOption('maxLength', false) ) {
					$this->addHtmlAttr('maxlength', intval($this->getOption('maxLength')));
				}

				if( $this->getOption('placeholder', false) ) {
					$this->addHtmlAttr('placeholder', $this->getOption('placeholder'));
				}

				$this->addCssClass('form-control');
				$this->addHtmlAttr('type', 'text');
				$this->addHtmlAttr('id', $name_on_form);
				$this->addHtmlAttr('name', $name_on_form);
				$this->addHtmlAttr('value', $value);
			}

			/**
			 * Shows the html markup of the control.
			 *
			 * @since 1.0.0
			 * @return void
			 */
			public function html()
			{
				$units = $this->getOption('units', false);
				?>
				<?php if( $units ) { ?><div class="input-group"><?php } ?>
				<input <?php $this->attrs() ?>/>
				<?php if( $units ) { ?>
				<span class="input-group-addon"><?php echo $units; ?></span>
			<?php } ?>
				<?php if( $units ) { ?></div><?php } ?>
			<?php
			}

            /**
             * Returns a submit value of the control by a given name.
             *
             * @since 1.0.0
             * @return mixed
             */
            public function getSubmitValue($name, $subName)
            {
                $name_on_form = $this->getNameOnForm($name);


                $raw_value = isset($_POST[$name_on_form])
                    ? $_POST[$name_on_form]
                    : null;

                $value = $raw_value;

                if( is_array($value) ) {
                    $value = implode(',', $value);
                }

                return $this->filterValue($value, $raw_value);
            }


            /**
             * @param $value
             * @param $raw_value
             * @return mixed
             */
            protected function filterValue($value, $raw_value)
            {
                $sanitize_func = $this->getOption('filter_value');

                // if the data options is a valid callback for an object method
                if (!empty($sanitize_func) && ((is_array($sanitize_func) && count($sanitize_func) == 2 && gettype($sanitize_func[0]) == 'object') || function_exists($sanitize_func))) {
                    return call_user_func_array($sanitize_func, array($value, $raw_value));
                }else{
                    return sanitize_text_field($value);
                }
            }


		}
	}