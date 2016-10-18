<?php
/**
 * Plugin Name: PageBuilder
 * Description: Drag and drop page building UI for WordPress
 * Version: 0.1.0
 * Author: Nik Sudan
 * Author URI: http://niksudan.com
 */

// Options
define( 'PB_META_DATA', '_pb_data' );							// Postmeta containing the post's layout data
define( 'PB_META_ENABLED', '_pb_enabled' );						// Whether or not PageBuilder is active for a post
define( 'PB_POSTTYPES', serialize( array( 'page', 'post' ) ) ); // Posts that can use the PageBuilder interface

/**
 * Returns whether the post is using PageBuilder
 */
function is_pagebuilder( $post_id = null )
{
	if ( $post_id == null ) {
		global $post;
		$post_id = $post->ID;
	}
	return get_post_meta( $post_id, PB_META_ENABLED, true );
}

/**
 * Inits PageBuilder
 */
function pb_init()
{
	// Load the PageBuilder class
	require_once( 'pagebuilder.class.php' );
}
add_action( 'admin_init', 'pb_init' );

/**
 * Adds PageBuilder meta box to post types
 */
function pb_meta_box()
{
	foreach( unserialize( PB_POSTTYPES ) as $screen ) {

		// Show the PageBuilder boxes
		add_meta_box( 'pb_meta', 'PageBuilder', 'pb_meta_content', $screen, 'normal', 'high' );
		add_meta_box( 'pb_meta_preview', 'PageBuilder Preview', 'pb_meta_preview_content', $screen, 'normal', 'high' );
	}
}
add_action( 'add_meta_boxes', 'pb_meta_box' );

/**
 * Displays the PageBuilder meta box content
 */
function pb_meta_content( $post )
{
	$enabled = get_post_meta( $post->ID, PB_META_ENABLED, true );
	$data = get_post_meta( $post->ID, PB_META_DATA, true );
	?>

	<!-- jQuery -->
	<script type="text/javascript" src="<?= plugins_url() . '/pagebuilder/js/jquery.min.js'  ?>"></script>
	<script type="text/javascript" src="<?= plugins_url() . '/pagebuilder/js/jquery-ui.min.js' ?>"></script>

	<!-- PageBuilder -->
	<link rel="stylesheet" href="<?= plugins_url() . '/pagebuilder/style.css' ?>">
	<script id="pb_src" type="text/javascript" src="<?= plugins_url() . '/pagebuilder/js/pagebuilder.js' ?>" data-renderUrl = "<?= plugins_url() . '/pagebuilder/render.php' ?>"></script>

	<label><input id="pbToggle" name="pb_enabled" type="checkbox" <?= $enabled ? 'checked="checked"' : '' ?>> Use PageBuilder</label>

	<style>
		#pbInfoMessage {
			display: none;
		}
	</style>

	<div id="pbDisabled">
		<p><strong>Warning:</strong> When PageBuilder is enabled, it will overwrite whatever is in the content editor.</p>
	</div>

	<div id="pbEnabled"></div>

	<script type="text/javascript">
		var url = "<?= plugins_url() . '/pagebuilder/admin.php' ?>",
			data = "<?= addslashes($data) ?>";

		jQuery(document).ready(function($)
		{
			pb_reloadPageBuilder();
			$('#pbToggle').change(function(){pb_reloadPageBuilder(this.checked)});
		});

		function pb_reloadPageBuilder(enabled)
		{
			$.get(url, {enabled: enabled, data: data}, function(response)
			{
				$('#pbEnabled').empty();
				$('#pbEnabled').html(response);
				if (enabled) {
					pb_init();
				}
			});
		}
	</script>

	<?php if ($enabled) : ?><script>jQuery(document).ready(function($) { pb_reloadPageBuilder(true) })</script><?php endif; ?>

	<?php
}

/**
 * Displays the PageBuilder preview area
 */
function pb_meta_preview_content( $post )
{
	?>
	<p><strong>Please Note:</strong> The preview might look much different on the actual site.</p>
	<hr>
	<div id="pbPreview"></div>
	<?php
}

/**
 * Save the PageBuilder data and generate HTML for save
 */
function pb_save_post( $post_id )
{
	global $post;
	if ( in_array( $post->post_type, unserialize( PB_POSTTYPES ) ) ) {
		if ( isset( $_POST ) ) {

			// Set PageBuilder status
			$enabled = isset( $_POST['pb_enabled'] ) && $_POST['pb_enabled'] == 'on' ? true : false;
			update_post_meta( $post_id, PB_META_ENABLED, $enabled );

			if ( $enabled && isset( $_POST['pb_data'] ) ) {

				// Save postmeta
				$data = $_POST['pb_data'];
				update_post_meta( $post_id, PB_META_DATA, $data );

				// Prepare PageBuilder data
				$page = new PageBuilder();
				$page->loadData( stripslashes( $data ) );
				$post->post_content = $page->render();

				// Update post_content
				global $wpdb;
				$wpdb->update('wp_posts', array(
					'post_content' => $post->post_content
				), array( 'ID' => $post->ID ));

			}
		}
	}
}
add_action( 'save_post', 'pb_save_post' );