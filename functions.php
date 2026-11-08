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
 * Notes synced from Nostr/Farcaster usually carry tweet or video URLs inline,
 * mid-sentence — but WordPress only auto-embeds URLs that sit alone on
 * their own line. Lift still-plain twitter/x status links and common video
 * URLs (including those wrapped in <a href>) and append cards after the
 * note. Render-time only: stored note content stays verbatim to the event.
 *
 * Runs at priority 20 — after core autoembed (8), wpautop (10), and plugin
 * content filters. Nostr for WP defers to this filter on Wonder Burrow via
 * nfw_note_embeds_enabled.
 */
add_filter( 'nfw_note_embeds_enabled', '__return_false' );

/**
 * URL variants for matching the same tweet/video link in stored HTML.
 *
 * @param string $url Normalized embed URL.
 * @return string[]
 */
function wonder_burrow_embed_url_variants( $url ) {
	$url      = untrailingslashit( html_entity_decode( $url, ENT_QUOTES, 'UTF-8' ) );
	$variants = array( $url );

	if ( preg_match( '#^https://x\.com#i', $url ) ) {
		$variants[] = preg_replace( '#^https://x\.com#i', 'https://twitter.com', $url );
	}
	if ( preg_match( '#^https://twitter\.com#i', $url ) ) {
		$variants[] = preg_replace( '#^https://twitter\.com#i', 'https://x.com', $url );
	}
	if ( preg_match( '#^https://www\.#i', $url ) ) {
		$variants[] = preg_replace( '#^https://www\.#i', 'https://', $url );
	} elseif ( preg_match( '#^https://#i', $url ) ) {
		$variants[] = preg_replace( '#^https://#i', 'https://www.', $url );
	}

	return array_values( array_unique( array_filter( $variants ) ) );
}

/**
 * Remove a URL from note HTML once it is represented by an embed card.
 *
 * @param string $content Post HTML.
 * @param string $url     Embedded URL.
 * @return string
 */
function wonder_burrow_remove_embed_url_from_content( $content, $url ) {
	foreach ( wonder_burrow_embed_url_variants( $url ) as $variant ) {
		$quoted = preg_quote( $variant, '#' );

		$content = preg_replace(
			'#<a[^>]+href=["\']' . $quoted . '["\'][^>]*>.*?</a>#is',
			'',
			$content
		);

		$content = preg_replace( '#\s*' . $quoted . '\s*#i', ' ', $content );
	}

	$content = preg_replace( '#<p>\s*(<br\s*/?>\s*)*</p>#i', '', $content );
	$content = preg_replace( '#\s{2,}#', ' ', $content );

	return trim( $content );
}

/**
 * Whether note HTML already contains an embed for this URL.
 *
 * @param string $content Post HTML.
 * @param string $url     Candidate URL.
 * @return bool
 */
function wonder_burrow_url_already_embedded( $content, $url ) {
	if ( preg_match( '#(?:twitter|x)\.com/.+/status/(\d+)#i', $url, $m ) ) {
		$status_id = $m[1];
		if ( false !== strpos( $content, 'status/' . $status_id )
			&& preg_match( '#<(?:blockquote[^>]*twitter-tweet|figure[^>]*dw-note-embed)#i', $content ) ) {
			return true;
		}
	}

	if ( preg_match( '#(?:youtu\.be/|youtube\.com/watch\?v=)([A-Za-z0-9_-]+)#', $url, $m ) ) {
		$video_id = $m[1];
		if ( preg_match( '#<iframe[^>]+(?:youtube|youtu\.be)#i', $content )
			|| ( false !== strpos( $content, $video_id ) && preg_match( '#dw-note-embed#', $content ) ) ) {
			return true;
		}
	}

	return false;
}

add_filter( 'the_content', function ( $content ) {
	if ( 'note' !== get_post_type() ) {
		return $content;
	}

	if ( doing_filter( 'get_the_excerpt' ) ) {
		return $content;
	}

	$urls = array();

	if ( preg_match_all( '#(?<!href=")(?<!href=\')(?<!src=")(?<!src=\')(?<!">)https?://(?:www\.)?(?:twitter\.com|x\.com)/[\w.]+/status/\d+#i', $content, $plain ) ) {
		$urls = array_merge( $urls, $plain[0] );
	}

	if ( preg_match_all( '#<a[^>]+href=["\'](https?://[^"\']+)["\'][^>]*>#i', $content, $anchors ) ) {
		foreach ( $anchors[1] as $url ) {
			if ( preg_match( '#(?:twitter\.com|x\.com)/[\w.]+/status/\d+#i', $url )
				|| preg_match( '#(?:youtube\.com/watch\?|youtu\.be/)#i', $url ) ) {
				$urls[] = $url;
			}
		}
	}

	if ( preg_match_all( '#(?<!href=")(?<!href=\')(?<!src=")(?<!src=\')(?<!">)https?://(?:www\.)?(?:youtube\.com/watch\?v=|youtu\.be/)[^\s<>"\'\]]+#i', $content, $videos ) ) {
		$urls = array_merge( $urls, $videos[0] );
	}

	$urls = array_values( array_unique( array_filter( array_map( 'esc_url_raw', $urls ) ) ) );
	$urls = array_values( array_filter( $urls, function ( $url ) use ( $content ) {
		return ! wonder_burrow_url_already_embedded( $content, $url );
	} ) );
	if ( empty( $urls ) ) {
		return $content;
	}

	global $wp_embed;
	if ( ! $wp_embed instanceof WP_Embed ) {
		if ( ! class_exists( 'WP_Embed' ) ) {
			require_once ABSPATH . WPINC . '/class-wp-embed.php';
		}
		$wp_embed = new WP_Embed();
	}

	$append   = '';
	$embedded = array();

	foreach ( $urls as $url ) {
		if ( preg_match( '#^https://x\.com#i', $url ) ) {
			$url = preg_replace( '#^https://x\.com#i', 'https://twitter.com', $url );
		}

		$embed      = $wp_embed->shortcode( array(), $url );
		$did_embed  = false;

		if ( $embed && ( false !== strpos( $embed, '<blockquote' ) || false !== strpos( $embed, '<iframe' ) || false !== strpos( $embed, 'twitter-tweet' ) ) ) {
			$append    .= '<figure class="dw-note-embed">' . $embed . '</figure>';
			$did_embed  = true;
		} elseif ( preg_match( '#(?:twitter|x)\.com/([\w.]+)/status/#i', $url, $m ) ) {
			$append    .= '<p class="dw-note-embed dw-embed-card"><a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">@' . esc_html( $m[1] ) . ' on X ↗</a></p>';
			$did_embed  = true;
		}

		if ( $did_embed ) {
			$embedded[] = $url;
		}
	}

	foreach ( $embedded as $url ) {
		$content = wonder_burrow_remove_embed_url_from_content( $content, $url );
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
					array( 'name' => 'Primary', 'slug' => 'primary', 'color' => '#CE4770' ),
					array( 'name' => 'Primary Accent', 'slug' => 'primary-accent', 'color' => '#DED5D0' ),
					array( 'name' => 'Primary Alt', 'slug' => 'primary-alt', 'color' => '#D4572B' ),
					array( 'name' => 'Primary Alt Accent', 'slug' => 'primary-alt-accent', 'color' => '#B16803' ),
					array( 'name' => 'Main', 'slug' => 'main', 'color' => '#2C232E' ),
					array( 'name' => 'Main Accent', 'slug' => 'main-accent', 'color' => '#A59C9C' ),
					array( 'name' => 'Base', 'slug' => 'base', 'color' => '#F8EFE7' ),
					array( 'name' => 'Secondary', 'slug' => 'secondary', 'color' => '#72696D' ),
					array( 'name' => 'Tertiary', 'slug' => 'tertiary', 'color' => '#FDF7F3' ),
					array( 'name' => 'Border Light', 'slug' => 'border-light', 'color' => '#DED5D0' ),
					array( 'name' => 'Border Dark', 'slug' => 'border-dark', 'color' => '#A59C9C' ),
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
