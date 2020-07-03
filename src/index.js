import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { useSelect, withDispatch } from '@wordpress/data';
import { TextControl } from "@wordpress/components";
import { __ } from '@wordpress/i18n';

let PluginMetaFields = ( disp ) => {
	const props = useSelect( ( select ) => {
		return {
			_plugin_slug: select('core/editor').getEditedPostAttribute('meta')['_plugin_slug'],
			_plugin_github_url: select('core/editor').getEditedPostAttribute('meta')['_plugin_github_url']
		};
	} );

	return (
		<>
			<TextControl
				value={ props._plugin_slug }
				label={ __( 'Slug', 'my-plugins' ) }
				onChange={ ( value ) => { disp.onMetaFieldChange( value, '_plugin_slug' ); console.log( value ); } }
			/>
			<TextControl
				value={ props._plugin_github_url }
				label={ __( 'Github URL', 'my-plugins' ) }
				onChange={ ( value ) => disp.onMetaFieldChange( value, '_plugin_github_url' ) }
			/>
		</>
	)
}

PluginMetaFields = withDispatch(
	(dispatch) => {
		return {
			onMetaFieldChange: ( value, field ) => {
				dispatch( 'core/editor' ).editPost( { meta: { [ field ] : value } } );
			}
		}
	}
)(PluginMetaFields);

const PluginDocumentSettingPanelDemo = () => {
	// Only display this on our plugins post type
	const postType = useSelect( select => select( 'core/editor' ).getCurrentPostType() );
	if ( 'plugin' !== postType ) {
		return null;
	}
	return(
		<PluginDocumentSettingPanel
			name="my-plugins-sidebar-panel"
			title={ __( 'My plugin info', 'my-plugins' ) }
			className="my-plugins-sidebar-content"
			intialOpen={ true }
			icon='admin-plugins'
		>
			<PluginMetaFields />
		</PluginDocumentSettingPanel>
	);
}
registerPlugin( 'plugin-document-setting-panel-demo', {
	render: PluginDocumentSettingPanelDemo,
} );
