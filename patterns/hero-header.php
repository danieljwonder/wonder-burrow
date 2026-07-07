<?php
/**
 * Title: Native Archive Hero
 * Slug: wonder-burrow/hero-header
 * Categories: featured, ollie/hero
 * Description: Monospace eyebrow with light/dark toggle, then avatar inline with the oversized site title, lede, and nav.
 */
?>
<!-- wp:group {"tagName":"header","align":"wide","className":"dw-hero","style":{"spacing":{"padding":{"top":"var:preset|spacing|large","bottom":"var:preset|spacing|small"}}},"layout":{"type":"constrained","contentSize":"920px"}} -->
<header class="wp-block-group alignwide dw-hero" style="padding-top:var(--wp--preset--spacing--large);padding-bottom:var(--wp--preset--spacing--small)">
	<!-- wp:group {"className":"dw-hero-top","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between","verticalAlignment":"center"}} -->
	<div class="wp-block-group dw-hero-top">
		<!-- wp:paragraph {"className":"dw-eyebrow"} -->
		<p class="dw-eyebrow">danielwonder.com</p>
		<!-- /wp:paragraph -->

		<!-- wp:html -->
		<button class="dw-scheme-toggle" type="button" aria-label="Toggle light or dark mode">◐</button>
		<!-- /wp:html -->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"dw-hero-brand","style":{"spacing":{"blockGap":"20px"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
	<div class="wp-block-group dw-hero-brand">
		<!-- wp:html -->
		<img class="dw-avatar" src="<?php echo esc_url( get_avatar_url( get_option( 'admin_email' ), array( 'size' => 144 ) ) ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" width="72" height="72" loading="lazy" decoding="async" />
		<!-- /wp:html -->

		<!-- wp:site-title {"level":1,"className":"dw-display"} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:paragraph {"textColor":"secondary","fontSize":"medium"} -->
	<p class="has-secondary-color has-text-color has-medium-font-size">Marketing technologist, decentralised web builder, practical AI operator.</p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph {"className":"dw-mono"} -->
	<p class="dw-mono"><a href="/blog/">archive</a> · <a href="/note/">notes</a> · <a href="/profile/">links</a> · <a href="/feed/">rss</a></p>
	<!-- /wp:paragraph -->
</header>
<!-- /wp:group -->
