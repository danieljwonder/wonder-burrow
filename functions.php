<?php
/**
 * Wonder Burrow — Ollie child theme.
 *
 * Block themes do not auto-load the child style.css, so enqueue it
 * after the global styles so its custom-property overrides win.
 */

add_action( 'wp_enqueue_scripts', function () {
	$version = wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'wonder-burrow',
		get_stylesheet_uri(),
		array( 'global-styles' ),
		$version
	);

	// In <head> on purpose: applies the visitor's stored light/dark
	// choice before first paint, so there is no flash of wrong scheme.
	wp_enqueue_script(
		'wonder-burrow-scheme',
		get_stylesheet_directory_uri() . '/assets/scheme-toggle.js',
		array(),
		$version,
		false
	);

	// The rabbit source-reveal on archive items.
	wp_enqueue_script(
		'wonder-burrow-rabbit',
		get_stylesheet_directory_uri() . '/assets/rabbit.js',
		array(),
		$version,
		true
	);
}, 20 );

/**
 * Notes synced from Nostr/Farcaster usually carry tweet URLs inline,
 * mid-sentence — but WordPress only auto-embeds URLs that sit alone on
 * their own line. Lift twitter/x status links still sitting in plain
 * text and append the embed card after the note. Render-time only: the
 * stored note content stays verbatim to the synced event.
 *
 * Runs at priority 20 — after core autoembed (8), wpautop (10), and
 * any plugin content filters — and the lookbehinds only match URLs
 * that are not already inside markup (an href, an embed blockquote's
 * link text). So if the sync plugin later ships its own embedding,
 * nothing here matches and this filter is a natural no-op. No
 * coordination between theme and plugin required.
 */
add_filter( 'the_content', function ( $content ) {
	if ( 'note' !== get_post_type() ) {
		return $content;
	}

	if ( ! preg_match_all( '#(?<!href=")(?<!href=\')(?<!">)https?://(?:www\.)?(?:twitter\.com|x\.com)/[\w.]+/status/\d+#i', $content, $matches ) ) {
		return $content;
	}

	global $wp_embed;
	$append = '';
	foreach ( array_unique( $matches[0] ) as $url ) {
		$embed = $wp_embed->shortcode( array(), $url );
		if ( $embed && ( false !== strpos( $embed, '<blockquote' ) || false !== strpos( $embed, '<iframe' ) ) ) {
			$append .= '<figure class="dw-note-embed">' . $embed . '</figure>';
		} elseif ( preg_match( '#(?:twitter|x)\.com/([\w.]+)/status/#i', $url, $m ) ) {
			// X's oEmbed endpoint is unreliable; fall back to a styled
			// link card so the note still gets a visual affordance.
			$append .= '<p class="dw-note-embed dw-embed-card"><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">@' . esc_html( $m[1] ) . ' on X ↗</a></p>';
		}
	}
	return $content . $append;
}, 20 );

/**
 * Nostr for WP blanks core/post-date blocks on single note pages (it
 * predates themes that style note meta deliberately — flagged in
 * docs/plugin-handover-note-embeds.md). Rebuild the date line when
 * that happens so the meta — and the rabbit that anchors to it —
 * survives. Becomes a no-op the moment the plugin stops stripping.
 */
add_filter( 'render_block_core/post-date', function ( $content, $block ) {
	if ( '' !== $content || ! is_singular( 'note' ) ) {
		return $content;
	}
	return sprintf(
		'<div class="wp-block-post-date dw-mono has-secondary-color has-text-color has-x-small-font-size"><time datetime="%s">%s</time></div>',
		esc_attr( get_the_date( 'c' ) ),
		esc_html( get_the_date() )
	);
}, 11, 2 );

/**
 * Provenance data for the rabbit reveal. Read-only and public — it only
 * surfaces the sync metadata that Nostr for WP, BurrowSync for
 * Farcaster, and BurrowSync for X record on published posts/notes,
 * plus the site's relay list.
 */
add_action( 'rest_api_init', function () {
	register_rest_route( 'wonder-burrow/v1', '/source/(?P<id>\d+)', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => function ( $request ) {
			$post = get_post( (int) $request['id'] );
			if ( ! $post || 'publish' !== $post->post_status || ! in_array( $post->post_type, array( 'post', 'note' ), true ) ) {
				return new WP_Error( 'wonder_burrow_no_source', 'No source available.', array( 'status' => 404 ) );
			}

			$id      = $post->ID;
			$sources = array();

			$event_id = get_post_meta( $id, '_nostr_event_id', true );
			if ( $event_id ) {
				$options   = get_option( 'nostr_for_wp_options', array() );
				$sources[] = array(
					'network' => 'nostr',
					'label'   => 'Nostr event',
					'id'      => $event_id,
					'url'     => 'https://njump.me/' . rawurlencode( $event_id ),
					'synced'  => (string) get_post_meta( $id, '_nostr_synced_at', true ),
					'origin'  => (string) get_post_meta( $id, '_nostr_origin', true ),
					'relays'  => ( isset( $options['relays'] ) && is_array( $options['relays'] ) ) ? array_values( array_map( 'strval', $options['relays'] ) ) : array(),
				);
			}

			$cast_hash = get_post_meta( $id, '_farcaster_cast_hash', true );
			if ( $cast_hash ) {
				$sources[] = array(
					'network' => 'farcaster',
					'label'   => 'Farcaster cast',
					'id'      => $cast_hash,
					'url'     => (string) get_post_meta( $id, '_farcaster_cast_url', true ),
					'synced'  => (string) get_post_meta( $id, '_farcaster_synced_at', true ),
					'origin'  => (string) get_post_meta( $id, '_farcaster_origin', true ),
				);
			}

			$tweet_id = get_post_meta( $id, '_x_tweet_id', true );
			if ( $tweet_id ) {
				$sources[] = array(
					'network' => 'x',
					'label'   => 'X post',
					'id'      => $tweet_id,
					'url'     => (string) get_post_meta( $id, '_x_tweet_url', true ),
					'synced'  => (string) get_post_meta( $id, '_x_synced_at', true ),
					'origin'  => (string) get_post_meta( $id, '_x_origin', true ),
				);
			}

			return rest_ensure_response( array(
				'id'      => $id,
				'kind'    => $post->post_type,
				'sources' => $sources,
			) );
		},
	) );
} );

add_action( 'after_setup_theme', function () {
	add_editor_style( 'style.css' );
} );

/**
 * Ollie Pro's Site Setup Wizard stores a brand palette (option "style":
 * "orange" on this site) and merges it over theme.json at render time,
 * which silently overrides the child theme's palette. Re-assert this
 * theme's palette after all other filters so theme.json stays the source
 * of truth for color tokens.
 */
add_filter( 'wp_theme_json_data_theme', function ( $theme_json ) {
	return $theme_json->update_with( array(
		'version'  => 3,
		'settings' => array(
			'color' => array(
				'palette' => array(
					array( 'name' => 'Primary', 'slug' => 'primary', 'color' => '#FF6B0A' ),
					array( 'name' => 'Primary Accent', 'slug' => 'primary-accent', 'color' => '#FBEADC' ),
					array( 'name' => 'Primary Alt', 'slug' => 'primary-alt', 'color' => '#FFB27A' ),
					array( 'name' => 'Primary Alt Accent', 'slug' => 'primary-alt-accent', 'color' => '#4A2410' ),
					array( 'name' => 'Main', 'slug' => 'main', 'color' => '#171411' ),
					array( 'name' => 'Main Accent', 'slug' => 'main-accent', 'color' => '#DAD4C9' ),
					array( 'name' => 'Base', 'slug' => 'base', 'color' => '#F7F4EC' ),
					array( 'name' => 'Secondary', 'slug' => 'secondary', 'color' => '#6C6258' ),
					array( 'name' => 'Tertiary', 'slug' => 'tertiary', 'color' => '#FFFAF0' ),
					array( 'name' => 'Border Light', 'slug' => 'border-light', 'color' => '#E2DCD1' ),
					array( 'name' => 'Border Dark', 'slug' => 'border-dark', 'color' => '#4A443D' ),
				),
			),
		),
	) );
}, PHP_INT_MAX );

/**
 * Mix long-form posts and Nostr microblog notes (the `note` CPT) in the
 * archive Query Loop, like the draft's unified archive. The filter
 * receives the inner post-template block, which only exposes the query
 * via context — so the mixed archive is keyed on queryId 10 (set in the
 * wonder-burrow/archive-list pattern). Front-end only; the editor
 * preview still shows posts.
 */
add_filter( 'query_loop_block_query_vars', function ( $query, $block ) {
	$query_id = isset( $block->context['queryId'] ) ? (int) $block->context['queryId'] : -1;
	if ( 10 === $query_id && post_type_exists( 'note' ) ) {
		$query['post_type'] = array( 'post', 'note' );
	}
	return $query;
}, 10, 2 );
