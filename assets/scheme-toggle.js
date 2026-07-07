/**
 * Light/dark toggle. Default follows the OS via prefers-color-scheme;
 * clicking a .dw-scheme-toggle button forces a scheme and remembers it
 * in localStorage. Loaded in <head> so the stored choice applies
 * before first paint.
 */
( function () {
	var KEY = 'dw-scheme';
	var root = document.documentElement;

	function apply( value ) {
		if ( value === 'dark' || value === 'light' ) {
			root.setAttribute( 'data-dw-scheme', value );
		} else {
			root.removeAttribute( 'data-dw-scheme' );
		}
	}

	try {
		apply( localStorage.getItem( KEY ) );
	} catch ( e ) {}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.dw-scheme-toggle' );
		if ( ! button ) {
			return;
		}
		var systemDark = window.matchMedia( '(prefers-color-scheme: dark)' ).matches;
		var current = root.getAttribute( 'data-dw-scheme' ) || ( systemDark ? 'dark' : 'light' );
		var next = current === 'dark' ? 'light' : 'dark';
		try {
			localStorage.setItem( KEY, next );
		} catch ( e ) {}
		apply( next );
	} );
} )();
