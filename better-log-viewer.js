(function( el, render ) {
	document.addEventListener('DOMContentLoaded', main, false);
	function main() {
		var container = document.querySelector('#better-log-viewer-container');
		if ( ! container ) {
			console.info( 'Could not find element #better-log-viewer-container' );
			return;
		}
		render( el( LogViewer ) , container);
	}

	class LogViewer extends wp.element.Component {
		constructor( props ) {
			super( props );
			this.state = {
				logLines: []
			};
		}
		componentDidMount() {
			wp.apiFetch( { path: '/better-log-viewer/v1/debug.log' } )
				.then( data => { this.setState( { logLines: data } ) } );
		}
		render() {
			return el( 'div', {}, 
				this.state.logLines.map( line => {
					return el( Line, {}, line );
				} )
			);
		}
	}
	function Line( { children } ) {
		return el( 'div', {}, children );
	}
} )( wp.element.createElement, wp.element.render );
