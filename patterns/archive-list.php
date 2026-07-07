<?php
/**
 * Title: Native Archive List
 * Slug: wonder-burrow/archive-list
 * Categories: featured, ollie/blog
 * Description: Hairline-ruled list mixing long-form posts and Nostr notes, with monospace metadata and pill filters, matching the native-archive draft.
 */
?>
<!-- wp:group {"tagName":"section","align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|medium","bottom":"var:preset|spacing|large"}}},"className":"dw-rule-top","layout":{"type":"constrained","contentSize":"920px"}} -->
<section class="wp-block-group alignwide dw-rule-top" style="padding-top:var(--wp--preset--spacing--medium);padding-bottom:var(--wp--preset--spacing--large)">
	<!-- wp:paragraph {"className":"dw-mono dw-pills dw-filter-pills","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|medium"}}}} -->
	<p class="dw-mono dw-pills dw-filter-pills" style="margin-bottom:var(--wp--preset--spacing--medium)"><a href="/">all</a> <a href="/blog/">writing</a> <a href="/note/">notes</a></p>
	<!-- /wp:paragraph -->

	<!-- wp:query {"queryId":10,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"className":"dw-archive-list","layout":{"type":"constrained","contentSize":"920px"}} -->
	<div class="wp-block-query dw-archive-list">
		<!-- wp:post-template -->
		<!-- wp:post-title {"isLink":true,"fontSize":"medium"} /-->

		<!-- wp:post-excerpt {"excerptLength":32,"textColor":"secondary"} /-->

		<!-- wp:post-content /-->

		<!-- wp:post-date {"isLink":true,"textColor":"secondary","className":"dw-mono","fontSize":"x-small"} /-->

		<!-- wp:post-terms {"term":"category","className":"dw-mono dw-pills","fontSize":"x-small"} /-->
		<!-- /wp:post-template -->

		<!-- wp:query-pagination {"className":"dw-mono","layout":{"type":"flex","justifyContent":"left"}} -->
		<!-- wp:query-pagination-previous /-->

		<!-- wp:query-pagination-numbers /-->

		<!-- wp:query-pagination-next /-->
		<!-- /wp:query-pagination -->
	</div>
	<!-- /wp:query -->
</section>
<!-- /wp:group -->
