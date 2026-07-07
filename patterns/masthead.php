<?php
/**
 * Title: Compact Masthead
 * Slug: wonder-burrow/masthead
 * Categories: ollie/header
 * Description: Small avatar and site name on the left, mono nav and light/dark toggle on the right — the hero, scaled down for inner pages.
 */
?>
<!-- wp:group {"tagName":"header","className":"dw-site-header","style":{"spacing":{"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|medium"}}},"layout":{"type":"constrained","contentSize":"920px"}} -->
<header class="wp-block-group dw-site-header" style="padding-top:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--medium)">
	<!-- wp:group {"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
	<div class="wp-block-group">
		<!-- wp:group {"style":{"spacing":{"blockGap":"12px"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
		<div class="wp-block-group">
			<!-- wp:html -->
			<img class="dw-avatar" src="<?php echo esc_url( get_avatar_url( get_option( 'admin_email' ), array( 'size' => 96 ) ) ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="40" height="40" loading="lazy" decoding="async" />
			<!-- /wp:html -->

			<!-- wp:site-title {"level":0} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"style":{"spacing":{"blockGap":"14px"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"className":"dw-mono"} -->
			<p class="dw-mono"><a href="/">archive</a> · <a href="/note/">notes</a> · <a href="/profile/">links</a> · <a href="/feed/">rss</a></p>
			<!-- /wp:paragraph -->

			<!-- wp:html -->
			<button class="dw-scheme-toggle" type="button" aria-label="Toggle light or dark mode">◐</button>
			<!-- /wp:html -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</header>
<!-- /wp:group -->
