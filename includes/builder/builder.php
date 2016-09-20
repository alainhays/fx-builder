<?php
namespace fx_builder\builder;
use fx_builder\Functions as Fs;
if ( ! defined( 'WPINC' ) ) { die; }

/* Load Class */
Builder::get_instance();

/**
 * Builder
 * @since 1.0.0
 */
class Builder{

	/**
	 * Returns the instance.
	 */
	public static function get_instance(){
		static $instance = null;
		if ( is_null( $instance ) ) $instance = new self;
		return $instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {

		/* Add it after editor in edit screen */
		add_action( 'edit_form_after_editor', array( $this, 'form' ) );

		/* Load Underscore Templates + Print Scripts */
		add_action( 'admin_footer', array( $this, 'load_templates' ) );

		/* Save Builder Data */
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );

		/* Format Content Ajax */
		add_action( 'wp_ajax_fxb_item_format_content', array( $this, 'ajax_format_content' ) );
		add_action( 'wp_ajax_fxb_item_wpautop', array( $this, 'ajax_wpautop' ) );

		/* Scripts */
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ), 99 );
	}


	/**
	 * Builder Form
	 */
	public function form( $post ){
		if( ! post_type_supports( $post->post_type, 'fx_builder' ) ){ return; }
		$post_id = $post->ID;
		?>

		<div id="fxb-wrapper">

			<div class="fxb-modal-overlay" style="display:none;"></div>

			<div id="fxb-menu">
				<p><a href="#" class="button button-primary fxb-add-row"><?php _e( 'Add Row', 'fx-builder' ); ?></a></p>
			</div><!-- #fxb-menu -->

			<div id="fxb">
			</div><!-- #fxb -->

			<input type="hidden" name="_fxb_row_ids" value="<?php echo esc_attr( get_post_meta( $post_id, '_fxb_row_ids', true ) ); ?>" autocomplete="off"/>
			<input type="hidden" name="_fxb_db_version" value="<?php echo esc_attr( VERSION ); ?>" autocomplete="off"/>
			<?php wp_nonce_field( __FILE__ , 'fxb_nonce' ); // create nonce ?>

			<?php /* Load Custom Editor */ ?>

			<?php Functions::render_settings( array(
				'id'        => 'fxb-editor', // data-target
				'title'     => __( 'Edit Content', 'fx-builder' ),
				'width'     => '800px',
				'callback'  => function(){

					wp_editor( '', 'fxb_editor', array(
						'tinymce'       => array(
							'wp_autoresize_on' => false,
							'resize'           => false,
						),
						'editor_height' => 300,
					) );
				},
			));?>

		</div><!-- #fxb-wrapper -->
		<?php
	}


	/**
	 * Admin Footer Scripts
	 */
	public function load_templates(){
		global $post_type;
		if( ! post_type_supports( $post_type, 'fx_builder' ) ){ return; }
		$post_id = get_the_ID();

		/* Row Template */
		require_once( PATH . 'templates/tmpl-row.php' );

		/* Item Template */
		require_once( PATH . 'templates/tmpl-item.php' );

		/* Rows data */
		$rows_data   = get_post_meta( $post_id, '_fxb_rows', true );
		$row_ids     = get_post_meta( $post_id, '_fxb_row_ids', true );
		if( ! $rows_data && $row_ids && is_array( $rows_data ) && is_array( $row_ids ) ){ return false; }
		$rows        = explode( ',', $row_ids );

		/* Items data */
		$items_data  = get_post_meta( $post_id, '_fxb_items', true );
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				var row_template = wp.template( 'fxb-row' );

				<?php foreach( $rows as $row_id ){ ?>
					<?php if( isset( $rows_data[$row_id] ) ){ ?>
						$( '#fxb' ).append( row_template( <?php echo wp_json_encode( $rows_data[$row_id] ); ?> ) );
					<?php } ?>
				<?php } // end foreach ?>

				<?php if( $items_data && is_array( $items_data ) ){ ?>
					var item_template = wp.template( 'fxb-item' );
					<?php foreach( $items_data as $item_id => $item ){ ?>
						<?php if( isset( $rows_data[$item['row_id']] ) ){ ?>
							$( '.fxb-row[data-id="<?php echo $item['row_id']; ?>"] .fxb-col[data-col_index="<?php echo $item['col_index']; ?>"] .fxb-col-content' ).append( item_template( <?php echo wp_json_encode( $item ); ?> ) );
						<?php } ?>
					<?php } // end foreach ?>
				<?php } ?>
			} );
		</script>
		<?php
	}


	/**
	 * Save Page Builder Data
	 * @since 1.0.0
	 */
	public function save( $post_id, $post ){
		$request = stripslashes_deep( $_POST );
		if ( ! isset( $request['fxb_nonce'] ) || ! wp_verify_nonce( $request['fxb_nonce'], __FILE__ ) ){
			return false;
		}
		if( defined('DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
			return false;
		}
		$post_type = get_post_type_object( $post->post_type );
		if ( !current_user_can( $post_type->cap->edit_post, $post_id ) ){
			return false;
		}

		/* DB Version */
		if( isset( $request['_fxb_db_version'] ) ){
			if( $request['_fxb_db_version'] ){
				update_post_meta( $post_id, '_fxb_db_version', $request['_fxb_db_version'] );
			}
			else{
				delete_post_meta( $post_id, '_fxb_db_version' );
			}
		}
		else{
			delete_post_meta( $post_id, '_fxb_db_version' );
		}


		/* Row IDs */
		if( isset( $request['_fxb_row_ids'] ) ){
			if( $request['_fxb_row_ids'] ){
				update_post_meta( $post_id, '_fxb_row_ids', $request['_fxb_row_ids'] );
			}
			else{
				delete_post_meta( $post_id, '_fxb_row_ids' );
			}
		}
		else{
			delete_post_meta( $post_id, '_fxb_row_ids' );
		}

		/* Rows Datas */
		if( isset( $request['_fxb_rows'] ) ){
			if( $request['_fxb_rows'] ){
				update_post_meta( $post_id, '_fxb_rows', $request['_fxb_rows'] );
			}
			else{
				delete_post_meta( $post_id, '_fxb_rows' );
			}
		}
		else{
			delete_post_meta( $post_id, '_fxb_rows' );
		}

		/*  Items Datas */
		if( isset( $request['_fxb_items'] ) ){
			if( $request['_fxb_items'] ){
				update_post_meta( $post_id, '_fxb_items', $request['_fxb_items'] );
			}
			else{
				delete_post_meta( $post_id, '_fxb_items' );
			}
		}
		else{
			delete_post_meta( $post_id, '_fxb_items' );
		}
	}

	/**
	 * Ajax Format Content
	 */
	public function ajax_format_content(){

		/* Strip Slash */
		$request = stripslashes_deep( $_POST );

		/* Check Ajax */
		check_ajax_referer( 'fxb_ajax_nonce', 'nonce' );

		/* Format Content */
		$content = '';
		if( isset( $request['content'] ) && !empty( $request['content'] ) ){
			global $wp_embed;
			$content = $request['content'];
			$content = $wp_embed->run_shortcode( $content );
			$content = $wp_embed->autoembed( $content );
			$content = wptexturize( $content );
			$content = convert_smilies( $content );
			$content = convert_chars( $content );
			$content = wptexturize( $content );
			$content = do_shortcode( $content );
			$content = shortcode_unautop( $content );
			if( function_exists('wp_make_content_images_responsive') ) { /* WP 4.4+ */
				$content = wp_make_content_images_responsive( $content );
			}
			$content = wpautop( $content );
		}

		/* Output */
		echo $content;
		wp_die();
	}


	/**
	 * Ajax Format Content
	 */
	public function ajax_wpautop(){

		/* Strip Slash */
		$request = stripslashes_deep( $_POST );

		/* Check Ajax */
		check_ajax_referer( 'fxb_ajax_nonce', 'nonce' );

		/* Format Content */
		$content = '';
		if( isset( $request['content'] ) && !empty( $request['content'] ) ){
			$content = wpautop( $request['content'] );
		}

		/* Output */
		echo $content;
		wp_die();
	}


	/**
	 * Admin Scripts
	 * @since 1.0.0
	 */
	public function scripts( $hook_suffix ){
		global $post_type;
		if( ! post_type_supports( $post_type, 'fx_builder' ) ){ return; }

		/* In Page Edit Screen */
		if( in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) ){

			/* Enqueue CSS */
			wp_enqueue_style( 'fx-builder', URI . 'assets/page-builder.css', array(), VERSION );

			/* Enqueue JS: ROW */
			wp_enqueue_script( 'fx-builder-row', URI . 'assets/page-builder-row.js', array( 'jquery', 'jquery-ui-sortable', 'wp-util' ), VERSION, true );

			/* Enqueue JS: ITEM */
			wp_enqueue_script( 'fx-builder-item', URI . 'assets/page-builder-item.js', array( 'jquery', 'jquery-ui-sortable', 'wp-util' ), VERSION, true );
			$ajax_data = array(
				'ajax_url'         => admin_url( 'admin-ajax.php' ),
				'ajax_nonce'       => wp_create_nonce( 'fxb_ajax_nonce' ),
			);
			wp_localize_script( 'fx-builder-item', 'fxb_ajax', $ajax_data );
		}
	}


}