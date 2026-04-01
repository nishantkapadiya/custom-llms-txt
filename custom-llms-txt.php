<?php
/**
 * Plugin Name: Custom LLMS.txt Generator
 * Description: Generate llms.txt dynamically or as a static file with CPT support.
 * Version: 1.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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

	if ( isset( $_POST['llms_save'] ) && check_admin_referer( 'llms_settings_action', 'llms_nonce' ) ) {

		$post_types = isset( $_POST['post_types'] ) ? array_map( 'sanitize_text_field', (array) $_POST['post_types'] ) : array();

		update_option( 'llms_post_types', $post_types );
		update_option( 'llms_heading', sanitize_text_field( wp_unslash( $_POST['llms_heading'] ?? '' ) ) );
		update_option( 'llms_description', sanitize_textarea_field( wp_unslash( $_POST['llms_description'] ?? '' ) ) );

		echo '<div class="updated"><p>' . esc_html__( 'Settings Saved', 'custom-llms-txt' ) . '</p></div>';
	}

	if ( isset( $_POST['llms_generate'] ) && check_admin_referer( 'llms_settings_action', 'llms_nonce' ) ) {
		llms_generate_file();
		echo '<div class="updated"><p>' . esc_html__( 'llms.txt file generated!', 'custom-llms-txt' ) . '</p></div>';
	}

	$selected    = get_option( 'llms_post_types', array( 'post', 'page' ) );
    $heading     = get_option( 'llms_heading', esc_html__( 'Your Website', 'custom-llms-txt' ) );
    $description = get_option( 'llms_description', esc_html__( 'Your website description', 'custom-llms-txt' ) );

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
	?>

	<div class="wrap">
		<h1><?php echo esc_html__( 'LLMS.txt Settings', 'custom-llms-txt' ); ?></h1>

		<form method="post">
			<?php wp_nonce_field( 'llms_settings_action', 'llms_nonce' ); ?>

			<h3><?php echo esc_html__( 'Heading', 'custom-llms-txt' ); ?></h3>
			<input type="text" name="llms_heading" value="<?php echo esc_attr( $heading ); ?>" style="width:100%;">

			<h3><?php echo esc_html__( 'Description', 'custom-llms-txt' ); ?></h3>
			<textarea name="llms_description" style="width:100%; height:100px;"><?php echo esc_textarea( $description ); ?></textarea>

			<h3><?php echo esc_html__( 'Select Post Types', 'custom-llms-txt' ); ?></h3>

			<?php foreach ( $post_types as $pt ) : ?>
				<label>
					<input type="checkbox" name="post_types[]" value="<?php echo esc_attr( $pt->name ); ?>"
						<?php checked( in_array( $pt->name, $selected, true ) ); ?>>
					<?php echo esc_html( $pt->label ); ?>
				</label><br>
			<?php endforeach; ?>

			<br><br>
			<input type="submit" name="llms_save" class="button button-primary"
				value="<?php echo esc_attr__( 'Save Settings', 'custom-llms-txt' ); ?>">

			<input type="submit" name="llms_generate" class="button button-secondary"
				value="<?php echo esc_attr__( 'Generate llms.txt', 'custom-llms-txt' ); ?>">
		</form>
	</div>

	<?php
}

/* =========================================
 * GENERATE CONTENT
 * ========================================= */
function llms_generate_content() {

	$post_types  = get_option( 'llms_post_types', array( 'post', 'page' ) );
	$heading     = get_option( 'llms_heading', 'Your Website' );
	$description = get_option( 'llms_description', 'Your website description' );

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

			// Permalink Manager support.
			if ( function_exists( 'permalink_manager_get_permalink' ) ) {
				$url = permalink_manager_get_permalink( $post->ID );
			} else {
				$url = get_permalink( $post->ID );
			}

			if ( empty( $url ) ) {
				continue;
			}

			$title   = html_entity_decode( get_the_title( $post->ID ), ENT_QUOTES, 'UTF-8' );
			$output .= '- [' . $title . '](' . esc_url( $url ) . ')' . "\n";
		}

		$output .= "\n";
	}

	return $output;
}

/* =========================================
 * GENERATE STATIC FILE
 * ========================================= */
function llms_generate_file() {

	$content = llms_generate_content();
	$file    = trailingslashit( ABSPATH ) . 'llms.txt';

	wp_filesystem();

	global $wp_filesystem;

	if ( $wp_filesystem ) {
		$wp_filesystem->put_contents( $file, $content, FS_CHMOD_FILE );
	}
}

/* =========================================
 * DYNAMIC /llms.txt SUPPORT
 * ========================================= */
add_action( 'init', function () {

	// Direct access fallback.
	if ( isset( $_SERVER['REQUEST_URI'] ) && '/llms.txt' === $_SERVER['REQUEST_URI'] ) {

		header( 'Content-Type: text/plain; charset=utf-8' );

		$file = trailingslashit( ABSPATH ) . 'llms.txt';

		if ( file_exists( $file ) ) {
			readfile( $file ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
		} else {
			echo llms_generate_content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		exit;
	}

	add_rewrite_rule( '^llms\.txt$', 'index.php?llms_txt=1', 'top' );
	add_rewrite_tag( '%llms_txt%', '1' );
} );

add_filter(
	'query_vars',
	function ( $vars ) {
		$vars[] = 'llms_txt';
		return $vars;
	}
);

add_action( 'template_redirect', function () {

	if ( get_query_var( 'llms_txt' ) ) {

		header( 'Content-Type: text/plain; charset=utf-8' );
		echo llms_generate_content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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