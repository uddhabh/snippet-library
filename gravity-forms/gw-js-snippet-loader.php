<?php
/**
 * Gravity Wiz // Gravity Forms // JS Snippet Loader
 * https://gravitywiz.com/
 * Demo: https://www.loom.com/share/e48a89a701104c169db1292a9608eb2e
 *
 * Easily load JS snippets from the wp-content/plugins/js-snippets
 *
 * Simply add the name of the JS file (without the .js extension) to the $script_names array.
 *
 * @version   1.0
 * @author    Matt Ehlinger <matt@gravitywiz.com>
 * @license   GPL-2.0+
 */



class GW_JS_SNIPPET_LOADER {
	public $form_id = null;

	public $script_names = array();

	public $find_replace_mappings = array();

	function __construct( $config = array() ) {
		if ( isset( $config['form_id'] ) ) {
			$this->form_id = $config['form_id'];
		}

		if ( isset( $config['find_replace_mappings'] ) ) {
			$this->find_replace_mappings = $config['find_replace_mappings'];
		}

		if ( isset( $config['script_names'] ) ) {
			$this->script_names = $config['script_names'];
		}

		// ensure that these scripts are loaded before anything else on form page load
		add_action( 'gform_register_init_scripts', array( $this, 'enqueue_scripts_first' ), 100 /* right after GF Custom JS */ );

		add_action('gform_register_init_scripts', array( $this, 'register_scripts' ) );

	}

	public function replace_values( $script, $form ) {
		foreach ( $this->find_replace_mappings as $find => $replace ) {
			$script = str_replace( $find, $replace, $script );
		}

		// default to applying to all forms if form_id is not set
		$form_id = isset( $this->form_id ) ? $this->form_id : $form['id'];

		$script = str_replace( 'GFFORMID', $form_id, $script );

		return $script;
	}

	public function register_scripts( $form ) {
		if ( isset( $this->form_id ) && $this->form_id != $form['id'] ) {
			return '';
		}

		$script = '';

		foreach ( $this->script_names as $script_name ) {
		    $file_path = WP_PLUGIN_DIR . '/js-snippets/' . $script_name . '.js';
		    $file_content = file_get_contents($file_path);

		    if ($file_content === false ) {
		        continue;
		    }

			$file_content = $this->replace_values( $file_content, $form );

		    $script = $script . ' (function($) { '. $file_content . '})(jQuery);' . ' ';
		}

		if ( $script ) {
			$slug = 'gp-custom-snippet-loader';
			GFFormDisplay::add_init_script($form['id'], $slug, GFFormDisplay::ON_PAGE_RENDER, $script);
		}
	}

	public function enqueue_scripts_first( $form ) {

		$scripts  = rgar( GFFormDisplay::$init_scripts, $form['id'] );
		if ( empty( $scripts ) ) {
			return;
		}

		$filtered = array();
		foreach ( $scripts as $slug => $script ) {
			if ( strpos( $slug, 'gp-custom-snippet-loader' ) === 0 ) {
				$filtered = array( $slug => $script ) + $filtered;
			} else {
				$filtered[ $slug ] = $script;
			}
		}

		GFFormDisplay::$init_scripts[ $form['id'] ] = $filtered;
	}
}

new GW_JS_SNIPPET_LOADER( array(
	'form_id' => 5,
	'script_names' => array(
		'gpaa-google-satellite-view',
	),
	// 'find_replace_mappings' => array(
	// 	'FIND1' => 'REPLACE1',
	// 	'FIND2' => 'REPLACE2',
	// ),
) );
