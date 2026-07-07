/**
 * The rabbit source-reveal. The 🐇 sits inline at the end of the meta
 * line (date · kind 🐇); clicking it expands a provenance panel right
 * beneath that line — Nostr event, relays, Farcaster cast — fetched
 * from the theme's REST endpoint. Attached to every item in a
 * .dw-archive-list and to the post meta on single post/note pages.
 * No metadata on the normal post layer — that's the whole point.
 */
( function () {
	// Inline copy of assets/burrowsync-icon.svg (ears peeking out of the
	// burrow), drawn with currentColor so it follows the scheme.
	var ICON =
		'<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
		'<ellipse cx="12" cy="16.6" rx="9.5" ry="3.4" fill="none" stroke="currentColor" stroke-width="1.5"/>' +
		'<ellipse cx="9.2" cy="8" rx="1.7" ry="5.2" transform="rotate(-16 9.2 8)" fill="currentColor"/>' +
		'<ellipse cx="14.8" cy="8" rx="1.7" ry="5.2" transform="rotate(16 14.8 8)" fill="currentColor"/>' +
		'<path d="M6.8 16.6a5.2 5.2 0 0 1 10.4 0a5.2 1.7 0 0 1 -10.4 0z" fill="currentColor"/>' +
		'</svg>';

	function el( tag, className, text ) {
		var node = document.createElement( tag );
		if ( className ) {
			node.className = className;
		}
		if ( text ) {
			node.textContent = text;
		}
		return node;
	}

	function renderPanel( panel, data ) {
		panel.textContent = '';
		panel.appendChild( el( 'h4', null, 'Source' ) );

		if ( ! data || ! data.sources || ! data.sources.length ) {
			panel.appendChild( el( 'p', null, 'WordPress-native. No mirrors recorded for this item.' ) );
			return;
		}

		data.sources.forEach( function ( source ) {
			var block = el( 'div', 'dw-source-block' );
			block.appendChild( el( 'strong', null, source.label ) );
			block.appendChild( el( 'code', null, source.id ) );

			if ( source.relays && source.relays.length ) {
				block.appendChild( el( 'p', 'dw-source-meta', 'Relays: ' + source.relays.join( ' · ' ) ) );
			}
			if ( source.synced ) {
				block.appendChild( el( 'p', 'dw-source-meta', 'Synced: ' + source.synced ) );
			}
			if ( source.url ) {
				var links = el( 'p', 'dw-source-links' );
				var linkLabel = 'View cast';
				if ( source.network === 'nostr' ) {
					linkLabel = 'View on njump';
				} else if ( source.network === 'x' ) {
					linkLabel = 'View on X';
				}
				var a = el( 'a', null, linkLabel );
				a.href = source.url;
				a.target = '_blank';
				a.rel = 'noopener noreferrer';
				links.appendChild( a );
				block.appendChild( links );
			}
			panel.appendChild( block );
		} );
	}

	// Adds the rabbit at the end of a meta element and the collapsible
	// panel directly after it, so the reveal opens where you clicked.
	function attach( metaEl, postId ) {
		if ( ! metaEl || metaEl.querySelector( '.dw-rabbit' ) ) {
			return;
		}

		var button = el( 'button', 'dw-rabbit' );
		button.type = 'button';
		button.innerHTML = ICON;
		button.setAttribute( 'aria-expanded', 'false' );
		button.setAttribute( 'aria-label', 'Reveal source' );

		var panel = el( 'div', 'dw-source-panel dw-panel' );
		panel.hidden = true;

		metaEl.appendChild( document.createTextNode( ' · ' ) );
		metaEl.appendChild( button );
		metaEl.insertAdjacentElement( 'afterend', panel );

		var loaded = false;
		button.addEventListener( 'click', function () {
			var open = ! panel.hidden;
			panel.hidden = open;
			button.setAttribute( 'aria-expanded', String( ! open ) );
			if ( ! open ) {
				// Point the bubble's caret at the rabbit.
				var b = button.getBoundingClientRect();
				var p = panel.getBoundingClientRect();
				panel.style.setProperty( '--dw-caret-x', Math.max( 12, b.left - p.left + b.width / 2 - 6 ) + 'px' );
			}
			if ( open || loaded ) {
				return;
			}
			loaded = true;
			panel.appendChild( el( 'p', null, 'Fetching source…' ) );
			fetch( '/wp-json/wonder-burrow/v1/source/' + postId )
				.then( function ( response ) {
					return response.ok ? response.json() : null;
				} )
				.then( function ( data ) {
					renderPanel( panel, data );
				} )
				.catch( function () {
					renderPanel( panel, null );
				} );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		// Archive lists: one rabbit per item, on the date line.
		document.querySelectorAll( '.dw-archive-list .wp-block-post' ).forEach( function ( item ) {
			var idMatch = ( item.className || '' ).match( /\bpost-(\d+)\b/ );
			if ( idMatch ) {
				attach( item.querySelector( '.wp-block-post-date' ), idMatch[ 1 ] );
			}
		} );

		// Single post/note pages: the post's own date element — the
		// first one that is not part of a query loop (e.g. related
		// posts) or an archive list.
		var bodyMatch = ( document.body.className || '' ).match( /\bpostid-(\d+)\b/ );
		if ( bodyMatch && document.body.classList.contains( 'single' ) ) {
			var dates = document.querySelectorAll( '.wp-block-post-date' );
			for ( var i = 0; i < dates.length; i++ ) {
				if ( ! dates[ i ].closest( '.wp-block-query' ) && ! dates[ i ].closest( '.dw-archive-list' ) ) {
					attach( dates[ i ], bodyMatch[ 1 ] );
					break;
				}
			}
		}
	} );
} )();
