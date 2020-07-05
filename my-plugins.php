<?php
/**
 * Plugin Name: myPlugins
 * Plugin URI: http://aarondcampbell.com/
 * Description: Plugin for showing your plugins
 * Version: 2.0.0
 * Author: Aaron D. Campbell
 * Author URI: http://aarondcampbell.com/
 * Text Domain: my-plugins
 */

class myPlugins {
	/**
	 * @var myPlugins - Static property to hold our singleton instance
	 */
	static $instance = false;

	/**
	 * Private for force the singleton
	 */
	private function __construct() {
		add_action( 'admin_init',                           array( $this, 'action_admin_init'                    ) );
		add_action( 'update_plugin_info',                   array( $this, 'update_plugin_info'                   ) );
		add_action( 'after_setup_theme',                    array( $this, 'after_setup_theme'                    ) );
		add_action( 'rest_after_insert_plugin',             array( $this, 'rest_after_insert_plugin'             ) );
		add_action( 'the_content',                          array( $this, 'the_content'                          ) );
		add_action( 'wp_footer',                            array( $this, 'wp_footer'                            ) );
		add_action( 'init',                                 array( $this, 'init'                                 ) );
		add_action( 'enqueue_block_editor_assets',          array( $this, 'sidebar_script_enqueue'               ) );

		// For sorting
		add_action( 'admin_menu',                           array( $this, 'admin_menu'                           ) );
		add_action( 'wp_ajax_sort',                         array( $this, 'wp_ajax_sort'                         ) );
		add_action( 'parse_query',                          array( $this, 'order_plugins'                        ) );
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
		register_post_meta(
			'plugin',
			'_plugin_slug',
			array(
				'show_in_rest' => true,
				'type' => 'string',
				'single' => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback' => array( $this, 'auth_callback' ),
			)
		);

		// register meta to store plugin github URL
		register_post_meta(
			'plugin',
			'_plugin_github_url',
			array(
				'show_in_rest' => true,
				'type' => 'string',
				'single' => true,
				'sanitize_callback' => 'esc_url_raw',
				'auth_callback' => array( $this, 'auth_callback' ),
			)
		);

		// Register script for editor sidebar
		$asset_file = include( plugin_dir_path( __FILE__ ) . 'build/index.asset.php');

		wp_register_script(
			'editor-sidebar-js',
			plugins_url( 'build/index.js', __FILE__ ),
			$asset_file['dependencies'],
			$asset_file['version']
		);
	}

	public function sidebar_script_enqueue() {
		wp_enqueue_script( 'editor-sidebar-js' );
	}

	/**
	 * Determine if the current user can edit posts
	 *
	 * @return bool True when can edit posts, else false.
	 */
	public function auth_callback() {

		return current_user_can( 'edit_posts' );

	}

	public function admin_menu() {
		$my_plugins_sort_plugins = add_submenu_page( 'edit.php?post_type=plugin', __( 'Sort Plugins', 'my-plugins' ), __( 'Sort Plugins', 'my-plugins' ), 'edit_posts', 'sort-plugins', array( $this, 'sort' ) );

		add_action( 'admin_print_styles-' . $my_plugins_sort_plugins,  array( $this, 'print_sort_styles'  ) );
		add_action( 'admin_print_scripts-' . $my_plugins_sort_plugins, array( $this, 'print_sort_scripts' ) );
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

	public function order_plugins ( $query ) {
		if ( ! is_admin() && $query->is_main_query() && is_post_type_archive( array( 'plugin' ) ) ) {
			$query->set( 'nopaging', true );
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

	/**
	 * @param WP_Post         $post     Inserted or updated post object.
	 * @param WP_REST_Request $request  Request object.
	 * @param bool            $creating True when creating a post, false when updating.
	 */
	public function rest_after_insert_plugin( $post ) {
		$plugin_info = $this->get_plugin_info( get_registered_metadata( 'post', $post->ID, '_plugin_slug' ) );
		if ( ! is_wp_error( $plugin_info ) ) {
			update_post_meta( $post->ID, '_plugin_info', $plugin_info );
			if ( $plugin_info->banners && is_array( $plugin_info->banners ) ) {
				$banner = empty( $plugin_info->banners['high'] )? $plugin_info->banners['low'] : $plugin_info->banners['high'];
			}
			$this->_sideload_image_to_featured_image( $banner, $post->ID );
		}
	}

	public function get_plugin_info( $slug = null ) {
		if ( empty( $slug ) )
			return false;

		admin_url();

		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

		$slug = sanitize_title( $slug );
		return plugins_api( 'plugin_information', array( 'slug' => $slug ) );
	}

	public function action_admin_init() {
		if ( ! wp_next_scheduled( 'update_plugin_info' ) ) {
			// @TODO should this be hourly?
			wp_schedule_event( time(), 'hourly', 'update_plugin_info' );
		}
	}

	public function update_plugin_info() {
		$q = new WP_Query;

		$posts = $q->query( array(
			'posts_per_page' => -1,
			'meta_key'       => '_plugin_slug',
			'post_type'      => 'plugin'
		) );

		if ( ! count( $posts ) )
			return;

		foreach ( $posts as $post ) {
			$plugin_info = $this->get_plugin_info( get_registered_metadata( 'post', $post->ID, '_plugin_slug' ) );
			if ( ! is_wp_error( $plugin_info ) ) {
				update_post_meta( $post->ID, '_plugin_info', $plugin_info );
			}
		}
	}

	private function _sideload_image_to_featured_image( $file, $post_id, $desc = null ) {
		// Has download_url() which is used by media_sideload_image()
		require_once ABSPATH . 'wp-admin/includes/file.php';
		// Has wp_read_image_metadata() which is used by media_sideload_image()
		require_once ABSPATH . 'wp-admin/includes/image.php';
		// Has media_sideload_image()
		require_once ABSPATH . 'wp-admin/includes/media.php';
		$image = media_sideload_image( $file, $post_id, $desc, 'id' );

		if ( is_wp_error( $image ) ) {
			return;
		}

		return set_post_thumbnail( $post_id, $image );
	}

	private function _get_stars( $rating ) {
		$num_stars = round( 5 * $rating / 100, 1 );
		$stars_title = sprintf( _n( '%s star', '%s stars', $num_stars, 'my-plugins' ), $num_stars );
		return <<<STARS
		<div class="star-holder">
			<svg
				xmlns:svg="http://www.w3.org/2000/svg"
				xmlns="http://www.w3.org/2000/svg"
				version="1.1"
				height="240"
				width="1275"
				viewBox="0 0 255 48">
				<title>{$stars_title}</title>
				<defs>
					<style>
					.fill {
						width: {$rating}%;
					}
					</style>
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

	public function the_content( $content ) {
		if ( ! is_singular( 'plugin' ) ) {
			return $content;
		}
		$plugin_info = get_post_meta( get_the_ID(), '_plugin_info', true );
		if ( ! $plugin_info ) {
			$plugin_info = $this->get_plugin_info( get_registered_metadata( 'post', get_the_ID(), '_plugin_slug' ) );
			if ( ! $plugin_info || is_wp_error( $plugin_info ) ) {
				return $content;
			}
		}
		if ( ! empty( $content ) ) {
			$plugin_info->sections['description'] = $content;
		}
		$installation_tab = $installation_tab_content = $faq_tab = $faq_tab_content = $changelog_tab = $changelog_tab_content = $details_tab = $details_tab_content = '';

		if ( ! empty( $plugin_info->sections['installation'] ) ) {
			$installation_tab = '<li><a href="#tabs-installation">Installation</a></li>';
			$installation_tab_content = '<div id="tabs-installation" class="ui-tabs-hide">' . $plugin_info->sections['installation'] . '</div>';
		}
		if ( !empty( $plugin_info->sections['faq'] ) ) {
			$faq_tab = '<li><a href="#tabs-faq">FAQ</a></li>';
			$faq_tab_content = '<div id="tabs-faq" class="ui-tabs-hide">' . $plugin_info->sections['faq'] . '</div>';
		}
		if ( !empty( $plugin_info->sections['changelog'] ) ) {
			$changelog_tab = '<li><a href="#tabs-changelog">Changelog</a></li>';
			$changelog_tab_content = '<div id="tabs-changelog" class="ui-tabs-hide">' . $plugin_info->sections['changelog'] . '</div>';
		}

		$github_item = '';
		$github_url = get_registered_metadata( 'post', get_the_ID(), '_plugin_github_url' );
		if ( $github_url && ! is_wp_error( $github_url ) ) {
			$github_item = '<li><strong><a href="' . esc_url( $github_url ) . '">Contribute on Github</a></strong></li>';
		}
		$plugin_info->plugin_link_url = esc_html( "https://wordpress.org/plugins/{$plugin_info->slug}/" );
		$plugin_info->last_updated_ago = esc_html(  sprintf( __( '%s ago', 'my-plugins' ), human_time_diff( strtotime( $plugin_info->last_updated ) ) ) );

		$plugin_info->details = <<<CONTENT
        <ul>
            <li><strong>Latest Version:</strong> <a href="{$plugin_info->download_link}">{$plugin_info->version}</a></li>
            {$github_item}
            <li><strong><a href="{$plugin_info->plugin_link_url}">View on WordPress.org</a></strong></li>
            <li><strong>Active Installs:</strong> {$plugin_info->active_installs}</li>
            <li><strong>Last Updated:</strong> {$plugin_info->last_updated_ago}</li>
            <li><strong>Requires WordPress Version:</strong> {$plugin_info->requires}+</li>
            <li><strong>Tested compatible up to:</strong> {$plugin_info->tested}</li>
            <li><strong>Rating:</strong> {$this->_get_stars( $plugin_info->rating )}</li>
        </ul>
CONTENT;

		$details_tab = '<li><a href="#tabs-details">Details</a></li>';
		$details_tab_content = '<div id="tabs-details" class="ui-tabs-hide">' . $plugin_info->details . '</div>';

		$content = <<<CONTENT
	<div class="entry-content">
		<a href="{$plugin_info->download_link}" class="big button">Download</a>
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
			{$plugin_info->sections['description']}
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
