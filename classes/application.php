<?php defined('APP_EPK') or die;
/**
 * 
 */
abstract class Application{
    /**
     * @var Application
     */
    private static $_instance = null;
    /**
     * @var array
     */
    private $_components = array(
        //
    );
    /**
     * 
     */
    protected function __construct() {
        $this->preload();
    }
    /**
     * @return string
     */
    protected static final function __root(){
        return preg_replace('/\\\\/', '/', __DIR__);
    }
    /**
     * @return Application
     */
    protected function preload() {
        foreach($this->_components as $component){
            $path = sprintf('%s/components/%s.php',self::__root(), strtolower( $component ) );
            if(file_exists($path)){
                require_once $path;;
            }
            else{
                throw new Exception(sprintf('invalid component %s',$component));
            }
        }
        return $this;
    }
    /**
     * @param string $component
     * @return Application
     */
    protected function register( $component ) {
        if(!in_array( $component, $this->_components ) ){
            $this->_components[] = $component;
        }
        return $this;
    }
    /**
     * Inicializar la aplicación indicando el cargador requerido
     * 
     * @param string $app Identificador del cargador descrito en la carpeta bootstrap
     * @param array $settings
     * @return Application|NULL Instancia de la aplicación
     * @throws Exception
     */
    public static final function create( $app, array $settings = array( ) ){
        
        if( !defined('APP_ROOT')){
            define('APP_ROOT', '../application');
            define('APP_PROJECTS', '../projects');
            define('APP_REPO', '../repository');
            
            $path = sprintf('%s/%s/%s.php',
                    APP_PROJECTS,
                    strtolower($app),
                    strtolower($app) );

            $class = $app."App";

            if(file_exists($path)){
                require_once($path);
                if(class_exists($class)){
                    self::$_instance = new $class( $settings );
                }
                else{
                    throw new Exception(sprintf('Invalid App Class %s',$class));
                }
            }
            else{
                throw new Exception(sprintf('Invalid App path %s',$path));
            }            
        }
        
        return self::$_instance;
    }
    /**
     * @return Application Instancia de la aplicación
     */
    public static final function instance(){
        return self::$_instance;
    }
}




