(function( el, render ) {
	document.addEventListener('DOMContentLoaded', main, false);
	function main() {
		var b = document.querySelector('#wpbody');
		render( el( function() { return el('div', {}, 'ochoa' ) } ), b);
	}
} )( wp.element.createElement, wp.element.render );
