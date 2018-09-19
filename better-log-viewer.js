(function( el, render ) {
	document.addEventListener('DOMContentLoaded', main, false);
	function main() {
		var container = document.querySelector('#better-log-viewer-scroll');
		if ( ! container ) {
			console.info( 'Could not find element #better-log-viewer-scroll' );
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
			const timeout = () => {
				setTimeout( () => {
					wp.apiFetch( { path: '/better-log-viewer/v1/debug.log' } )
						.then( data => { this.setState( { logLines: data } ) } );
					timeout();
				}, 1000);
			}
			timeout();
		}
		render() {
			return el( 'div',
				{
					style: {
						height: '500px',
						width: '100%',
						overflow: 'auto',
						fontFamily: 'courier',
					},
				}, 
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
