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
				timeout: 3000,
				logLines: []
			};
		}
		componentDidMount() {
			const timeout = () => {
				setTimeout( () => {
					wp.apiFetch( { path: '/better-log-viewer/v1/debug.log' } )
						.then( data => { this.setState( { logLines: data } ) } );
					timeout();
				}, this.state.timeout);
			}
			timeout();
		}
		render() {
			return el( 'div',
				{
					style: {
						height: '500px',
						width: '100%',
						overflowY: 'auto',
						fontFamily: 'courier',
					},
				},
				this.state.logLines.map( ( line, i ) => {
					return el( Line, {
						key: 'log-line-' + i,
					},
						el( 'pre', {
							style: {
								whiteSpace: 'pre-wrap',
								wordWrap: 'break-word',
							}
						},
							line
						)
					);
				} )
			);
		}
	}
	function Line( { children } ) {
		return el( wp.element.Fragment, {}, children );
	}
} )( wp.element.createElement, wp.element.render );
