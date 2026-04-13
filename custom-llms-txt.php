<?php
/**
 * Plugin Name: Custom LLMS.txt Generator
 * Description: Generate llms.txt dynamically or as a static file with CPT support.
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Version: 1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================
 * ADMIN MENU
 * ========================================= */
add_action( 'admin_menu', function () {
	add_menu_page(
		'LLMS.txt Settings',
		'LLMS.txt',
		'manage_options',
		'llms-txt-settings',
		'llms_txt_settings_page',
		'dashicons-media-text'
	);
} );

/* =========================================
 * SETTINGS PAGE
 * ========================================= */
function llms_txt_settings_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_POST['llms_save'] ) && check_admin_referer( 'llms_settings_action', 'llms_nonce' ) ) {

		$post_types = isset( $_POST['post_types'] ) ? array_map( 'sanitize_text_field', (array) $_POST['post_types'] ) : array();

		update_option( 'llms_post_types', $post_types );
		update_option( 'llms_heading', sanitize_text_field( wp_unslash( $_POST['llms_heading'] ?? '' ) ) );
		update_option( 'llms_description', sanitize_textarea_field( wp_unslash( $_POST['llms_description'] ?? '' ) ) );

		// Delete old file
		$file = trailingslashit( ABSPATH ) . 'llms.txt';
		if ( file_exists( $file ) ) {
			unlink( $file );
		}

		// Regenerate file
		llms_generate_file();

		echo '<div class="updated"><p>Settings saved and llms.txt regenerated!</p></div>';
	}

	$selected    = get_option( 'llms_post_types', array( 'post', 'page' ) );
	$heading     = wp_strip_all_tags( get_option( 'llms_heading', 'Your Website' ) );
	$description = wp_strip_all_tags( get_option( 'llms_description', 'Your website description' ) );

	$post_types = get_post_types(
		array(
			'public'  => true,
			'show_ui' => true,
		),
		'objects'
	);

	$exclude = array( 'attachment', 'revision', 'nav_menu_item' );
	foreach ( $exclude as $type ) {
		unset( $post_types[ $type ] );
	}

	$llms_url  = home_url( '/llms.txt' );
	$file_path = trailingslashit( ABSPATH ) . 'llms.txt';
	$disabled  = ! file_exists( $file_path ) ? 'style="pointer-events:none;opacity:0.5;"' : '';
	?>

	<div class="wrap">
		<h1>LLMS.txt Settings</h1>

		<form method="post">
			<?php wp_nonce_field( 'llms_settings_action', 'llms_nonce' ); ?>

			<h3>Heading</h3>
			<input type="text" name="llms_heading" value="<?php echo esc_attr( $heading ); ?>" style="width:100%;">

			<h3>Description</h3>
			<textarea name="llms_description" style="width:100%; height:120px;"><?php echo esc_textarea( $description ); ?></textarea>

			<h3>Select Post Types</h3>

			<?php foreach ( $post_types as $pt ) : ?>
				<label>
					<input type="checkbox" name="post_types[]" value="<?php echo esc_attr( $pt->name ); ?>"
						<?php checked( in_array( $pt->name, $selected, true ) ); ?>>
					<?php echo esc_html( $pt->label ); ?>
				</label><br>
			<?php endforeach; ?>

			<br><br>

			<input type="submit" name="llms_save" class="button button-primary" value="Save Settings">

			<a href="<?php echo esc_url( $llms_url ); ?>" target="_blank" class="button button-secondary" <?php echo $disabled; ?>>
				Open llms.txt
			</a>
		</form>
	</div>

	<?php
}

/* =========================================
 * GENERATE CONTENT
 * ========================================= */
function llms_generate_content() {

	$post_types  = get_option( 'llms_post_types', array( 'post', 'page' ) );
	$heading     = wp_strip_all_tags( get_option( 'llms_heading', 'Your Website' ) );
	$description = wp_strip_all_tags( get_option( 'llms_description', 'Your website description' ) );

	$output  = '# ' . $heading . "\n\n";
	$output .= '> ' . $description . "\n\n";
	$output .= "---\n\n";

	foreach ( $post_types as $post_type ) {

		$label   = ucfirst( str_replace( array( '-', '_' ), ' ', $post_type ) );
		$output .= '## ' . $label . "\n\n";

		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);

		if ( empty( $posts ) ) {
			$output .= "- No content found\n\n";
			continue;
		}

		foreach ( $posts as $post ) {

			$url = function_exists( 'permalink_manager_get_permalink' )
				? permalink_manager_get_permalink( $post->ID )
				: get_permalink( $post->ID );

			if ( empty( $url ) ) {
				continue;
			}

			$title = html_entity_decode( get_the_title( $post->ID ), ENT_QUOTES, 'UTF-8' );

			$output .= '- [' . $title . '](' . esc_url( $url ) . ')' . "\n";
		}

		$output .= "\n";
	}

	return $output;
}

/* =========================================
 * GENERATE STATIC FILE (UTF-8 FIX)
 * ========================================= */
function llms_generate_file() {

	$content = llms_generate_content();

	// Ensure UTF-8 encoding
	if ( ! mb_detect_encoding( $content, 'UTF-8', true ) ) {
		$content = mb_convert_encoding( $content, 'UTF-8', 'auto' );
	}

	// Add BOM (helps fix special character issues)
	$content = "\xEF\xBB\xBF" . $content;

	$file = trailingslashit( ABSPATH ) . 'llms.txt';

	require_once ABSPATH . 'wp-admin/includes/file.php';
	WP_Filesystem();

	global $wp_filesystem;

	if ( $wp_filesystem ) {
		$wp_filesystem->put_contents( $file, $content, FS_CHMOD_FILE );
	}
}

/* =========================================
 * DYNAMIC /llms.txt SUPPORT
 * ========================================= */
add_action( 'init', function () {
	add_rewrite_rule( '^llms\.txt$', 'index.php?llms_txt=1', 'top' );
	add_rewrite_tag( '%llms_txt%', '1' );
} );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'llms_txt';
	return $vars;
} );

add_action( 'template_redirect', function () {

	if ( get_query_var( 'llms_txt' ) ) {

		header( 'Content-Type: text/plain; charset=UTF-8' );

		$content = llms_generate_content();

		if ( ! mb_detect_encoding( $content, 'UTF-8', true ) ) {
			$content = mb_convert_encoding( $content, 'UTF-8', 'auto' );
		}

		echo $content;
		exit;
	}
} );

/* =========================================
 * FLUSH REWRITE
 * ========================================= */
register_activation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );
