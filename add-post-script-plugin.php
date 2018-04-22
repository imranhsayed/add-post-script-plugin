<?php
/*
Plugin Name: Add Post Script Plugin
Description: A plugin to insert style/scripts in individual post or page codes
Plugin URI:   https://github.com/imranhsayed/add-post-script-plugin
Author: Imran Sayed
Author URI:   https://profiles.wordpress.org/gsayed786
Version: 1.0.0
Text Domain:  add-post-script-plugin
License: GPLv2
License URI:  https://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! function_exists( 'ihs_script_add_custom_box' ) ) {
	/**
	 * Adds two meta boxes below the text editor of the Post and Page edit screens
	 */
	function ihs_script_add_custom_box() {

		$screens = get_post_types( '', 'names' );
		foreach ( $screens as $screen ) {
			add_meta_box(
				'ihs_section_id',
				__( 'IHS add script', 'add-post-script-plugin' ),
				'ihs_display_custom_script_meta_boxes',
				$screen
			);
		}
	}
	add_action( 'add_meta_boxes', 'ihs_script_add_custom_box' );
}

if ( ! function_exists( 'ihs_display_custom_script_meta_boxes' ) ) {
	/* Prints the box content */
	function ihs_display_custom_script_meta_boxes( $post ) {

		// Use nonce for verification
		wp_nonce_field( plugin_basename( __FILE__ ), 'ihs_add_script_nonce_name' );

		// The actual fields for data entry
		// Use get_post_meta to retrieve an existing value from the database and use the value for the form
		$value        = get_post_meta( $post->ID, 'ihs_add_script_header_meta', true );
		$value_footer = get_post_meta( $post->ID, 'ihs_add_script_footer_meta', true );
		?>
		<div class="postbox">
			<div class="inside">
				<table width="100%">
					<tr>
						<td valign="top">
							<p>
								<label for="ihs-add-script-header">
									<?php _e( "add script / style to be added to the header of the page", 'add-post-script-plugin' ); ?>
								</label>
								<label for=""><textarea id="ihs-add-script-header" class="ihs-add-script-header" name="ihs-add-script-header" size="25"><?php echo $value ?></textarea></label>
							</p>
						</td>
					</tr>
					<tr>
						<td valign="top">
							<p>
								<label for="ihs-add-script-header">
									<?php _e( "add script to be added to the footer of the page before the </body> ( Only put javascript codes here )", 'ihs-add-script-footer' ); ?>
								</label>
								<label for="">
									<textarea id="ihs-add-script-footer" class="ihs-add-script-footer" name="ihs-add-script-footer" size="25"><?php echo $value_footer ?></textarea>
								</label>
							</p>
							<p><?php _e( "You should put the code with the script tags<code> &lt;script type='text/javascript'&gt; the code &lt;/script&gt;</code>", 'add-post-script-plugin' ); ?></p>
							<p><?php _e( "You should put the code with the script tags<code> &lt;style&gt; the code &lt;/script&gt;</code>", 'add-post-script-plugin' ); ?></p>
						</td>
					</tr>
				</table>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'ihs_script_save_custom_box' ) ) {

	/**
	 * Saves our custom meta data, when the post is saved
	 *
	 * @param $post_id
	 */
	function ihs_script_save_custom_box( $post_id ) {

		// First we need to check if the current user is authorised to do this action.
		if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		// Secondly we need to check if the user intended to change this value.
		if ( ! isset( $_POST['ihs_add_script_nonce_name'] ) || ! wp_verify_nonce( $_POST['ihs_add_script_nonce_name'], plugin_basename( __FILE__ ) ) ) {
			return;
		}

		// Thirdly we can save the value to the database

		//if saving in a custom table, get post_ID
		$post_ID = isset( $_POST['post_ID'] ) ? $_POST['post_ID'] : - 1;
		//sanitize user input
		$header_script = isset( $_POST['ihs-add-script-header'] ) ? $_POST['ihs-add-script-header'] : '';

		$footer_script = isset( $_POST['ihs-add-script-footer'] ) ? $_POST['ihs-add-script-footer'] : '';

		update_post_meta( $post_ID, 'ihs_add_script_header_meta', $header_script );
		update_post_meta( $post_ID, 'ihs_add_script_footer_meta', $footer_script );

	}

	add_action( 'save_post', 'ihs_script_save_custom_box' );
}

if ( ! function_exists( 'ihs_get_current_page_id' ) ) {
	function ihs_get_current_page_id() {
		global $post;

		$id = false;
		if ( ! isset( $post ) ) {
			return false;
		}

		if ( is_singular() ) {
			$id = $post->ID;
		}
		return $id;
	}
}

if ( ! function_exists( 'ihs_add_script_header' ) ) {
	/**
	 * Adds the style/script to the head section of the post/poge.
	 */
	function ihs_add_script_header() {

		$output = '';

		$id = ihs_get_current_page_id();
		if ( $id ) {
			$output = stripslashes( get_post_meta( $id, 'ihs_add_script_header_meta', true ) );
		}
		echo $output;
	}
	add_action( 'wp_head', 'ihs_add_script_header', 100 );
}

if ( ! function_exists( 'ihs_add_script_footer' ) ) {
	/**
	 * Add scripts to the footer section of the post/page.
	 */
	function ihs_add_script_footer() {
		$output = '';
		$id = ihs_get_current_page_id();
		if ( $id ) {
			$output = stripslashes( get_post_meta( $id, 'ihs_add_script_footer_meta', true ) );
		}
		echo $output;
	}
	add_action( 'wp_footer', 'ihs_add_script_footer', 100 );
}

if ( ! function_exists( 'ihs_add_post_enqueue_script' ) ) {
	/**
	 * Enqueue plugin stylesheet.
	 */
	function ihs_add_post_enqueue_script() {
		wp_enqueue_style( 'ihs_add_post', plugins_url( 'add-post-script-plugin' ) . '/style.css' );
	}
	add_action( 'admin_enqueue_scripts', 'ihs_add_post_enqueue_script' );
}