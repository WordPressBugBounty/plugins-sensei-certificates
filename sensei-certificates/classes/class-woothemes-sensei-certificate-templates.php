<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Sensei LMS Certificates Templates Class
 *
 * All functionality pertaining to the Certificate Templates functionality in Sensei.
 *
 * @package WordPress
 * @subpackage Sensei
 * @category Extension
 * @author Automattic
 * @since 1.0.0
 *
 * TABLE OF CONTENTS
 *
 * - __construct()
 * - plugin_path()
 * - certificate_templates_locate_preview_template()
 * - certificate_templates_admin_menu_items()
 * - setup_certificate_templates_post_type()
 * - post_type_custom_column_heading()
 * - post_type_custom_column_content()
 * - populate_object()
 * - get_message()
 * - get_image_id()
 * - get_field_position()
 * - generate_pdf()
 * - get_certificate_font_settings()
 * - textarea_field()
 * - text_field()
 * - hex2rgb()
 * - get_item_meta_value()
 * - save_post_meta()
 * - add_column_headings()
 * - add_column_data()
 */
class WooThemes_Sensei_Certificate_Templates {

	/**
	 * @var string url link to plugin files
	 */
	public $plugin_url;

	/**
	 * @var string path to the plugin files
	 */
	public $plugin_path;

	/**
	 * @var string class token
	 */
	public $token;

	/**
	 * Template post ID.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Template post meta fields.
	 *
	 * @var array|false|string
	 */
	public $certificate_template_custom_fields;

	/**
	 * Template image ids.
	 *
	 * @var array
	 */
	public $image_ids;

	/**
	 * Template main image id.
	 *
	 * @var int
	 */
	public $image_id;

	/**
	 * Template additional image ids.
	 *
	 * @var array
	 */
	public $additional_image_ids;

	/**
	 * Font color.
	 *
	 * @var
	 */
	public $certificate_font_color;

	/**
	 * Font size.
	 *
	 * @var array
	 */
	public $certificate_font_size;

	/**
	 * Font style.
	 *
	 * @var array
	 */
	public $certificate_font_style;

	/**
	 * Font family.
	 *
	 * @var array
	 */
	public $certificate_font_family;

	/**
	 * Heading position.
	 *
	 * @var array
	 */
	public $certificate_heading_pos;

	/**
	 * Template fields.
	 *
	 * @var
	 */
	public $certificate_template_fields;

	/**
	 * __construct function.
	 *
	 * @access public
	 * @return void
	 * @since 1.0.0
	 */
	public function __construct() {

		// Default values
		$this->plugin_url  = trailingslashit( plugins_url( '', SENSEI_CERTIFICATES_PLUGIN_FILE ) );
		$this->plugin_path = plugin_dir_path( SENSEI_CERTIFICATES_PLUGIN_FILE );
		$this->token       = 'sensei-certificate-templates';

		// Setup post type
		add_action( 'init', array( $this, 'setup_certificate_templates_post_type' ), 110 );

		/**
		 * BACKEND
		 */
		if ( is_admin() ) {

			// Admin section
			include $this->plugin_path . 'admin/woothemes-sensei-certificate-templates-admin-init.php';
			// Custom Write Panel Columns
			add_filter( 'manage_edit-course_columns', array( $this, 'add_column_headings' ), 11, 1 );
			add_action( 'manage_posts_custom_column', array( $this, 'add_column_data' ), 11, 2 );

		}

		// Preview Template
		add_filter( 'single_template', array( $this, 'certificate_templates_locate_preview_template' ), 10, 2 );

	}


	/**
	 * plugin_path function
	 *
	 * @access public
	 * @since  1.0.0
	 * @return string
	 */
	public function plugin_path() {

		if ( $this->plugin_path ) {
			return $this->plugin_path;
		}

		return $this->plugin_path = untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) );

	}


	/**
	 * Locate the certificate template preview template file, in this plugin's templates directory
	 *
	 * @access public
	 * @since 1.0
	 * @param string $locate locate path
	 * @param string $type type of template
	 *
	 * @return string the location path for the certificate template preview file
	 */
	public function certificate_templates_locate_preview_template( $locate, $type ) {

		$post_type = get_query_var( 'post_type' );

		if ( 'certificate_template' === $post_type && 'single' === $type ) {
			$locate = $this->plugin_path() . '/templates/single-certificate_template.php';
		}

		return $locate;
	}


	/**
	 * Setup the certificate post type, it's admin menu item and the appropriate labels and permissions.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function setup_certificate_templates_post_type() {

		$args = array(
			'labels'              => array(
				'name'               => _x( 'Certificate Templates', 'post type general name', 'sensei-certificates' ),
				'singular_name'      => _x( 'Certificate Template', 'post type singular name', 'sensei-certificates' ),
				'add_new'            => _x( 'Add New Certificate Template', 'post type add_new', 'sensei-certificates' ),
				'add_new_item'       => __( 'Add New Certificate Template', 'sensei-certificates' ),
				'edit_item'          => __( 'Edit Certificate Template', 'sensei-certificates' ),
				'new_item'           => __( 'New Certificate Template', 'sensei-certificates' ),
				'all_items'          => __( 'Certificate Templates', 'sensei-certificates' ),
				'view_item'          => __( 'View Certificate Template', 'sensei-certificates' ),
				'search_items'       => __( 'Search Certificate Templates', 'sensei-certificates' ),
				'not_found'          => __( 'No certificate templates found', 'sensei-certificates' ),
				'not_found_in_trash' => __( 'No certificate templates found in Trash', 'sensei-certificates' ),
				'parent_item_colon'  => '',
				'menu_name'          => __( 'Certificate Templates', 'sensei-certificates' ),
			),
			'public'              => true,
			'publicly_queryable'  => true,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=certificate',
			'query_var'           => true,
			'rewrite'             => array(
				'slug'       => esc_attr( apply_filters( 'sensei_certificate_templates_slug', 'certificate-template' ) ),
				'with_front' => true,
				'feeds'      => true,
				'pages'      => true,
			),
			'capability_type'     => 'certificate_template',
			'map_meta_cap'        => true,
			'has_archive'         => false,
			'hierarchical'        => false,
			'menu_icon'           => esc_url( Sensei()->plugin_url . 'assets/images/certificate.png' ),
			'supports'            => array( 'title' ),
		);

		register_post_type( 'certificate_template', $args );

	}

	/**
	 * post_type_custom_column_headings function.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function post_type_custom_column_headings( $defaults ) {

		unset( $defaults['date'] );
		$defaults['learner']        = __( 'Learner', 'sensei-certificates' );
		$defaults['course']         = __( 'Course', 'sensei-certificates' );
		$defaults['date_completed'] = __( 'Date Completed', 'sensei-certificates' );
		$defaults['actions']        = __( 'Actions', 'sensei-certificates' );

		return $defaults;

	}


	/**
	 * post_type_custom_column_content function.
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function post_type_custom_column_content( $column_name, $post_ID ) {

		$user_id          = get_post_meta( $post_ID, $key = 'learner_id', true );
		$course_id        = get_post_meta( $post_ID, $key = 'course_id', true );
		$user             = get_userdata( $user_id );
		$course           = get_post( $course_id );
		$course_end_date  = WooThemes_Sensei_Utils::sensei_get_activity_value(
			array(
				'post_id' => $course_id,
				'user_id' => $user_id,
				'type'    => 'sensei_course_status',
				'field'   => 'comment_date',
			)
		);
		$certificate_hash = esc_html( substr( md5( $course_id . $user_id ), -8 ) );

		switch ( $column_name ) {
			case 'learner':
				echo '<a href="' . esc_url(
					add_query_arg(
						array(
							'page'      => 'sensei_analysis',
							'user'      => intval( $user_id ),
							'course_id' => intval( $course_id ),
						),
						admin_url( 'edit.php?post_type=lesson' )
					)
				) . '">' . esc_html( $user->user_login ) . '</a>';
				break;
			case 'course':
				echo '<a href="' . esc_url(
					add_query_arg(
						array(
							'page'      => 'sensei_analysis',
							'course_id' => intval( $course_id ),
						),
						admin_url( 'edit.php?post_type=lesson' )
					)
				) . '">' . esc_html( $course->post_title ) . '</a>';
				break;
			case 'date_completed':
				echo wp_kses_post( $course_end_date );
				break;
			case 'actions':
				echo '<a href="' . esc_url(
					add_query_arg(
						array(
							'certificate' => '1',
							'hash'        => $certificate_hash,
						),
						site_url()
					)
				) . '" target="_blank">' . esc_html__( 'View Certificate', 'sensei-certificates' ) . '</a>';
				break;
		}

	}


	/**
	 * populate_object
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  int $id
	 * @return boolean
	 */
	public function populate_object( $id ) {

		$this->id = (int) $id;

		$this->certificate_template_custom_fields = get_post_custom( $this->id );

		// Define the data we're going to load: Key => Default value
		$load_data = array(
			'image_ids'                   => array(),
			'additional_image_ids'        => array(),
			'certificate_font_color'      => '',
			'certificate_font_size'       => '',
			'certificate_font_style'      => '',
			'certificate_font_family'     => '',
			'certificate_heading_pos'     => '',
			'certificate_template_fields' => array(),
		);

		// Load the data from the custom fields
		foreach ( $load_data as $key => $default ) {

			// set value from db (unserialized if needed) or use default
			$this->$key = ( isset( $this->certificate_template_custom_fields[ '_' . $key ][0] ) && '' !== $this->certificate_template_custom_fields[ '_' . $key ][0] ) ? ( is_array( $default ) ? maybe_unserialize( $this->certificate_template_custom_fields[ '_' . $key ][0] ) : $this->certificate_template_custom_fields[ '_' . $key ][0] ) : $default;

		}

		// set the main template image, if any
		if ( count( $this->image_ids ) > 0 ) {
			$this->image_id = $this->image_ids[0];
		}

		return false;

	} // populate_object()


	/** Getter/Setter methods ******************************************************/

	/**
	 * Get the certificate template message if any
	 *
	 * @access public
	 * @since 1.0
	 * @return string message or empty string
	 */
	public function get_message() {

		if ( ! isset( $this->message ) ) {
			$this->message = $this->get_item_meta_value( $this->certificate_template_fields['message']['display_name'] );
		}

		return $this->message;

	}


	/**
	 * Gets the certificate template image id: the selected image id if this is a certificate template
	 * otherwise the certificate template primary image id
	 *
	 * @access public
	 * @since 1.0.0
	 * @return int certificate template image id
	 */
	public function get_image_id() {

		global $post;

		if ( isset( $post->ID ) && 0 < $post->ID ) {
			$image_ids = get_post_meta( $post->ID, '_image_ids', true );
			$image_id  = $image_ids[0];
		} else {
			return false;
		}

		// otherwise return the template primary image id
		return $image_id;

	}


	/**
	 * Returns the field position for the field $field_name
	 *
	 * @access public
	 * @since 1.0.0
	 * @return array associative array with position members 'x1', 'y1', 'width'
	 *         and 'height'
	 */
	public function get_field_position( $field_name ) {

		return isset( $this->certificate_template_fields[ $field_name ]['position'] ) ? $this->certificate_template_fields[ $field_name ]['position'] : array();

	}


	/** PDF Generation methods ******************************************************/

	/**
	 * Generate and save or stream a PDF file
	 *
	 * @access public
	 * @since 1.0.0
	 *
	 * @return mixed nothing if a $path is supplied, otherwise a PDF download
	 */
	public function generate_pdf() {

		global $post;

		$image    = wp_get_attachment_metadata( $this->get_image_id() );

		// determine orientation: landscape or portrait
		if ( $image['width'] > $image['height'] ) {
			$orientation = 'L';
		} else {
			$orientation = 'P';
		}

		// Create the pdf
		// TODO: we're assuming a standard DPI here of where 1 point = 1/72 inch = 1 pixel
		// When writing text to a Cell, the text is vertically-aligned in the middle
		$fpdf = Woothemes_Sensei_Certificates_TFPDF::get_tfpdf_object(
			$orientation, 'pt', array( $image['width'], $image['height'] )
		);

		$fpdf->AddPage();
		$fpdf->SetAutoPageBreak( false );

		// Add custom font
		$custom_font = apply_filters( 'sensei_certificates_custom_font', false );
		if ( $custom_font ) {
			if ( isset( $custom_font['family'] ) && isset( $custom_font['file'] ) ) {
				$fpdf->AddFont( $custom_font['family'], '', $custom_font['file'], true );
			}
		} else {
			// Add multibyte font
			$fpdf->AddFont( 'DejaVu', '', 'DejaVuSansCondensed.ttf', true );
		}

		// set the certificate image
		$fpdf->Image( get_attached_file( $this->get_image_id() ), 0, 0, $image['width'], $image['height'] );

		// this is useful for displaying the text cell borders when debugging the PDF layout,
		// though keep in mind that we translate the box position to align the text to bottom
		// edge of what the user selected, so if you want to see the originally selected box,
		// display that prior to the translation
		$show_border = 0;

		// Get the certificate template
		$certificate_template_custom_fields = get_post_custom( $post->ID );

		// Define the data we're going to load: Key => Default value
		$load_data = array(
			'certificate_font_style'      => '',
			'certificate_font_color'      => '',
			'certificate_font_size'       => '',
			'certificate_font_family'     => '',
			'image_ids'                   => array(),
			'certificate_template_fields' => array(),
		);

		// Load the data from the custom fields
		foreach ( $load_data as $key => $default ) {

			// set value from db (unserialized if needed) or use default
			$this->$key = ( isset( $certificate_template_custom_fields[ '_' . $key ][0] ) && '' !== $certificate_template_custom_fields[ '_' . $key ][0] ) ? ( is_array( $default ) ? maybe_unserialize( $certificate_template_custom_fields[ '_' . $key ][0] ) : $certificate_template_custom_fields[ '_' . $key ][0] ) : $default;

		}

		// Data fields.
		$data_fields = sensei_get_certificate_data_fields();
		foreach ( $data_fields as $field_key => $field_info ) {

			$meta_key = 'certificate_' . $field_key;

			// Get the default field value.
			$field_value = $field_info['text_placeholder'];
			if ( isset( $this->certificate_template_fields[ $meta_key ]['text'] ) && '' !== $this->certificate_template_fields[ $meta_key ]['text'] ) {
				$field_value = $this->certificate_template_fields[ $meta_key ]['text'];
			}

			// Replace the template tags.
			$field_value = apply_filters( 'sensei_certificate_data_field_value', $field_value, $field_key, wp_get_current_user(), null );

			// Check if the field has a set position.
			if ( isset( $this->certificate_template_fields[ $meta_key ]['position']['x1'] ) ) {

				// Write the value to the PDF.
				$function_name = ( 'textarea' === $field_info['type'] ) ? 'textarea_field' : 'text_field';

				$font_settings = $this->get_certificate_font_settings( $meta_key );

				call_user_func_array( array( $this, $function_name ), array( $fpdf, $field_value, $show_border, array( $this->certificate_template_fields[ $meta_key ]['position']['x1'], $this->certificate_template_fields[ $meta_key ]['position']['y1'], $this->certificate_template_fields[ $meta_key ]['position']['width'], $this->certificate_template_fields[ $meta_key ]['position']['height'] ), $font_settings ) );

			}
		}

		// download file
		Woothemes_Sensei_Certificates_TFPDF::output_to_http(
			$fpdf, 'certificate-preview-' . $post->ID . '.pdf'
		);

	}


	/**
	 * Returns font settings for the certificate template
	 *
	 * @access public
	 * @since  1.0.0
	 * @return void
	 */
	public function get_certificate_font_settings( $field_key = '' ) {

		$return_array = array();

		if ( isset( $this->certificate_template_fields[ $field_key ]['font']['color'] ) && '' != $this->certificate_template_fields[ $field_key ]['font']['color'] ) {
			$return_array['font_color'] = $this->certificate_template_fields[ $field_key ]['font']['color'];
		}

		if ( isset( $this->certificate_template_fields[ $field_key ]['font']['family'] ) && '' != $this->certificate_template_fields[ $field_key ]['font']['family'] ) {
			$return_array['font_family'] = $this->certificate_template_fields[ $field_key ]['font']['family'];
		}

		if ( isset( $this->certificate_template_fields[ $field_key ]['font']['style'] ) && '' != $this->certificate_template_fields[ $field_key ]['font']['style'] ) {
			$return_array['font_style'] = $this->certificate_template_fields[ $field_key ]['font']['style'];
		}

		if ( isset( $this->certificate_template_fields[ $field_key ]['font']['size'] ) && '' != $this->certificate_template_fields[ $field_key ]['font']['size'] ) {
			$return_array['font_size'] = $this->certificate_template_fields[ $field_key ]['font']['size'];
		}

		return $return_array;

	}


	/**
	 * Render a multi-line text field to the PDF
	 *
	 * @access public
	 * @since 1.0.0
	 * @param FPDF   $fpdf fpdf library object
	 * @param string $field_name the field name
	 * @param mixed  $value string or int value to display
	 * @param int    $show_border a debugging/helper option to display a border
	 *           around the position for this field
	 */
	public function textarea_field( $fpdf, $value, $show_border, $position, $font = array() ) {

		if ( $value ) {

			if ( empty( $font ) ) {

				$font = array(
					'font_color'  => $this->certificate_font_color,
					'font_family' => $this->certificate_font_family,
					'font_style'  => $this->certificate_font_style,
					'font_size'   => $this->certificate_font_size,
				);

			}

			// Test each font element
			if ( empty( $font['font_color'] ) ) {
				$font['font_color'] = $this->certificate_font_color; }
			if ( empty( $font['font_family'] ) ) {
				$font['font_family'] = $this->certificate_font_family; }
			if ( empty( $font['font_style'] ) ) {
				$font['font_style'] = $this->certificate_font_style; }
			if ( empty( $font['font_size'] ) ) {
				$font['font_size'] = $this->certificate_font_size; }

			// get the field position
			list( $x, $y, $w, $h ) = $position;

			// font color
			$font_color = $this->hex2rgb( $font['font_color'] );
			$fpdf->SetTextColor( $font_color[0], $font_color[1], $font_color[2] );

			// Check for Border and Center align
			$border = 0;
			$center = 'J';
			if ( isset( $font['font_style'] ) && ! empty( $font['font_style'] ) && false !== strpos( $font['font_style'], 'C' ) ) {
				$center             = 'C';
				$font['font_style'] = str_replace( 'C', '', $font['font_style'] );
			}
			if ( isset( $font['font_style'] ) && ! empty( $font['font_style'] ) && false !== strpos( $font['font_style'], 'O' ) ) {
				$border             = 1;
				$font['font_style'] = str_replace( 'O', '', $font['font_style'] );
			}

			$custom_font = $this->set_custom_font( $fpdf, $font );

			// Set the field text styling based on the font type
			$fonttype = '';
			if ( ! $custom_font ) {
				$fonttype = $this->get_font_type( $value );
				switch ( $fonttype ) {
					case 'mb':
						$fpdf->SetFont( 'dejavusanscondensed', '', $font['font_size'] );
						break;
					case 'latin':
						$fpdf->SetFont( $font['font_family'], $font['font_style'], $font['font_size'] );
						break;
					default:
						$fpdf->SetFont( $font['font_family'], $font['font_style'], $font['font_size'] );
						break;
				}
			}

			$fpdf->setXY( $x, $y );

			if ( 0 < $border ) {
				$show_border = 1;
				$fpdf->SetDrawColor( $font_color[0], $font_color[1], $font_color[2] );
			}

			// Decode string based on font type
			if ( 'latin' == $fonttype ) {
				$value = utf8_decode( $value );
			}

			// and write out the value
			$fpdf->Multicell( $w, $font['font_size'], $value, $show_border, $center );

		}

	}

	/**
	 * Render a single-line text field to the PDF
	 *
	 * @access public
	 * @since 1.0.0
	 * @param FPDF   $fpdf fpdf library object
	 * @param string $field_name the field name
	 * @param mixed  $value string or int value to display
	 * @param int    $show_border a debugging/helper option to display a border
	 *           around the position for this field
	 */
	private function text_field( $fpdf, $value, $show_border, $position, $font = array() ) {

		if ( $value ) {

			if ( empty( $font ) ) {

				$font = array(
					'font_color'  => $this->certificate_font_color,
					'font_family' => $this->certificate_font_family,
					'font_style'  => $this->certificate_font_style,
					'font_size'   => $this->certificate_font_size,
				);

			}

			// Test each font element
			if ( empty( $font['font_color'] ) ) {
				$font['font_color'] = $this->certificate_font_color; }
			if ( empty( $font['font_family'] ) ) {
				$font['font_family'] = $this->certificate_font_family; }
			if ( empty( $font['font_style'] ) ) {
				$font['font_style'] = $this->certificate_font_style; }
			if ( empty( $font['font_size'] ) ) {
				$font['font_size'] = $this->certificate_font_size; }

			// get the field position
			list( $x, $y, $w, $h ) = $position;

			// font color
			$font_color = $this->hex2rgb( $font['font_color'] );
			$fpdf->SetTextColor( $font_color[0], $font_color[1], $font_color[2] );

			// Check for Border and Center align
			$border = 0;
			$center = 'J';
			if ( isset( $font['font_style'] ) && ! empty( $font['font_style'] ) && false !== strpos( $font['font_style'], 'C' ) ) {
				$center             = 'C';
				$font['font_style'] = str_replace( 'C', '', $font['font_style'] );
			}
			if ( isset( $font['font_style'] ) && ! empty( $font['font_style'] ) && false !== strpos( $font['font_style'], 'O' ) ) {
				$border             = 1;
				$font['font_style'] = str_replace( 'O', '', $font['font_style'] );
			}

			$custom_font = $this->set_custom_font( $fpdf, $font );

			// Set the field text styling based on the font type
			$fonttype = '';
			if ( ! $custom_font ) {
				$fonttype = $this->get_font_type( $value );
				switch ( $fonttype ) {
					case 'mb':
						$fpdf->SetFont( 'dejavusanscondensed', '', $font['font_size'] );
						break;
					case 'latin':
						$fpdf->SetFont( $font['font_family'], $font['font_style'], $font['font_size'] );
						break;
					default:
						$fpdf->SetFont( $font['font_family'], $font['font_style'], $font['font_size'] );
						break;
				}
			}

			// show a border for debugging purposes
			if ( $show_border ) {
				$fpdf->setXY( $x, $y );
				$fpdf->Cell( $w, $h, '', 1 );
			}

			if ( 0 < $border ) {
				$show_border = 1;
				$fpdf->SetDrawColor( $font_color[0], $font_color[1], $font_color[2] );
			}

			// align the text to the bottom edge of the cell by translating as needed
			$y = $font['font_size'] > $h ? $y - ( $font['font_size'] - $h ) / 2 : $y + ( $h - $font['font_size'] ) / 2;
			$fpdf->setXY( $x, $y );

			// Decode string based on font type
			if ( 'latin' == $fonttype ) {
				$value = utf8_decode( $value );
			}

			// and write out the value
			$fpdf->Cell( $w, $h, $value, $show_border, $position, $center );

		}

	}

	/**
	 * Taxes a hex color code and returns the RGB components in an array
	 *
	 * @access public
	 * @since 1.0.0
	 * @param string $hex hex color code, ie #EEEEEE
	 * @return array rgb components, ie array( 'EE', 'EE', 'EE' )
	 */
	private function hex2rgb( $hex ) {

		if ( ! $hex ) {
			return '';
		}

		$hex = str_replace( '#', '', $hex );

		if ( 3 == strlen( $hex ) ) {
			$r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
			$g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
			$b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
		} else {
			$r = hexdec( substr( $hex, 0, 2 ) );
			$g = hexdec( substr( $hex, 2, 2 ) );
			$b = hexdec( substr( $hex, 4, 2 ) );
		}

		return array( $r, $g, $b );

	}

	/**
	 * Gets the font type (character set) of a string
	 *
	 * @access private
	 * @since  1.0.4
	 * @param  string $string String to check
	 * @return string         Font type
	 */
	public function get_font_type( $string = '' ) {

		if ( ! $string ) {
			return 'latin';
		}

		if ( mb_strlen( $string ) != strlen( $string ) ) {
			return 'mb';
		}

		return 'latin';

	}

	/**
	 * Set custom font
	 *
	 * @access private
	 * @since  1.0.4
	 * @param  object $fpdf         The FPDF object
	 * @param  array  $default_font The default font
	 * @return boolean              True if the custom font was set
	 */
	public function set_custom_font( $fpdf, $default_font ) {

		$custom_font = apply_filters( 'sensei_certificates_custom_font', false );

		if ( $custom_font ) {

			if ( ! isset( $custom_font['family'] ) || ! $custom_font['family'] ) {
				$custom_font['family'] = $default_font['font_family'];
			}

			if ( ! isset( $custom_font['size'] ) || ! $custom_font['size'] ) {
				$custom_font['size'] = $default_font['font_size'];
			}

			$fpdf->SetFont( $custom_font['family'], '', $custom_font['size'] );

			return true;
		}

		return false;
	}

	/** Helper methods ******************************************************/

	/**
	 * Returns the value for $meta_name, or empty string
	 *
	 * @access public
	 * @since 1.0.0
	 * @param string $meta_name untranslated meta name
	 * @return string value for $meta_name or empty string
	 */
	private function get_item_meta_value( $meta_name ) {

		// no item set
		if ( ! $this->item ) {
			return '';
		}

		foreach ( $this->item as $name => $value ) {

			if ( __( $meta_name, 'sensei-certificates' ) == $name ) {

				return $value;

			}
		}

		// not found
		return '';

	}


	/**
	 * save_post_meta function.
	 *
	 * Does the save
	 *
	 * @access public
	 * @param string $post_key (default: '')
	 * @param int    $post_id (default: 0)
	 * @return void
	 */
	public function save_post_meta( $post_key = '', $post_id = 0 ) {
		if (
			empty( $_POST['course_certificates_meta_nonce'] )
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Leave nonce value unmodified.
			|| ! wp_verify_nonce( wp_unslash( $_POST['course_certificates_meta_nonce'] ), 'course_certificates_save_data' )
		) {
			return;
		}

		// Get the meta key.
		$meta_key       = '_' . $post_key;
		$new_meta_value = isset( $_POST[ $post_key ] ) && !empty($_POST[ $post_key ]) ? intval( $_POST[ $post_key ] ) : '';
		// Get the meta value of the custom field key.
		$meta_value = get_post_meta( $post_id, $meta_key, true );
		// If a new meta value was added and there was no previous value, add it.
		if ( $new_meta_value && '' == $meta_value ) {
			add_post_meta( $post_id, $meta_key, $new_meta_value, true );
		} elseif ( $new_meta_value && $new_meta_value != $meta_value ) {
			// If the new meta value does not match the old value, update it.
			update_post_meta( $post_id, $meta_key, $new_meta_value );
		} elseif ( '' == $new_meta_value && $meta_value ) {
			// If there is no new meta value but an old value exists, delete it.
			delete_post_meta( $post_id, $meta_key, $meta_value );
		}

	}


	/**
	 * Add column headings to the "lesson" post list screen.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  array $defaults
	 * @return array $new_columns
	 */
	public function add_column_headings( $defaults ) {

		$new_columns                                = $defaults;
		$new_columns['course-certificate-template'] = _x( 'Certificate Template', 'column name', 'sensei-certificates' );

		return $new_columns;

	}

	/**
	 * Add data for our newly-added custom columns.
	 *
	 * @access public
	 * @since  1.0.0
	 * @param  string $column_name
	 * @param  int    $id
	 * @return void
	 */
	public function add_column_data( $column_name, $id ) {

		global $wpdb, $post;

		switch ( $column_name ) {

			case 'course-certificate-template':
				$course_certificate_template_id = get_post_meta( $id, '_course_certificate_template', true );

				if ( 0 < absint( $course_certificate_template_id ) ) {
					/* translators: %s is replaced with the title of the certificate template */
					echo '<a href="' . esc_url( get_edit_post_link( absint( $course_certificate_template_id ) ) ) . '" title="' . esc_attr( sprintf( __( 'Edit %s', 'sensei-certificates' ), get_the_title( absint( $course_certificate_template_id ) ) ) ) . '">' . esc_html( get_the_title( absint( $course_certificate_template_id ) ) ) . '</a>';

				}

				break;

			default:
				break;
		}

	}

}
