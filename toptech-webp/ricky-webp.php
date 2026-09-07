<?php
/**
 * Plugin Name: Ricky WebP Optimizer
 * Description: One-click WebP. Converts every JPEG and PNG in your Media Library to WebP, auto-converts new uploads, and serves WebP automatically to browsers that support it. Just install and activate - no settings to configure. Originals are never deleted.
 * Version: 1.0.0
 * Author: Ricky Tools
 * License: GPLv2 or later
 * Requires at least: 5.5
 * Requires PHP: 7.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RICKY_WEBP_VER', '1.0.0' );
define( 'RICKY_WEBP_QUALITY', 82 );
define( 'RICKY_WEBP_BATCH', 10 );
define( 'RICKY_WEBP_MARKER', 'Ricky WebP' );

/**
 * Detect an available WebP engine. Returns 'gd', 'imagick', or '' (none).
 */
function ricky_webp_engine() {
	if ( function_exists( 'imagewebp' ) ) {
		return 'gd';
	}
	if ( class_exists( 'Imagick' ) ) {
		try {
			$fmts = Imagick::queryFormats( 'WEBP' );
			if ( ! empty( $fmts ) ) {
				return 'imagick';
			}
		} catch ( Exception $e ) {
			return '';
		}
	}
	return '';
}

/**
 * Convert one JPEG/PNG file to <source>.webp. The original is kept intact.
 */
function ricky_webp_convert_file( $src, $force = false ) {
	if ( ! is_string( $src ) || ! is_readable( $src ) ) {
		return false;
	}
	$ext = strtolower( pathinfo( $src, PATHINFO_EXTENSION ) );
	if ( ! in_array( $ext, array( 'jpg', 'jpeg', 'png' ), true ) ) {
		return false;
	}
	$dest = $src . '.webp';
	if ( ! $force && file_exists( $dest ) && filemtime( $dest ) >= filemtime( $src ) ) {
		return true;
	}

	$engine = ricky_webp_engine();
	if ( '' === $engine ) {
		return false;
	}

	if ( function_exists( 'ini_set' ) ) {
		@ini_set( 'memory_limit', '512M' );
	}

	if ( 'imagick' === $engine ) {
		try {
			$img = new Imagick( $src );
			if ( 'png' === $ext ) {
				$img->setImageAlphaChannel( Imagick::ALPHACHANNEL_ACTIVATE );
			}
			$img->setImageFormat( 'webp' );
			$img->setImageCompressionQuality( RICKY_WEBP_QUALITY );
			$img->setOption( 'webp:method', '4' );
			$ok = $img->writeImage( $dest );
			$img->clear();
			$img->destroy();
			return $ok && file_exists( $dest ) && filesize( $dest ) > 0;
		} catch ( Exception $e ) {
			return false;
		}
	}

	// GD engine.
	if ( 'png' === $ext ) {
		$image = @imagecreatefrompng( $src );
		if ( ! $image ) {
			return false;
		}
		imagepalettetotruecolor( $image );
		imagealphablending( $image, false );
		imagesavealpha( $image, true );
	} else {
		$image = @imagecreatefromjpeg( $src );
		if ( ! $image ) {
			return false;
		}
	}
	$ok = @imagewebp( $image, $dest, RICKY_WEBP_QUALITY );
	imagedestroy( $image );
	if ( $ok && file_exists( $dest ) && filesize( $dest ) > 0 ) {
		return true;
	}
	if ( file_exists( $dest ) && 0 === (int) filesize( $dest ) ) {
		@unlink( $dest );
	}
	return false;
}

/**
 * List every JPEG/PNG under wp-content/uploads (all sizes, all folders).
 */
function ricky_webp_scan_uploads() {
	$uploads = wp_get_upload_dir();
	$base = isset( $uploads['basedir'] ) ? $uploads['basedir'] : '';
	$list = array();
	if ( '' === $base || ! is_dir( $base ) ) {
		return $list;
	}
	try {
		$it = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::LEAVES_ONLY
		);
		foreach ( $it as $file ) {
			if ( ! $file->isFile() ) {
				continue;
			}
			$ext = strtolower( $file->getExtension() );
			if ( in_array( $ext, array( 'jpg', 'jpeg', 'png' ), true ) ) {
				$list[] = $file->getPathname();
			}
		}
	} catch ( Exception $e ) {
		return $list;
	}
	return $list;
}

/**
 * Process up to $limit not-yet-converted images. Shared by the button and cron.
 */
function ricky_webp_run_batch( $limit, $force = false ) {
	$files = ricky_webp_scan_uploads();
	$total = count( $files );

	$failed_list = get_option( 'ricky_webp_failed', array() );
	if ( ! is_array( $failed_list ) ) {
		$failed_list = array();
	}
	if ( $force ) {
		$failed_list = array();
	}

	$done = 0;
	$todo = array();
	foreach ( $files as $f ) {
		$webp = $f . '.webp';
		$has  = file_exists( $webp ) && filemtime( $webp ) >= filemtime( $f );
		if ( $has && ! $force ) {
			$done++;
			continue;
		}
		if ( isset( $failed_list[ $f ] ) && ! $force ) {
			continue;
		}
		$todo[] = $f;
	}

	$converted = 0;
	$processed = 0;
	foreach ( $todo as $src ) {
		if ( $processed >= $limit ) {
			break;
		}
		if ( ricky_webp_convert_file( $src, $force ) ) {
			$converted++;
			$done++;
			unset( $failed_list[ $src ] );
		} else {
			$failed_list[ $src ] = time();
		}
		$processed++;
	}

	update_option( 'ricky_webp_failed', $failed_list, false );

	$finished = ( 0 === $converted ) || ( count( $todo ) - $processed <= 0 );

	return array(
		'total'       => $total,
		'done'        => min( $done, $total ),
		'converted'   => $converted,
		'failedTotal' => count( $failed_list ),
		'finished'    => $finished ? 1 : 0,
	);
}

/**
 * Auto-convert freshly uploaded images (full size plus every generated size).
 */
add_filter( 'wp_generate_attachment_metadata', 'ricky_webp_on_upload', 20, 2 );
function ricky_webp_on_upload( $metadata, $attachment_id ) {
	$file = get_attached_file( $attachment_id );
	if ( $file && is_string( $file ) ) {
		ricky_webp_convert_file( $file );
		$dir = trailingslashit( dirname( $file ) );
		if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
			foreach ( $metadata['sizes'] as $size ) {
				if ( ! empty( $size['file'] ) ) {
					ricky_webp_convert_file( $dir . $size['file'] );
				}
			}
		}
	}
	return $metadata;
}

/* -------------------------------------------------------------------------
 * WebP delivery via .htaccess (transparent - URLs stay .jpg/.png).
 * ---------------------------------------------------------------------- */
function ricky_webp_htaccess_lines() {
	return array(
		'<IfModule mod_rewrite.c>',
		'  RewriteEngine On',
		'  RewriteCond %{HTTP_ACCEPT} image/webp',
		'  RewriteCond %{REQUEST_FILENAME}\.webp -f',
		'  RewriteRule (.+)\.(jpe?g|png)$ $1.$2.webp [T=image/webp,L]',
		'</IfModule>',
		'<IfModule mod_headers.c>',
		'  <FilesMatch "\.(jpe?g|png)$">',
		'    Header append Vary Accept',
		'  </FilesMatch>',
		'</IfModule>',
		'<IfModule mod_mime.c>',
		'  AddType image/webp .webp',
		'</IfModule>',
	);
}

function ricky_webp_write_htaccess() {
	if ( ! function_exists( 'insert_with_markers' ) ) {
		require_once ABSPATH . 'wp-admin/includes/misc.php';
	}
	$htaccess = ABSPATH . '.htaccess';
	if ( ! file_exists( $htaccess ) ) {
		if ( ! is_writable( ABSPATH ) ) {
			return false;
		}
	} elseif ( ! is_writable( $htaccess ) ) {
		return false;
	}
	return insert_with_markers( $htaccess, RICKY_WEBP_MARKER, ricky_webp_htaccess_lines() );
}

function ricky_webp_remove_htaccess() {
	if ( ! function_exists( 'insert_with_markers' ) ) {
		require_once ABSPATH . 'wp-admin/includes/misc.php';
	}
	$htaccess = ABSPATH . '.htaccess';
	if ( file_exists( $htaccess ) && is_writable( $htaccess ) ) {
		insert_with_markers( $htaccess, RICKY_WEBP_MARKER, array() );
	}
}

/* -------------------------------------------------------------------------
 * Background processing via WP-Cron (so it finishes even without clicking).
 * ---------------------------------------------------------------------- */
add_filter( 'cron_schedules', 'ricky_webp_cron_schedule' );
function ricky_webp_cron_schedule( $schedules ) {
	$schedules['ricky_webp_5min'] = array(
		'interval' => 300,
		'display'  => 'Every 5 minutes (Ricky WebP)',
	);
	return $schedules;
}

add_action( 'ricky_webp_cron', 'ricky_webp_cron_run' );
function ricky_webp_cron_run() {
	if ( '' === ricky_webp_engine() ) {
		return;
	}
	ricky_webp_run_batch( 15, false );
}

/* -------------------------------------------------------------------------
 * Activation / deactivation / uninstall.
 * ---------------------------------------------------------------------- */
register_activation_hook( __FILE__, 'ricky_webp_activate' );
function ricky_webp_activate() {
	ricky_webp_write_htaccess();
	if ( ! wp_next_scheduled( 'ricky_webp_cron' ) ) {
		wp_schedule_event( time() + 60, 'ricky_webp_5min', 'ricky_webp_cron' );
	}
}

register_deactivation_hook( __FILE__, 'ricky_webp_deactivate' );
function ricky_webp_deactivate() {
	ricky_webp_remove_htaccess();
	$ts = wp_next_scheduled( 'ricky_webp_cron' );
	if ( $ts ) {
		wp_unschedule_event( $ts, 'ricky_webp_cron' );
	}
}

register_uninstall_hook( __FILE__, 'ricky_webp_uninstall' );
function ricky_webp_uninstall() {
	delete_option( 'ricky_webp_failed' );
}

/* -------------------------------------------------------------------------
 * Admin page + AJAX.
 * ---------------------------------------------------------------------- */
add_action( 'admin_menu', 'ricky_webp_menu' );
function ricky_webp_menu() {
	add_media_page( 'WebP Optimizer', 'WebP Optimizer', 'manage_options', 'ricky-webp', 'ricky_webp_admin_page' );
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'ricky_webp_action_link' );
function ricky_webp_action_link( $links ) {
	$url = admin_url( 'upload.php?page=ricky-webp' );
	array_unshift( $links, '<a href="' . esc_url( $url ) . '">Optimize</a>' );
	return $links;
}

add_action( 'wp_ajax_ricky_webp_run', 'ricky_webp_ajax_run' );
function ricky_webp_ajax_run() {
	check_ajax_referer( 'ricky_webp', 'nonce' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'forbidden' );
	}
	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 0 );
	}
	$force = ! empty( $_POST['force'] );
	$res   = ricky_webp_run_batch( RICKY_WEBP_BATCH, $force );
	wp_send_json_success( $res );
}

function ricky_webp_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$engine = ricky_webp_engine();
	$files  = ricky_webp_scan_uploads();
	$total  = count( $files );
	$done   = 0;
	foreach ( $files as $f ) {
		if ( file_exists( $f . '.webp' ) ) {
			$done++;
		}
	}
	$pct          = $total > 0 ? (int) round( $done / $total * 100 ) : 100;
	$nonce        = wp_create_nonce( 'ricky_webp' );
	$ajax         = admin_url( 'admin-ajax.php' );
	$engine_label = 'gd' === $engine ? 'GD (imagewebp)' : ( 'imagick' === $engine ? 'Imagick' : 'NONE' );
	?>
	<div class="wrap">
		<h1>WebP Optimizer</h1>
		<?php if ( '' === $engine ) : ?>
			<div class="notice notice-error"><p><strong>WebP is not available on this server.</strong> Ask your host to enable the GD extension with WebP support, or the Imagick extension. Conversion cannot run until then.</p></div>
		<?php else : ?>
			<p>Image engine detected: <strong><?php echo esc_html( $engine_label ); ?></strong>. Originals are never deleted - a matching <code>.webp</code> file is created next to each image and served automatically to supporting browsers.</p>
		<?php endif; ?>

		<div style="max-width:640px;background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:18px 20px;margin:16px 0;">
			<p id="rkw-status" style="font-size:14px;margin:0 0 10px;"><?php echo esc_html( $done . ' / ' . $total . ' images have WebP (' . $pct . '%).' ); ?></p>
			<div style="background:#eef0f3;border-radius:20px;height:16px;overflow:hidden;">
				<div id="rkw-bar" style="height:100%;width:<?php echo (int) $pct; ?>%;background:#2271b1;transition:width .3s;"></div>
			</div>
			<p style="margin-top:16px;">
				<button class="button button-primary" id="rkw-run" <?php disabled( '' === $engine ); ?>>Optimize all images now</button>
				<button class="button" id="rkw-force" <?php disabled( '' === $engine ); ?>>Regenerate all (force)</button>
			</p>
			<p class="description">Large libraries are processed in small batches to avoid timeouts. You can leave this page open; it also keeps converting in the background every few minutes.</p>
		</div>

		<script>
		( function () {
			var RKW = { ajax: <?php echo wp_json_encode( $ajax ); ?>, nonce: <?php echo wp_json_encode( $nonce ); ?> };
			var bar = document.getElementById( 'rkw-bar' );
			var txt = document.getElementById( 'rkw-status' );
			var btn = document.getElementById( 'rkw-run' );
			var btnF = document.getElementById( 'rkw-force' );
			var running = false;
			function step( force ) {
				var fd = new FormData();
				fd.append( 'action', 'ricky_webp_run' );
				fd.append( 'nonce', RKW.nonce );
				if ( force ) { fd.append( 'force', '1' ); }
				return fetch( RKW.ajax, { method: 'POST', body: fd, credentials: 'same-origin' } ).then( function ( r ) { return r.json(); } );
			}
			function loop( force ) {
				if ( running ) { return; }
				running = true;
				btn.disabled = true; btnF.disabled = true;
				var guard = 0;
				function finish( m ) {
					txt.textContent = m;
					btn.disabled = false; btnF.disabled = false; running = false;
				}
				function next() {
					guard++;
					if ( guard > 100000 ) { finish( 'Stopped.' ); return; }
					step( force ).then( function ( j ) {
						if ( ! j || ! j.success ) { finish( 'Error during conversion.' ); return; }
						var d = j.data;
						var pct = d.total ? Math.round( d.done / d.total * 100 ) : 100;
						bar.style.width = pct + '%';
						var msg = d.done + ' / ' + d.total + ' images have WebP (' + pct + '%).';
						if ( d.failedTotal ) { msg += ' ' + d.failedTotal + ' could not be converted.'; }
						txt.textContent = msg;
						force = false;
						if ( d.finished ) { finish( msg + ' Done.' ); return; }
						next();
					} ).catch( function ( e ) { finish( 'Error: ' + e ); } );
				}
				next();
			}
			btn.addEventListener( 'click', function () { loop( false ); } );
			btnF.addEventListener( 'click', function () { loop( true ); } );
		} )();
		</script>
	</div>
	<?php
}

/* Admin notice if the server lacks a WebP engine. */
add_action( 'admin_notices', 'ricky_webp_admin_notice' );
function ricky_webp_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( $screen && 'media_page_ricky-webp' === $screen->id ) {
		return;
	}
	if ( '' === ricky_webp_engine() ) {
		echo '<div class="notice notice-warning is-dismissible"><p><strong>Ricky WebP:</strong> this server has no WebP engine (GD or Imagick). Ask your host to enable it so images can be converted.</p></div>';
	}
}
