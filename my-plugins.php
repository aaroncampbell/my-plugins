<?php
/**
 * Plugin Name: myPlugins
 * Plugin URI: http://aarondcampbell.com/
 * Description: Plugin for showing your plugins
 * Version: 1.0.1
 * Author: Aaron D. Campbell
 * Author URI: http://aarondcampbell.com/
 * Text Domain: my-plugins
 */

class myPlugins {
	/**
	 * @var myPlugins - Static property to hold our singleton instance
	 */
	static $instance = false;

	private $_additional_plugin_info;

	/**
	 * Private for force the singleton
	 */
	private function __construct() {
		$this->_additional_plugin_info = apply_filters( 'my-plugins-additional-plugin-info', array(
			'github_url' => 'Github URL',
		) );

		add_action( 'after_setup_theme',                    array( $this, 'after_setup_theme'                    ) );
		add_action( 'admin_menu',                           array( $this, 'admin_menu'                           ) );
		add_action( 'wp_ajax_sort',                         array( $this, 'wp_ajax_sort'                         ) );
		add_action( 'updated_post_meta',                    array( $this, 'store_plugin_last_updated' ), null, 4   );
		add_action( 'add_post_metadata',                    array( $this, 'store_plugin_last_updated' ), null, 4   );
		add_action( 'parse_query',                          array( $this, 'order_plugins'                        ) );
		add_filter( 'plugin_info',                          array( $this, 'plugin_info'                          ) );
		add_action( 'save_post',                            array( $this, 'save_post'                            ) );
		add_action( 'save_post',                            array( $this, 'save_meta'                            ) );
		add_action( 'add_meta_boxes_plugin',                array( $this, 'add_meta_boxes_plugin'                ) );
		add_action( 'the_content',                          array( $this, 'the_content'                          ) );
		add_action( 'wp_footer',                            array( $this, 'wp_footer'                            ) );
		add_action( 'init',                                 array( $this, 'init'                                 ) );
		add_action( 'enqueue_block_editor_assets',          array( $this, 'sidebar_script_enqueue'               ) );
		add_action( 'enqueue_block_assets',                 array( $this, 'sidebar_style_enqueue'                ) );
	}

	/**
	 * Function to instantiate our class and make it a singleton
	 */
	public static function get_instance() {
		if ( !self::$instance )
			self::$instance = new self;

		return self::$instance;
	}

	public function after_setup_theme() {
		/**
		 * Plugins
		 */
		$labels = array(
			'name'               => _x( 'My Plugins', 'post type general name', 'my-plugins' ),
			'singular_name'      => _x( 'Plugin', 'post type singular name', 'my-plugins' ),
			'add_new'            => _x( 'Add New', 'post', 'my-plugins' ),
			'add_new_item'       => __( 'Add New Plugin', 'my-plugins' ),
			'edit_item'          => __( 'Edit Plugin', 'my-plugins' ),
			'new_item'           => __( 'New Plugin', 'my-plugins' ),
			'view_item'          => __( 'View Plugin', 'my-plugins' ),
			'search_items'       => __( 'Search Plugins', 'my-plugins' ),
			'not_found'          => __( 'No plugins found.', 'my-plugins' ),
			'not_found_in_trash' => __( 'No plugins found in Trash.', 'my-plugins' ),
			'all_items'          => __( 'All Plugins', 'my-plugins' ),
		);
		$args = array(
			'labels'          => $labels,
			'description'     => __( 'Plugins', 'my-plugins' ),
			'has_archive'     => 'wordpress-plugins',
			'rewrite'         => array(
				'feeds'	=> true,
				'slug'  => 'wordpress-plugin',
			),
			'public'          => true,
			'supports'        => array(
				'thumbnail',
				'excerpt',
				'custom-fields',
				'page-attributes',
				'revisions',
				'title',
				'editor'
			),
			'show_in_nav_menus' => true,
			'show_in_rest' => true,
		);
		register_post_type( 'plugin', $args );
	}

	public function init() {
		// register meta to store plugin slug
		register_post_meta( 'plugin', 'plugin_slug', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		) );

		// register meta to store plugin github URL
		register_post_meta( 'plugin', 'plugin_github_url', array(
			'show_in_rest' => true,
			'single' => true,
			'type' => 'string',
		) );

		// @TODO register meta to store plugin info

		// Register script for editor sidebar
		wp_register_script(
			'editor-sidebar-js',
			plugins_url( 'editor-sidebar.js', __FILE__ ),
			array(
				'wp-plugins',
				'wp-edit-post',
				'wp-element',
				'wp-components',
				'wp-data',
			)
		);
		// Register style for editor sidebar
		wp_register_style(
			'editor-sidebar-css',
			plugins_url( 'editor-sidebar.css', __FILE__ )
		);
	}

	public function sidebar_script_enqueue() {
		wp_enqueue_script( 'editor-sidebar-js' );
	}

	public function sidebar_style_enqueue() {
		wp_enqueue_style( 'editor-sidebar-css' );
	}

	public function admin_menu() {
		add_meta_box(
			'plugininfo',
			__( 'Plugin Info', 'plugin_info' ),
			array( $this, 'meta_box' ),
			'plugin',
			'side'
		);


		$my_plugins_sort_plugins = add_submenu_page( 'edit.php?post_type=plugin', __( 'Sort Plugins', 'my-plugins' ), __( 'Sort Plugins', 'my-plugins' ), 'edit_posts', 'sort-plugins', array( $this, 'sort' ) );

		add_action( 'admin_print_styles-' . $my_plugins_sort_plugins,  array( $this, 'print_sort_styles'  ) );
		add_action( 'admin_print_scripts-' . $my_plugins_sort_plugins, array( $this, 'print_sort_scripts' ) );
	}

	public function meta_box( $post ) {
		?>
		<label for="plugin_info"><?php _e( 'Plugin slug:', 'my-plugins' ); ?></label>
		<input type="text" name="plugin_info" id="plugin_info" value="<?php esc_attr_e( get_post_meta( $post->ID, 'plugin', true ) ); ?>" />
		<?php
	}

	public function print_sort_styles() {
		wp_enqueue_style( 'nav-menu' );
	}

	public function print_sort_scripts() {
		wp_enqueue_script( 'my-plugins_sort', plugin_dir_url( __FILE__ ) . '/js/sort.js', array( 'jquery-ui-sortable' ), '20140607', true );
	}

	public function sort() {
		if ( empty( $_GET['post_type'] ) || ! in_array( $_GET['post_type'], get_post_types() ) )
			wp_die( __( 'Cheatin&#8217; uh?' ) );
		//get_post_types()
		$args = array(
			'post_type' => $_GET['post_type'],
			'nopaging'  => true,
			'orderby'   => 'menu_order',
			'order'     => 'ASC',
		);
		$posts = new WP_Query( $args );
		?>
        <div class="wrap">
            <div id="icon-tools" class="icon32"><br /></div>
            <h2><?php _e('Sort items', 'my-plugins'); ?></h2>
            <p><?php _e('Click, drag, re-order. Repeat as neccessary. Item at the top will appear first.', 'my-plugins'); ?></p>

            <ul id="sort_list">
				<?php while( $posts->have_posts() ) : $posts->the_post(); ?>
					<?php if( get_post_status() == 'publish' ) { ?>
                        <li id="<?php the_id(); ?>" class="menu-item">
                            <dl class="menu-item-bar">
                                <dt class="menu-item-handle">
                                    <span class="menu-item-title"><?php the_title(); ?></span>
                                </dt>
                            </dl>
                            <ul class="menu-item-transport"></ul>
                        </li>
					<?php } ?>
				<?php endwhile; ?>
				<?php wp_reset_postdata(); ?>
            </ul>
        </div>
		<?php
	}

	public function wp_ajax_sort() {
		global $wpdb;

		$order = explode( ',', $_POST['order'] );
		$counter = 0;

		foreach( $order as $pid ) {
			$wpdb->update( $wpdb->posts, array( 'menu_order' => $counter ), array( 'ID' => $pid ) );
			$counter++;
		}
		die(1);
	}

	public function store_plugin_last_updated( $meta_id, $object_id, $meta_key, $meta_value ) {
		if ( 'plugin-info' == $meta_key )
			update_post_meta( $object_id, 'plugin-last-updated', $meta_value['updated_raw'] );
	}

	public function order_plugins ( $query ) {
		if ( ! is_admin() && $query->is_main_query() && is_post_type_archive( array( 'plugin' ) ) ) {
			$query->set( 'nopaging', true );
			//$query->set( 'meta_key', 'plugin-last-updated' );
			//$query->set( 'orderby', 'meta_value' );
			$query->set( 'orderby', 'menu_order' );
			$query->set( 'order', 'ASC' );
		}
	}

	public function highlight_syntax( $info ) {
		global $SyntaxHighlighter;
		if ( is_a($SyntaxHighlighter, 'SyntaxHighlighter') ) {
			$codeSearch = '|<pre><code(.*)>(.*)</code></pre>|Ums';
			$info = preg_replace_callback($codeSearch, array( $this, 'code_shortcode' ), $info);
			$info = $SyntaxHighlighter->parse_shortcodes($info);
		}

		return $info;
	}

	public function code_shortcode( $matches ) {
		if ( preg_match('/\s*lang[\'"](.*)[\'"]/U', $matches[1], $langMatches ) )
			$lang = $langMatches[1];
		else
			$lang = 'php';
		return "[code lang='{$lang}']" . html_entity_decode($matches[2], ENT_QUOTES) . '[/code]';
	}

	public function plugin_info( $info ) {
		$info['star_rating'] = $this->_get_stars( $info['rating_raw'] );
		return $info;
	}

	public function save_post( $post_ID ) {

		if ( wp_is_post_revision( $post_ID ) or wp_is_post_autosave( $post_ID ) )
			return;

		if ( empty( $_POST['plugin_info'] ) )
			return;

		$plugin = trim( stripslashes( $_POST['plugin_info'] ) );
		$old_plugin = get_post_meta( $post_ID, 'plugin' );

		// If the plugin has changed or there's no thumbnail
		if ( ! get_post_meta( $post_ID, '_thumbnail_id' ) || $plugin != $old_plugin ) {
			// Pull in the header asset as the featured image, trying 2x and falling back to standard
			$url = 'http://ps.w.org/%s/assets/banner-%s.png';
			if ( ! $this->_sideload_image_to_featured_image( sprintf( $url, $info['slug'], '1544×500' ), get_the_ID() ) ) {
				$this->_sideload_image_to_featured_image( sprintf( $url, $info['slug'], '772x250' ), get_the_ID() );
			}
		}
	}

	private function _sideload_image_to_featured_image( $file, $post_id, $desc = null ) {
		if ( ! empty( $file ) ) {
			// Set variables for storage
			// fix file filename for query strings
			preg_match( '/[^\?]+\.(jpe?g|jpe|gif|png)\b/i', $file, $matches );
			$file_array['name'] = basename( $matches[0] );
			// Download file to temp location
			$file_array['tmp_name'] = download_url( $file );

			// If error storing temporarily, return the error.
			if ( is_wp_error( $file_array['tmp_name'] ) ) {
				return $file_array['tmp_name'];
			}

			// do the validation and storage stuff
			$id = media_handle_sideload( $file_array, $post_id, $desc );
			// If error storing permanently, unlink
			if ( is_wp_error( $id ) ) {
				@unlink( $file_array['tmp_name'] );
				return $id;
			}
			update_post_meta( $post_id, '_thumbnail_id', $id );
			return $id;
		}
		return false;
	}

	private function _get_stars( $rating ) {
		return <<<STARS
		<div class="star-holder">
			<svg xmlns="http://www.w3.org/2000/svg" width="1275" height="240" viewBox="0 0 255 48">
				<title>Five Stars</title>
				<defs>
					<style type="text/css"><![CDATA[
						.fill {
							width: {$rating}%;
						}
					]]></style>
					<mask id="star-mask">
						<path fill="white" d="m25,1 6,17h18l-14,11 5,17-15-10-15,10 5-17-14-11h18z"/>
						<path fill="white" d="m76,1 6,17h18l-14,11 5,17-15-10-15,10 5-17-14-11h18z"/>
						<path fill="white" d="m127,1 6,17h18l-14,11 5,17-15-10-15,10 5-17-14-11h18z"/>
						<path fill="white" d="m178,1 6,17h18l-14,11 5,17-15-10-15,10 5-17-14-11h18z"/>
						<path fill="white" d="m229,1 6,17h18l-14,11 5,17-15-10-15,10 5-17-14-11h18z"/>
					</mask>

				</defs>
				<!-- This is the fill. It needs to be behind the content and the top -->
				<rect x="0" y="0" width="0" height="48" rx="1" class="fill" mask="url(#star-mask)"></rect>

				<path class="star" d="m25,1 6,17h18l-14,11 5,17-15-10-15,10 5-17-14-11h18z"/>
				<path class="star" d="m76,1 6,17h18l-14,11 5,17-15-10-15,10 5-17-14-11h18z"/>
				<path class="star" d="m127,1 6,17h18l-14,11 5,17-15-10-15,10 5-17-14-11h18z"/>
				<path class="star" d="m178,1 6,17h18l-14,11 5,17-15-10-15,10 5-17-14-11h18z"/>
				<path class="star" d="m229,1 6,17h18l-14,11 5,17-15-10-15,10 5-17-14-11h18z"/>
			</svg>
		</div>
STARS;
	}

	public function add_meta_boxes_plugin( $post_type ) {
		add_meta_box( 'myplugins-plugin-info', __( 'Additional Plugin Info', 'my-plugins' ), array( $this, 'metabox' ), 'plugin', 'normal', 'high' );
	}

	public function metabox( $post ) {
		foreach ( $this->_additional_plugin_info as $key => $label ) {
			$value = get_post_meta( $post->ID, $key, true );
			?>
            <div class="field field-text">
                <label for="<?php echo esc_attr( $key ) ?>"><?php echo esc_html( $label ) ?></label>
                <input class="widefat" type="text" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ) ?>" id="<?php echo esc_attr( $key ) ?>" />
            </div>
			<?php
		}
	}

	public function save_meta( $id ) {
		foreach ( $this->_additional_plugin_info as $key => $label ) {
			if ( isset( $_POST[$key] ) ) {
				$_POST[$key] = stripslashes( $_POST[$key] );
				update_post_meta( $id, $key, $_POST[$key] );
			} else {
				delete_post_meta( $id, $key );
			}
		}
	}

	public function the_content( $content ) {
		if ( ! is_singular( 'plugin' ) ) {
			return $content;
		}

		$plugin_info = array();
		$plugin_info['slug'] = get_post_meta( get_the_ID(), 'plugin', true );
		$plugin_info['faq'] = get_plugin_info( $plugin_info['slug'], 'faq' );
		$plugin_info['changelog'] = get_plugin_info( $plugin_info['slug'], 'changelog' );
		$plugin_info['installation'] = get_plugin_info( $plugin_info['slug'], 'installation' );
		$plugin_info['description'] = get_plugin_info( $plugin_info['slug'], 'description' );

		foreach ( $plugin_info as &$p_info ) {
			$p_info = do_shortcode( myPlugins::get_instance()->highlight_syntax( $p_info ) );
		}
		$installation_tab = $installation_tab_content = $faq_tab = $faq_tab_content = $changelog_tab = $changelog_tab_content = '';
		if ( !empty( $plugin_info['installation'] ) ) {
			$installation_tab = '<li><a href="#tabs-installation">Installation</a></li>';
			$installation_tab_content = '<div id="tabs-installation" class="ui-tabs-hide">' . $plugin_info['installation'] . '</div>';
		}
		if ( !empty( $plugin_info['faq'] ) ) {
			$faq_tab = '<li><a href="#tabs-faq">FAQ</a></li>';
			$faq_tab_content = '<div id="tabs-faq" class="ui-tabs-hide">' . $plugin_info['faq'] . '</div>';
		}
		if ( !empty( $plugin_info['changelog'] ) ) {
			$changelog_tab = '<li><a href="#tabs-changelog">Changelog</a></li>';
			$changelog_tab_content = '<div id="tabs-changelog" class="ui-tabs-hide">' . $plugin_info['changelog'] . '</div>';
		}

		if ( ! empty( $content ) ) {
			$plugin_info['description'] = $content;
		}

		$plugin_download_url = esc_url( get_plugin_info( $plugin_info['slug'], 'download_url' ) );
		$plugin_link_url = esc_url( get_plugin_info( $plugin_info['slug'], 'link_url' ) );
		$plugin_name = get_plugin_info( $plugin_info['slug'], 'name' );
		$plugin_version = get_plugin_info( $plugin_info['slug'], 'version' );
		$plugin_downloaded = get_plugin_info( $plugin_info['slug'], 'downloaded' );
		$plugin_updated_ago = get_plugin_info( $plugin_info['slug'], 'updated_ago' );
		$plugin_requires = get_plugin_info( $plugin_info['slug'], 'requires' );
		$plugin_tested = get_plugin_info( $plugin_info['slug'], 'tested' );
		$plugin_star_rating = get_plugin_info( $plugin_info['slug'], 'star_rating' );
		$plugin_rating = get_plugin_info( $plugin_info['slug'], 'rating' );
		$plugin_num_ratings = get_plugin_info( $plugin_info['slug'], 'num_ratings' );
		$github_item = '';
		$github_url = get_post_meta( get_the_ID(), 'github_url', true );
		if ( $github_url && ! is_wp_error( $github_url ) ) {
			$github_item = '<li><strong><a href="' . esc_url( $github_url ) . '">Contribute on Github</a></li>';
		}

		$plugin_info['details'] = <<<CONTENT
        <ul>
            <li><strong>Latest Version:</strong> <a href="{$plugin_download_url}">{$plugin_version}</a></li>
            {$github_item}
            <li><strong><a href="{$plugin_link_url}">View on WordPress.org</a></li>
            <li><strong>Downloads:</strong> {$plugin_downloaded}</li>
            <li><strong>Last Updated:</strong> {$plugin_updated_ago}</li>
            <li><strong>Requires WordPress Version:</strong> {$plugin_requires}+</li>
            <li><strong>Tested compatible up to:</strong> {$plugin_tested}</li>
        </ul>
CONTENT;

		$details_tab = '<li><a href="#tabs-details">Details</a></li>';
		$details_tab_content = '<div id="tabs-details" class="ui-tabs-hide">' . $plugin_info['details'] . '</div>';

		$content = <<<CONTENT
	<div class="entry-content">
		<a href="{$plugin_download_url}" class="big button">Download</a>
		<div class="tabbed-content">
			<div class="tabs-wrapper">
				<ul class="tabs">
					<li class="ui-tabs-selected"><a href="#tabs-description">Description</a></li>
					{$installation_tab}
					{$faq_tab}
					{$changelog_tab}
					{$details_tab}
				</ul>
			</div>
		<div id="tabs-description">
			{$plugin_info['description']}
		</div>
		{$installation_tab_content}
		{$faq_tab_content}
		{$changelog_tab_content}
		{$details_tab_content}
	</div><!-- .entry-content -->

CONTENT;

		return $content;
	}

	public function wp_footer() {
		if ( is_singular( 'plugin' ) ) {
			wp_enqueue_script( 'jquery-ui-tabs' );
			?>
            <script>
				jQuery(function($) {
					$( '.tabbed-content' ).tabs();
				});
            </script>
			<?php
		}
	}
}
// Instantiate our class
$myPlugins = myPlugins::get_instance();
