( function( wp ) {
	const registerPlugin = wp.plugins.registerPlugin;
	const PluginSidebar = wp.editPost.PluginSidebar;
	const el = wp.element.createElement;
	const Text = wp.components.TextControl;
	const withSelect = wp.data.withSelect;
	const withDispatch = wp.data.withDispatch;

	const mapPluginSlugSelectToProps = function( select ) {
		return {
			metaFieldValue: select( 'core/editor' )
				.getEditedPostAttribute( 'meta' )
				[ 'plugin_slug' ]
		}
	}

	const mapPluginSlugDispatchToProps = function( dispatch ) {
		return {
			setMetaFieldValue: function( value ) {
				dispatch( 'core/editor' ).editPost(
					{ meta: { plugin_slug: value } }
				);
			}
		}
	}

	const PluginSlugField = function( props ) {
		return el( Text, {
			label: 'Plugin Slug',
			value: props.metaFieldValue,
			onChange: function( content ) {
				props.setMetaFieldValue( content );
			},
		} );
	}

	const PluginSlugFieldWithData = withSelect( mapPluginSlugSelectToProps )( PluginSlugField );
	const PluginSlugFieldWithDataAndActions = withDispatch( mapPluginSlugDispatchToProps )( PluginSlugFieldWithData );

	const mapGithubURLSelectToProps = function( select ) {
		return {
			metaFieldValue: select( 'core/editor' )
				.getEditedPostAttribute( 'meta' )
				[ 'plugin_github_url' ]
		}
	}

	const mapGithubURLDispatchToProps = function( dispatch ) {
		return {
			setMetaFieldValue: function( value ) {
				dispatch( 'core/editor' ).editPost(
					{ meta: { plugin_github_url: value } }
				);
			}
		}
	}

	const GithubURLField = function( props ) {
		return el( Text, {
			label: 'Plugin Github URL',
			value: props.metaFieldValue,
			onChange: function( content ) {
				props.setMetaFieldValue( content );
			},
		} );
	}

	const GithubURLFieldWithData = withSelect( mapGithubURLSelectToProps )( GithubURLField );
	const GithubURLFieldWithDataAndActions = withDispatch( mapGithubURLDispatchToProps )( GithubURLFieldWithData );

	registerPlugin( 'my-plugin-sidebar', {
		render: function() {
			return el( PluginSidebar,
				{
					name: 'my-plugins-sidebar',
					icon: 'admin-plugins',
					title: 'My plugin info',
				},
				el( 'div',
					{ className: 'plugin-sidebar-content' },
					el( PluginSlugFieldWithDataAndActions ),
					el( GithubURLFieldWithDataAndActions )
				)
			);
		}
	} );
} )( window.wp );
