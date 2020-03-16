<?php
/**
 * Plugin Name: Wordpress 2-Step Verification
 * Plugin URI: http://as247.vui30.com/blog/wordpress-2-step-verification/
 * Description: Wordpress 2-Step Verification adds an extra layer of security to your Wordpress Account. In addition to your username and password, you'll enter a code that generated by Android/iPhone/Blackberry app or Plugin will send you via email upon signing in.
 * Author: as247
 * Version: 2.3.0
 * Author URI: http://as247.vui360.com/
 * Compatibility: WordPress 4.0
 * Text Domain: wordpress-2-step-verification
 * Domain Path: /languages
 * License: GPLv2 or later
 * Requires PHP: 5.4.0
 * Network: True
 */
if (!defined('PHP_INT_MAX')) {
    define('PHP_INT_MAX', 2147483647);
}
if (!defined('PHP_INT_MIN')) {
    define('PHP_INT_MIN', ~PHP_INT_MAX);
}
define('WP2SV_ABSPATH', dirname(__FILE__) . '/');
define('WP2SV_INC', WP2SV_ABSPATH . 'includes');
define('WP2SV_TEMPLATE', WP2SV_ABSPATH . 'template');

require_once(WP2SV_INC . '/helpers.php');

class Wordpress2StepVerification
{
    protected $setup = false;
    /**
     * @var WP_User
     */
    protected $user;
    protected static $instance;
    private $modules = array(
        'otp' => 'Wp2sv_OTP',
        'auth' => 'Wp2sv_Auth',
        'recovery' => 'Wp2sv_Recovery',
        'app_pass' => 'Wp2sv_AppPass',
        'handler' => 'Wp2sv_Handler',
        'admin' => 'Wp2sv_Admin',
        'setup' => 'Wp2sv_Setup',
        'backup_code' => 'Wp2sv_Backup_Code',
        'email' => 'Wp2sv_Email',
    );
    private $container = [];
    protected $ready=false;

    function __construct()
    {
        try {
            spl_autoload_register([$this, 'autoload']);
        }catch (Exception $e){

        }
        $this->registerModules();
        load_plugin_textdomain('wordpress-2-step-verification', FALSE, basename(dirname(__FILE__)) . '/languages/');
        add_action('setup_theme', [$this, 'initialize']);

        if(wp2sv_is_strict_mode()){
            /**
             * Strict mode handle 2-step in init hook, it's a little bit later than set_current_user but ensure that it always handled correctly
             */
            add_action('after_setup_theme',[$this,'setReady']);
            add_action('init', [$this, 'setup'], PHP_INT_MIN);
            add_action('init', [$this, 'setup'], 0);//in case negative priority isn't supported
            add_action('init', [$this, 'setup'], 1);//in case 0 priority isn't supported too
        }else{
            /***
             * Clear current user at last, then mark ready
             */
            add_action('after_setup_theme',[$this,'cleanup'],PHP_INT_MAX);
            add_action('set_current_user', [$this, 'setup'], PHP_INT_MIN);
            add_action('set_current_user', [$this, 'setup'], 0);
        }
        add_action('admin_enqueue_scripts', [$this, 'registerScripts'], 9);
		add_action('wp_enqueue_scripts', [$this, 'registerScripts'], 9);
    }


    function registerModules()
    {
        $this->bind('app_pass', function () {
            return new Wp2sv_AppPass($this->user()->ID);
        });
    }

    function initialize()
    {
        if(is_admin()) {//run update when in wp-admin area
            (new Wp2sv_Update())->run();
        }
        $this->getHandler();
        $this->make(Wp2sv_Woo::class);
    }
    function setReady(){
        $this->ready=true;
    }
    function cleanup(){
        global $current_user;
        $current_user=null;
        $this->ready=true;
    }


    function isSetCurrentUserCalledByWp(){
        $callers=(wp_debug_backtrace_summary(__CLASS__,0,false));
        $wpInitCaller='';
        foreach ($callers as $index=>$function){
            if($function==='WP->init'){
                $wpInitCaller=isset($callers[$index+1])?$callers[$index+1]:false;
                break;
            }
        }
        if(strpos($wpInitCaller,'wp-settings.php')){
            return true;
        }
        return false;
    }
    function isReady(){
        if (!empty($this->user)) {//already setup?
            return false;
        }
        if (!$this->ready) {//not did after setup theme
            return false;
        }
        if(!wp2sv_is_strict_mode()){//handle by set_current_user but did init
            if(did_action('init')){
                return false;
            }
            if(!$this->isSetCurrentUserCalledByWp()){//check set_current_user caller
                return false;
            }
        }
        return true;
    }
    function setup()
    {
        if(!$this->isReady()){
            return ;
        }
        $user = $this->get_current_user();
        if ($user instanceof WP_User) {
            if ($user->ID) {
                $this->user = $user;
                $this->set('model', new Wp2sv_Model($this->user));
                $this->getHandler()->run();
                $this->getSetup()->run();
                if (is_admin()) {
                    $this->getAdmin()->run();
                }

                do_action('wp2sv_init');
                do_action('wp2sv_handle');
            }
        }
    }




    function registerScripts()
    {
        wp_register_script('vue', wp2sv_public('vendor/vue/vue.js'), array(), '2.5.1', true);
        wp_register_script('vue-resource', wp2sv_public('vendor/vue-resource/vue-resource.min.js'), ['vue'], '1.3.4', true);
        wp_register_script('wp2sv', wp2sv_assets('/js/wp2sv.js'), array('backbone', 'vue'), '1.1', true);
        wp_register_style('wp2sv_base', wp2sv_assets('/css/base.css'));
		wp_register_style('wp2sv_admin', wp2sv_assets('/css/admin.css'));
        $wp2sv = [
        	'ajaxurl'=>admin_url( 'admin-ajax.php' ),
            'l10n' => [
                'ajax_fail' => __('Network error, please try again', 'wordpress-2-step-verification'),
            ],
            'url' => [
                'root' => wp2sv_url(''),
                'public' => wp2sv_public(),
                'assets' => wp2sv_assets(''),
            ],
            '_nonce' => wp_create_nonce('wp2sv'),
        ];
        wp_localize_script('wp2sv', 'wp2sv', $wp2sv);

    }

    public static function instance()
    {
        if (!static::$instance) {
            static::$instance = new static();
        }
        return static::$instance;
    }
    function set($name, $value)
    {
        $this->container[$name] = $value;
        return $this;
    }

    protected function get_current_user()
    {
        global $current_user;
        return $current_user;
    }

    function user()
    {
        return $this->user;
    }

    /**
     * @return Wp2sv_Model
     */
    function model()
    {
        return $this->make('model');
    }

    /**
     * @return Wp2sv_Setup
     */
    function getSetup()
    {
        return $this->make('setup');
    }

    /**
     * @return Wp2sv_Admin
     */
    function getAdmin()
    {
        return $this->make('admin');
    }

    /**
     * @return Wp2sv_Handler
     */
    function getHandler()
    {
        return $this->make('handler');
    }

    /**
     * @return Wp2sv_OTP
     */
    function getOtp()
    {
        return $this->make('otp');
    }

    /**
     * @return Wp2sv_Auth
     */
    function getAuth()
    {
        return $this->make('auth');
    }

    /**
     * @return Wp2sv_Recovery
     */
    function getRecovery()
    {
        return $this->make('recovery');
    }

    /**
     * @return Wp2sv_AppPass
     */
    function getAppPassword()
    {
        return $this->make('app_pass');
    }

    function get($name)
    {
        return $this->make($name);
    }

    function make($name)
    {
        $class = $name;
        if (isset($this->modules[$name])) {
            $class = $this->modules[$name];
        }
        if (!isset($this->container[$name])) {
            if ($class instanceof Closure) {
                $this->container[$name] = $class($this);
            } else {
                $this->container[$name] = new $class($this);
            }
        }
        return $this->container[$name];
    }

    function bind($name, $factory)
    {
        $this->modules[$name] = $factory;
        return $this;
    }

    function plugin_url($path = '', $echo = true)
    {
        $url = plugins_url($path, __FILE__);
        if ($echo) {
            echo $url;
            return '';
        } else {
            return $url;
        }
    }

    function url($args = [],$backendUrl=false)
    {
    	if(is_admin() || $backendUrl) {
			$url = menu_page_url('wp2sv', false);
		}else {
			$url = get_permalink(apply_filters('wp2sv_setup_page_id', get_option('wp2sv_setup_page_id')));
		}
		if ($args) {
			$url = add_query_arg($args, $url);
		}
		return $url;
    }

    /**
     * @param $class
     */
    function autoload($class)
    {
        if (false === strpos($class, 'Wp2sv_')) {
            return;
        }
        $files = [
            WP2SV_INC . '/' . $class . '.php',
            WP2SV_INC . '/' . strtolower($class) . '.php'
        ];
        foreach ($files as $file) {
            if (file_exists($file)) {
                /** @noinspection PhpIncludeInspection */
                include_once $file;
                return;
            }
        }
    }
}

class Wp2sv extends Wordpress2StepVerification
{
}

Wp2sv::instance();