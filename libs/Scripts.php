<?php

if ( !defined('ABSPATH') ){ die(); } //Exit if accessed directly

if ( !trait_exists('Scripts') ){
	trait Scripts {
		public function hooks(){
			//Register styles/scripts
			add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
			add_action('login_enqueue_scripts', array($this, 'register_scripts'));
			add_action('admin_enqueue_scripts', array($this, 'register_scripts'));

			//Enqueue styles/scripts
			add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
			add_action('login_enqueue_scripts', array($this, 'login_enqueue_scripts'));
			add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

			add_action('wp_enqueue_scripts', array($this, 'font_awesome_config'));

			if ( $this->is_debug() || !empty($GLOBALS['wp_customize']) ){
				add_filter('style_loader_src', array($this, 'add_debug_query_arg'), 500, 1);
				add_filter('script_loader_src', array($this, 'add_debug_query_arg'), 500, 1);
			}

			add_action('wp_head', array($this, 'output_nebula_data'));
			add_action('admin_head', array($this, 'output_nebula_data'));
		}

		//Register scripts
		public function register_scripts(){
			//Stylesheets
			//wp_register_style($handle, $src, $dependencies, $version, $media);
			wp_register_style('nebula-mmenu', 'https://cdnjs.cloudflare.com/ajax/libs/jQuery.mmenu/7.0.3/jquery.mmenu.all.css', null, '7.0.3', 'all');
			wp_register_style('nebula-main', get_template_directory_uri() . '/style.css', array('nebula-bootstrap'), $this->version('full'), 'all');
			wp_register_style('nebula-login', get_template_directory_uri() . '/assets/css/login.css', null, $this->version('full'), 'all');
			wp_register_style('nebula-admin', get_template_directory_uri() . '/assets/css/admin.css', null, $this->version('full'), 'all');
			if ( $this->get_option('google_font_url') ){
				wp_register_style('nebula-google_font', $this->get_option('google_font_url'), array(), null, 'all');
			}
			$this->bootstrap('css');
			wp_register_style('nebula-datatables', 'https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.16/css/jquery.dataTables.min.css', null, '1.10.16', 'all');
			wp_register_style('nebula-chosen', 'https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.3/chosen.min.css', null, '1.8.3', 'all');
			wp_register_style('nebula-jquery_ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.structure.min.css', null, '1.12.1', 'all');
			wp_register_style('nebula-pre', get_template_directory_uri() . '/assets/css/pre.css', null, $this->version('full'), 'all');
			wp_register_style('nebula-flags', get_template_directory_uri() . '/assets/css/flags.css', null, $this->version('full'), 'all');

			//Scripts
			//Use CDNJS to pull common libraries: http://cdnjs.com/
			//nebula_register_script($handle, $src, $exec, $dependencies, $version, $in_footer);
			$this->jquery();
			$this->bootstrap('js');
			$this->register_script('nebula-font_awesome', get_template_directory_uri() . '/assets/js/vendor/fontawesome-all.min.js', 'async', null, '5.0.7', true); //Font Awesome 5 JS SVG method
			$this->register_script('nebula-modernizr_dev', get_template_directory_uri() . '/assets/js/vendor/modernizr.dev.js', 'defer', null, '3.5.0', false);
			$this->register_script('nebula-modernizr_local', get_template_directory_uri() . '/assets/js/vendor/modernizr.min.js', 'defer', null, '3.3.1', false);
			$this->register_script('nebula-modernizr', 'https://cdnjs.cloudflare.com/ajax/libs/modernizr/2.8.3/modernizr.min.js', 'defer', null, '2.8.3', false); //https://github.com/cdnjs/cdnjs/issues/6100
			$this->register_script('nebula-jquery_ui', 'https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js', 'defer', null, '1.12.1', true);
			$this->register_script('nebula-mmenu', 'https://cdnjs.cloudflare.com/ajax/libs/jQuery.mmenu/7.0.3/jquery.mmenu.all.js', 'defer', null, '7.0.3', true);
			$this->register_script('nebula-vimeo', 'https://player.vimeo.com/api/player.js', null, null, null, true);
			$this->register_script('nebula-datatables', 'https://cdnjs.cloudflare.com/ajax/libs/datatables/1.10.16/js/jquery.dataTables.min.js', 'defer', null, '1.10.16', true);
			$this->register_script('nebula-chosen', 'https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.3/chosen.jquery.min.js', 'defer', null, '1.8.3', true);
			$this->register_script('nebula-autotrack', 'https://cdnjs.cloudflare.com/ajax/libs/autotrack/2.4.1/autotrack.js', 'async', null, '2.4.1', true);
			$this->register_script('nebula-nebula', get_template_directory_uri() . '/assets/js/nebula.js', 'defer', array('nebula-bootstrap', 'jquery-core'), $this->version('full'), true);
			$this->register_script('nebula-login', get_template_directory_uri() . '/assets/js/login.js', null, array('jquery-core'), $this->version('full'), true);
			$this->register_script('nebula-admin', get_template_directory_uri() . '/assets/js/admin.js', 'defer', array('jquery-core'), $this->version('full'), true);
		}

		//Build Nebula data object and output to the <head>
		public function output_nebula_data(){
			global $wp_scripts, $wp_styles, $upload_dir;
			$upload_dir = wp_upload_dir();

			//Prep CSS/JS resources for JS object
			$nebula_assets = array(
				'styles' => array(),
				'scripts' => array()
			);

			$lazy_assets = $this->lazy_load_assets();

			foreach ( $wp_styles->registered as $handle => $data ){
				if ( (strpos($handle, 'nebula-') !== false && strpos($handle, 'admin') === false && strpos($handle, 'login') === false) || array_key_exists($handle, $lazy_assets['styles']) ){ //If the handle contains "nebula-" but not "admin" or "login" -or- if the asset is prepped for lazy-loading
					$nebula_assets['styles'][str_replace('-', '_', $handle)] = str_replace(array('#defer', '#async'), '', $data->src);
				}
			}
			foreach ( $wp_scripts->registered as $handle => $data ){
				if ( (strpos($handle, 'nebula-') !== false && strpos($handle, 'admin') === false && strpos($handle, 'login') === false) || array_key_exists($handle, $lazy_assets['scripts']) ){ //If the handle contains "nebula-" but not "admin" or "login" -or- if the asset is prepped for lazy-loading
					$nebula_assets['scripts'][str_replace('-', '_', $handle)] = str_replace(array('#defer', '#async'), '', $data->src);
				}
			}

			//Be careful changing the following array as many JS functions use this data!
			$brain = array(
				'version' => array(
					'number' => $this->version('full'),
					'date' => $this->version('date')
				),
				'site' => array(
					'name' => get_bloginfo('name'),
					'charset' => get_bloginfo('charset'),
					'directory' => array(
						'root' => get_site_url(),
						'template' => array(
							'path' => get_template_directory(),
							'uri' => get_template_directory_uri(),
						),
						'stylesheet' => array(
							'path' => get_stylesheet_directory(),
							'uri' => get_stylesheet_directory_uri(),
						),
					),
					'home_url' => home_url(),
					'sw_url' => $this->sw_location(),
					'cache' => $this->get_sw_cache_name(),
					'domain' => $this->url_components('domain'),
					'protocol' => $this->url_components('protocol'),
					'language' => get_bloginfo('language'),
					'ajax' => array(
						'url' => admin_url('admin-ajax.php'),
						'nonce' => wp_create_nonce('nebula_ajax_nonce'),
					),
					'upload_dir' => $upload_dir['baseurl'],
					'ecommerce' => false,
					'options' => array(
						'sw' => $this->get_option('service_worker'),
						'gaid' => $this->get_option('ga_tracking_id'),
						'nebula_cse_id' => $this->get_option('cse_id'),
						'nebula_google_browser_api_key' => $this->get_option('google_browser_api_key'),
						'facebook_url' => $this->get_option('facebook_url'),
						'facebook_app_id' => $this->get_option('facebook_app_id'),
						'twitter_url' => $this->get_option('twitter_url'),
						'google_plus_url' => $this->get_option('google_plus_url'),
						'linkedin_url' => $this->get_option('linkedin_url'),
						'youtube_url' => $this->get_option('youtube_url'),
						'instagram_url' => $this->get_option('instagram_url'),
						'adblock_detect' => $this->get_option('adblock_detect'),
						'manage_options' => current_user_can('manage_options'),
						'debug' => $this->is_debug(),
						'sidebar_expanders' => get_theme_mod('sidebar_accordion_expanders', true),
					),
					'resources' => array(
						'styles' => $nebula_assets['styles'],
						'scripts' => $nebula_assets['scripts'],
						'lazy' => $lazy_assets,
					),
					'ecommerce' => false,
				),
				'post' => ( is_search() )? null : array( //Conditional prevents wrong ID being used on search results
					'id' => get_the_id(),
					'permalink' => get_the_permalink(),
					'title' => urlencode(get_the_title()),
					'excerpt' => $this->excerpt(array('words' => 100, 'more' => '', 'ellipsis' => false, 'strip_tags' => true)),
					'author' => urlencode(get_the_author()),
					'year' => get_the_date('Y'),
					'categories' => $this->post_categories(array('string' => true)),
					'tags' => $this->post_tags(array('string' => true)),
				),
				'dom' => null,
			);

			//Check for session data
			if ( isset($_SESSION['nebulaSession']) && json_decode($_SESSION['nebulaSession'], true) ){ //If session exists and is valid JSON
				$brain['session'] = json_decode($_SESSION['nebulaSession'], true); //Replace nebula.session with session data
			} else {
				$brain['session'] = array(
					'ip' => $this->get_ip_address(),
					'id' => $this->nebula_session_id(),
					'flags' => array(
						'adblock' => false,
						'gablock' => false,
					),
					'geolocation' => false
				);
			}

			//User Data
			$brain['user'] = array(
				'id' => get_current_user_id(),
				'name' => array(
					'first' => $this->get_user_info('first_name'),
					'last' => $this->get_user_info('last_name'),
					'full' => $this->get_user_info('display_name'),
				),
				'email' => $this->get_user_info('user_email'),
				'ip' => $this->get_ip_address(),
				'cid' => $this->ga_parse_cookie(),
				'client' => array( //Client data is here inside user because the cookie is not transferred between clients.
					'bot' => $this->is_bot(),
					'remote_addr' => $this->get_ip_address(),
					'device' => array(
						'full' => $this->get_device('full'),
						'formfactor' => $this->get_device('formfactor'),
						'brand' => $this->get_device('brand'),
						'model' => $this->get_device('model'),
						'type' => $this->get_device('type'),
					),
					'os' => array(
						'full' => $this->get_os('full'),
						'name' => $this->get_os('name'),
						'version' => $this->get_os('version'),
					),
					'browser' => array(
						'full' => $this->get_browser('full'),
						'name' => $this->get_browser('name'),
						'version' => $this->get_browser('version'),
						'engine' => $this->get_browser('engine'),
						'type' => $this->get_browser('type'),
					),
				),
				'address' => false,
				'facebook' => false,
				'flags' => array(
					'fbconnect' => false,
				),
			);

			$brain = apply_filters('nebula_brain', $brain); //Allow other functions to hook in to add/modify data
			$brain['user']['known'] = ( !empty($brain['user']['email']) )? true : false;

			echo '<script type="text/javascript">var nebula = ' . json_encode($brain) . '</script>'; //Output the data to <head>
		}

		//Enqueue frontend scripts
		function enqueue_scripts($hook){
			//Stylesheets
			wp_enqueue_style('nebula-bootstrap');
			wp_enqueue_style('nebula-main');

			if ( !is_customize_preview() ){ //@todo "Nebula" 0: Remove this when Font Awesome 5 JS works with the Customizer (currently causing an infinite loop which soft-locks the tab)
				wp_enqueue_script('nebula-font_awesome'); //Font Awesome 5 JS SVG method
			}

			if ( $this->get_option('google_font_url') ){
				wp_enqueue_style('nebula-google_font');
			}

			//Scripts
			wp_enqueue_script('jquery-core');

			if ( $this->get_option('device_detection') ){
				//wp_enqueue_script('nebula-modernizr_dev');
				//wp_enqueue_script('nebula-modernizr_local'); //@todo "Nebula" 0: Switch this back to CDN when version 3 is on CDNJS
			}

			wp_enqueue_script('nebula-bootstrap');

			if ( $this->is_analytics_allowed() && $this->get_option('ga_tracking_id') ){
				wp_enqueue_script('nebula-autotrack');
			}

			wp_enqueue_script('nebula-nebula');

			//Conditionals
			if ( $this->is_debug() ){ //When ?debug query string is used
				wp_enqueue_script('nebula-performance_timing');
			}

			if ( is_page_template('tpl-search.php') ){ //Form pages (that use selects) or Advanced Search Template. The Chosen library is also dynamically loaded in nebula.js.
				wp_enqueue_style('nebula-chosen');
				wp_enqueue_script('nebula-chosen');
			}
		}

		//Enqueue login scripts
		function login_enqueue_scripts($hook){
			//Stylesheets
			wp_enqueue_style('nebula-login');
			echo '<style>
				div#login h1 a {background: url(' . get_theme_file_uri('/assets/img/logo.png') . ') center center no-repeat; width: auto; background-size: contain;}
					.svg div#login h1 a {background: url(' . get_theme_file_uri('/assets/img/logo.svg') . ') center center no-repeat; background-size: contain;}
			</style>';

			//Scripts
			wp_enqueue_script('jquery-core');
			wp_enqueue_script('nebula-login');
		}

		//Enqueue admin scripts
		function admin_enqueue_scripts($hook){
			$current_screen = get_current_screen();

			//Stylesheets
			wp_enqueue_style('nebula-admin');

			if ( !is_customize_preview() ){ //@todo "Nebula" 0: Remove this when Font Awesome 5 JS works with the Customizer (currently causing an infinite loop which soft-locks the tab)
				wp_enqueue_script('nebula-font_awesome'); //Font Awesome 5 JS SVG method
			}

			if ( $this->ip_location() ){
				wp_enqueue_style('nebula-flags');
			}

			//Scripts
			//wp_enqueue_script('jquery-core'); //May need to uncomment for Bootstrap Beta 3
			wp_enqueue_script('nebula-admin');

			//Nebula Options page
			$current_screen = get_current_screen();
			if ( $current_screen->base === 'appearance_page_nebula_options' || $current_screen->base === 'options' ){
				//$this->append_dependency('nebula-admin', 'nebula-bootstrap');
				wp_enqueue_style('nebula-bootstrap');
				wp_enqueue_script('nebula-bootstrap');
			}

			//User Profile edit page
			if ( $current_screen->base === 'profile' ){
				wp_enqueue_style('thickbox');
				wp_enqueue_script('thickbox');
				wp_enqueue_script('media-upload');
			}
		}

		//Add $dep (script handle) to the array of dependencies for $handle
		public function append_dependency($handle, $dep){
			global $wp_scripts;

			$script = $wp_scripts->query($handle, 'registered');
			if ( !$script ){
				return false;
			}

			if ( !in_array($dep, $script->deps) ){
				$script->deps[] = $dep;
			}

			return true;
		}

		//Get fresh resources when debugging
		public function add_debug_query_arg($src){
			return add_query_arg('debug', str_replace('.', '', $this->version('raw')) . '-' . rand(1000, 9999), $src);
		}

		//Prep Font Awesome JavaScript implementation configuration before calling the script itself: https://fontawesome.com/how-to-use/font-awesome-api#configuration
		public function font_awesome_config(){
		    $font_awesome_config = '<script type="text/javascript">window.FontAwesomeConfig = {searchPseudoElements: true,'; //Replace :before and :after with <svg> icons too

			if ( $this->is_debug() ){
				$font_awesome_config .= 'measurePerformance: true,'; //Add markers in the Performance section of developer tools
			}

			$font_awesome_config = rtrim($font_awesome_config, ',');
			$font_awesome_config .= '}</script>';
			echo $font_awesome_config;
		}
	}
}