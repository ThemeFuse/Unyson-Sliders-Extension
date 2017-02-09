<?php if ( ! defined( 'FW' ) ) {
	die( 'Forbidden' );
}
require dirname( __FILE__ ) . '/includes/default/class-fw-extension-slider-default.php';

class FW_Extension_Slider extends FW_Extension {
	private $post_type = 'fw-slider';

	/**
	 * @internal
	 */
	public function _init() {
		$this->add_admin_filters();
		$this->add_admin_actions();
	}

	/**
	 * new function that update merged values
	 * @since 2.3.3 version of Unyson.
	 */
	public function _action_slider_post_update_merged_old_db_option_values( $post_id, $basekey, $subkey, $old_values ) {

		if (
			get_post_type( $post_id ) !== $this->post_type
			||
			!fw_is_post_edit()
		) {
			return;
		}

		remove_action( 'fw_post_options_update', array(
			$this,
			'_action_slider_post_update_merged_old_db_option_values'
		), 11 );

		fw_set_db_post_option(
			$post_id,
			null,
			array_merge( (array) $old_values, fw_get_db_post_option( $post_id ) )
		);

		add_action( 'fw_post_options_update', array(
			$this,
			'_action_slider_post_update_merged_old_db_option_values'
		), 11, 4 );

	}

	/**
	 * Do manually array_merge($old_values, $new_values) on slider post save
	 * because that feature was removed in https://github.com/ThemeFuse/Unyson/commit/e75ff06a2262e49576fac647e8d3651ab842b7b1
	 * int $post_id
	 * WP_Post $post
	 * array $old_values
	 */
	public function _action_slider_post_merge_old_db_option_values( $post_id, $post, $old_values ) {

		if ( $post->post_type !== $this->post_type ) {
			return;
		}

		fw_set_db_post_option(
			$post_id,
			null,
			array_merge( $old_values, fw_get_db_post_option( $post_id ) )
		);
	}

	private function add_admin_filters() {
		add_filter( 'fw_post_options', array( $this, '_admin_filter_load_options' ), 10, 2 );
		add_filter( 'wp_insert_post_data', array( $this, '_admin_filter_pre_save_slider_title' ), 99, 2 );
		add_filter( 'post_updated_messages', array( $this, '_admin_filter_change_updated_messages' ) );
		add_filter( 'manage_' . $this->get_post_type() . '_posts_columns', array( $this, '_admin_filter_add_columns' ),
			10, 1 );
		add_filter( 'post_row_actions', array( $this, '_admin_filter_post_row_actions' ), 10, 2 );
		add_filter( 'bulk_actions-edit-' . $this->get_post_type(),
			array( $this, '_admin_filter_customize_bulk_actions' ) );
		add_filter( 'parent_file', array( $this, '_set_active_submenu' ) );
		add_filter( 
			'fw_get_db_post_option:fw-storage-enabled', 
			array($this, '_filter_disable_post_options_fw_storage'),
			10, 2
		);
	}

	public function get_post_type() {
		return $this->post_type;
	}

	private function add_admin_actions() {
		add_action( 'admin_enqueue_scripts', array( $this, '_admin_action_enqueue_static' ) );
		add_action( 'admin_menu', array( $this, '_admin_action_replace_submit_meta_box' ) );
		add_action( 'manage_' . $this->get_post_type() . '_posts_custom_column',
			array( $this, '_admin_action_manage_custom_column' ), 10, 2 );

		if ( version_compare( fw()->manifest->get_version(), '2.3.3', '>=' ) ) {
			// this action was added in Unyson 2.2.8
			add_action( 'fw_post_options_update', array(
				$this,
				'_action_slider_post_update_merged_old_db_option_values'
			), 11, 4 );
		} else {
			// @deprecated
			add_action( 'fw_save_post_options', array(
				$this,
				'_action_slider_post_merge_old_db_option_values'
			), 10, 3 );
		}

	}

	function _set_active_submenu( $parent_file ) {
		global $submenu_file, $current_screen;

		// Set correct active/current submenu in the WordPress Admin menu
		if ( $current_screen->post_type == $this->post_type ) {
			$submenu_file = 'edit.php?post_type=' . $this->post_type;
		}

		return $parent_file;
	}

	/**
	 * {@inheritdoc}
	 */
	public function _get_link() {
		return self_admin_url( 'edit.php?post_type=' . $this->post_type );
	}

	/*Hide edit bulk action from table*/

	/**
	 * @internal
	 */
	public function _admin_action_manage_custom_column( $column, $post_id ) {
		switch ( $column ) {
			case 'slider_design' :
				$image = $this->get_slider_type( $post_id );
				$link  = get_edit_post_link( $post_id );
				if ( ! empty( $image ) ) {
					echo '<a href="' . $link . '"><img height="100" src="' . $image['small']['src'] . '"/></a>';
				}
				break;
			case 'number_of_images' :
				echo fw()->extensions->get( 'population-method' )->get_number_of_images( $post_id );
				break;
			case 'population_method' :
				$population_method = fw()->extensions->get( 'population-method' )->get_population_method( $post_id );
				echo '<p><a>' . array_shift( $population_method ) . '</a></p>';
				break;
			default :
				break;
		}
	}

	/*Hide actions from rows in table (Quick Edit and View)*/

	private function get_slider_type( $post_id ) {
		$slider_name   = fw_get_db_post_option( $post_id, $this->get_name() . '/selected' );
		$sliders_types = $this->get_sliders_types();

		return isset( $sliders_types[ $slider_name ] ) ? $sliders_types[ $slider_name ] : array();
	}

	//TODO must return to normal method
	private function get_sliders_types() {
		$choices = array();
		foreach ( $this->get_active_sliders() as $instance_name ) {
			$choices[ $instance_name ] = $this->get_child( $instance_name )->get_slider_type();
		}

		return $choices;
	}

	private function get_active_sliders() {
		$active_sliders = array();
		foreach ( $this->get_children() as $slider_instance ) {
			$slider_population_methods = $slider_instance->get_population_methods();
			if ( ! empty( $slider_population_methods ) ) {
				$active_sliders[] = $slider_instance->get_name();
			}
		}

		$active_sliders = apply_filters( 'fw_ext_slider_activated', $active_sliders );

		return $active_sliders;
	}

	/**
	 * @internal
	 */
	public function _admin_filter_customize_bulk_actions( $actions ) {
		unset( $actions['edit'] );

		return $actions;
	}

	/**
	 * @internal
	 */
	public function _admin_filter_post_row_actions( $actions, $post ) {
		if ( $post->post_type === $this->get_post_type() ) {
			unset( $actions['inline hide-if-no-js'], $actions['view'] );
		}

		return $actions;
	}

	/**
	 * @internal
	 */
	public function _admin_filter_add_columns( $columns ) {
		return array(
			'cb'                => $columns['cb'],
			'slider_design'     => __( 'Slider Design', 'fw' ),
			'title'             => $columns['title'],
			'number_of_images'  => __( 'Number of Images', 'fw' ),
			'population_method' => __( 'Population Method', 'fw' ),
		);
	}

	/**
	 * @internal
	 */
	function _admin_filter_change_updated_messages( $messages ) {
		global $post;
		$post_type = get_post_type( $post->ID );

		if ( $post_type === $this->get_post_type() ) {
			$obj      = get_post_type_object( $post_type );
			$singular = $obj->labels->singular_name;

			$messages[ $post_type ] = array(
				0  => '', // Unused. Messages start at index 1.
				1  => sprintf( __( '%s updated.', 'fw' ), $singular ),
				2  => __( 'Custom field updated.', 'fw' ),
				3  => __( 'Custom field deleted.', 'fw' ),
				4  => sprintf( __( '%s updated.', 'fw' ), $singular ),
				5  => isset( $_GET['revision'] ) ? sprintf( __( '%s restored to revision from %s', 'fw' ), $singular,
					wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
				6  => sprintf( __( '%s published.', 'fw' ), $singular ),
				7  => __( 'Page saved.', 'fw' ),
				8  => sprintf( __( '%s submitted.', 'fw' ), $singular ),
				9  => sprintf( __( '%s scheduled for: %s.', 'fw' ), $singular,
					'<strong>' . date_i18n( 'M j, Y @ G:i' ) . '</strong>' ),
				10 => sprintf( __( '%s draft updated.', 'fw' ), $singular ),
			);
		}

		return $messages;
	}

	/**
	 * @internal
	 */
	public function _admin_filter_pre_save_slider_title( $data, $postarr ) {
		if ( $data['post_type'] === $this->get_post_type() ) {
			if ( isset( $postarr['fw_options']['slider']['selected'] ) ) {
				$active_slider      = $postarr['fw_options']['slider']['selected'];
				$data['post_title'] = $postarr['fw_options']['slider'][ $active_slider ]['title'];
			}

			if ( isset( $postarr['fw_options']['title'] ) ) {
				$data['post_title'] = $postarr['fw_options']['title'];
			}
		}

		return $data;
	}

	/**
	 * @internal
	 */
	public function _admin_action_replace_submit_meta_box() {
		remove_meta_box( 'submitdiv', $this->get_post_type(), 'core' );
		add_meta_box( 'submitdiv', __( 'Publish', 'fw' ), array( $this, 'render_submit_meta_box' ),
			$this->get_post_type(), 'side' );
	}

	public function render_submit_meta_box( $post, $args = array() ) {
		// a modified version of post_submit_meta_box() (wp-admin/includes/meta-boxes.php, line 12)
		$post_type        = $post->post_type;
		$post_type_object = get_post_type_object( $post_type );
		$can_publish      = current_user_can( $post_type_object->cap->publish_posts );
		$meta             = (array) fw_get_db_post_option( $post->ID );

		if ( isset( $_GET['action'] ) && $_GET['action'] === 'edit' ) {
			if (
				array_key_exists( $this->get_name(), $meta )
				&&
				! is_null( $this->get_child( $meta['slider']['selected'] ) )
			) {
				$slider_name       = $meta['slider']['selected'];
				$population_method = $this->get_child( $slider_name )->get_population_method( $meta['slider'][ $slider_name ]['population-method'] );
				$slider_type       = $this->get_slider_type( $post->ID );
				echo $this->render_view( 'backend/submit-box-edit',
					compact( 'post', 'population_method', 'meta', 'post_type', 'post_type_object', 'can_publish',
						'slider_type' ) );
			} else {
				echo $this->render_view( 'backend/submit-box-error', compact( 'post' ) );
			}
		} else {
			echo $this->render_view( 'backend/submit-box-raw',
				compact( 'post', 'meta', 'post_type', 'post_type_object', 'can_publish' ) );
		}
	}

	/**
	 * @internal
	 */
	public function _admin_filter_load_options( $options, $post_type ) {
		if ( $post_type === $this->get_post_type() ) {
			if ( fw_is_post_edit() ) {
				return $this->load_post_edit_options();
			} else {
				return $this->load_post_new_options();
			}
		}

		return $options;
	}

	public function error_metabox( $message ) {
		return array(
			'slider-sidebar-errobox' => array(
				'title'   => 'Error Message',
				'type'    => 'box',
				'context' => 'normal',
				'options' => array(
					'error' => array(
						'label' => false,
						'type'  => 'html-full',
						'html'  => '<p style="color:#dd3d36; text-align:center;">' . $message . '</p>'
					)
				)
			)
		);
	}

	public function load_post_edit_options() {
		global $post;

		$selected        = fw_get_db_post_option( $post->ID, $this->get_name() . '/selected' );
		$title_value     = fw_get_db_post_option( $post->ID, $this->get_name() . '/' . $selected . '/title' );
		$child_extension = $this->get_child( $selected );

		if (
			is_null( $child_extension ) or
			! in_array( $selected, $this->get_active_sliders() )
		) {
			$message = __( 'This slider was created correctly, but the code implementation was delete from source code or blocked from filter.Delete this post or recovery slider implementation',
				'fw' );

			return $this->error_metabox( $message );
		}

		$multimedia_types = $child_extension->get_multimedia_types();

		if ( empty( $multimedia_types ) ) {
			$message = __( 'This slider was created correctly, but the multimedia_types from config.php file was deleted, please set multimedia_types for this slider type.',
				'fw' );

			return $this->error_metabox( $message );
		}

		$options = array_merge(
			array(
				'slider-sidebar-metabox' => array(
					'context' => 'side',
					'title'   => __( 'Slider Configuration', 'fw' ),
					'type'    => 'box',
					'options' => array(
						'populated'   => array(
							'type'  => 'hidden',
							'value' => true
						),
						'slider_type' => array(
							'type'       => 'hidden',
							'value'      => $selected,
							'fw-storage' => array(
								'type' => 'post-meta',
								'post-meta' => 'fw_option:slider_type',
							),
						),
						'title'       => array(
							'type'  => 'text',
							'label' => __( 'Slider Title', 'fw' ),
							'value' => $title_value,
							'desc'  => __( 'Choose a title for your slider only for internal use: Ex: "Homepage".',
								'fw' )
						)
					)
				)
			),
			$this->get_slider_population_method_options()
		);

		$custom_settings = $this->get_slider_options();

		if ( ! empty( $custom_settings ) ) {
			$selected              = fw_get_db_post_option( $post->ID, $this->get_name() . '/selected' );
			$custom_settings_value = fw_get_db_post_option( $post->ID, $this->get_name() . '/' . $selected . '/custom-settings' );

			$options['slider-sidebar-metabox']['options']['custom-settings'] = array(
				'label'         => false,
				'desc'          => false,
				'type'          => 'multi',
				'value'         => $custom_settings_value,
				'inner-options' => $this->get_slider_options()
			);
		}

		return $options;
	}

	private function get_slider_population_method_options() {
		global $post;

		$slider_name       = fw_get_db_post_option( $post->ID, $this->get_name() . '/selected' );
		$population_method = fw_get_db_post_option( $post->ID,
			$this->get_name() . '/' . $slider_name . '/population-method' );
		$slider_instance   = $this->get_child( $slider_name );
		$multimedia_types  = $slider_instance->get_multimedia_types();
		$options           = $slider_instance->get_population_method_options( $population_method );

		return apply_filters( 'fw_ext_' . str_replace( '-', '_', $slider_name ) . '_population_method_' . $population_method . '_options',
			fw()->extensions->get( 'population-method' )->get_population_options(
				$population_method,
				$multimedia_types,
				$options )
		);
	}

	private function get_slider_options() {
		global $post;

		$slider_type = fw_get_db_post_option( $post->ID, $this->get_name() . '/selected' );

		return $this->get_child( $slider_type )->get_slider_options();
	}

	public function load_post_new_options() {
		return array(
			'general' => array(
				'title'   => __( 'Slider Settings', 'fw' ),
				'type'    => 'box',
				'options' => array(
					$this->get_name() => array(
						'type'         => 'multi-picker',
						'value'        => '',
						'show_borders' => true,
						'label'        => false,
						'desc'         => false,
						'picker'       => array(
							'selected' => array(
								'label'   => __( 'Type', 'fw' ),
								'type'    => 'image-picker',
								'choices' => $this->get_sliders_types()
							)
						),
						'choices'      => $this->get_sliders_sets_options()
					)
				)
			)
		);
	}

	private function get_sliders_sets_options() {
		$options        = array();
		$active_sliders = $this->get_active_sliders();

		if ( empty( $active_sliders ) ) {
			$message = __( "You don't have slider extensions, please create at least one extension for properly work",
				'fw' );
			wp_die( $message );
		}

		foreach ( $active_sliders as $instance_name ) {

			$slider_options = $this->get_child( $instance_name )->get_slider_options();
			$population_methods = $this->get_child( $instance_name )->get_population_methods();

			$options[ $instance_name ] = array(
				'population-method' => array(
					'type'    => 'select',
					'label'   => __( 'Population Method', 'fw' ),
					'desc'    => __( 'Choose the population method for your slider', 'fw' ),
					'value'   => '',
					'choices' => $population_methods,
				),
				'title'             => array(
					'type'  => 'text',
					'label' => __( 'Title', 'fw' ),
					'value' => '',
					'desc'  => 'Choose the ' . $this->get_name() . ' title (for internal use)'
				)
			);

			if ( ! empty( $slider_options ) ) {
				$options[ $instance_name ]['custom-settings'] = array(
					'label'         => false,
					'desc'          => false,
					'type'          => 'multi',
					'inner-options' => $slider_options
				);
			}
		}

		return $options;
	}

	public function render_slider( $post_id, $dimensions, $extra_data = array() ) {
		$slider_name = fw_get_db_post_option( $post_id, $this->get_name() . '/selected' );

		if ( ! is_null( $this->get_child( $slider_name ) ) ) {
			return $this->get_child( $slider_name )->render_slider( $post_id, $dimensions, $extra_data );
		}
	}

	/**
	 * @internal
	 */
	public function _admin_action_enqueue_static() {
		$match_current_screen = fw_current_screen_match(
			array(
				'only' => array(
					array(
						'post_type' => $this->post_type,
					)
				)
			) );

		if ( $match_current_screen ) {
			wp_enqueue_style(
				'fw-extension-' . $this->get_name() . '-css',
				$this->get_declared_URI( '/static/css/style.css' ),
				array(),
				fw()->manifest->get_version()
			);
			wp_enqueue_style( 'fw-selectize' );
			wp_enqueue_script(
				'fw-population-method-categories',
				$this->get_declared_URI( '/static/js/population-method.js' ),
				array( 'fw-selectize' ),
				fw()->manifest->get_version()
			);
		}
	}

	public function get_populated_sliders_choices() {
		$choices = array();

		foreach ( $this->get_populated_sliders() as $slider ) {

			$choices[ $slider->ID ] = empty( $slider->post_title ) ? __( '(no title)', 'fw' ) : $slider->post_title;
		}

		return $choices;
	}

	/**
	 * Get populated sliders.
	 *
	 * If want to get the sliders from a slider type, set the next array
	 * array('slider_types' => array('slider_type_name'))
	 *
	 * @param array $params
	 *
	 * @return array
	 */
	public function get_populated_sliders( $params = array() ) {

		$params     = (array) $params;
		$meta_query = array();

		if ( array_key_exists( 'slider_types', $params ) && ! empty( $params['slider_types'] ) ) {
			$collector = array();
			array_push( $collector, array(
				'key'   => 'fw_option:slider_type',
				'value' => $params['slider_types']
			) );

			$meta_query = array(
				'meta_query' => $collector,
			);
		}

		$posts = get_posts(
			array_merge(
				array(
					'post_type'   => $this->post_type,
					'numberposts' => - 1
				),
				$meta_query
			)
		);

		foreach ( $posts as $key => $post ) {
			$data = fw()->extensions->get( 'population-method' )->get_frontend_data( $post->ID );
			if ( empty( $data['slides'] ) ) {
				unset( $posts[ $key ] );
			}
		}

		return $posts;
	}

	public function _filter_disable_post_options_fw_storage($enabled, $post_type) {
		if ($this->get_post_type() === $post_type) {
			$enabled = false;
		}

		return $enabled;
	}
}
