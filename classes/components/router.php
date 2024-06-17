<?php
/**
 * 
 */
class Router {
    /**
     * @var array
     */
    private $_routes = [];
    /**
     * 
     * @param type $route
     * @param type $controllerAction
     * @return Router
     */
    public function add($route, $controllerAction) {
        $this->_routes[$route] = $controllerAction;
        return $this; // Return the instance for chaining
    }
    /**
     * @return URL
     */
    public static final function requestURI(){
        return filter_input(INPUT_SERVER, 'REQUEST_URI');
    }
    /**
     * 
     * @param string $route
     */
     public function route($route = '') {
        // Use the provided route or default to the current URL from the server request URI
        $request = $route ?: self::requestURI();
        $response = $this->map($request);

        if ($response) {
            try {
                $response->run();
            }
            catch (Exception $e) {
                http_response_code(500);
                echo $e->getMessage();
            }
        } else {
            // Handle 404 Not Found
            http_response_code(404);
            print "404 Not Found";
        }
    }
    /**
     * 
     * @param string $url
     * @return Controller
     */
    private function map($url) {
        foreach ($this->_routes as $route => $action) {
            // Convert route to regex
            $convert = $this->convert($route);

            // Check if current URL matches the route
            $matches = $this->match($convert, $url);
            if (!empty($matches)) {
                // Extract variable names and values
                $variables = $this->extract($route, $matches);

                return Controller::create($action, $variables);
            }
        }
        return null;
    }
    /**
     * @param string $route
     * @return string
     */
    private function convert($route) {
        // Convert placeholders to regex capture groups
        $routeRegex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([a-zA-Z0-9_]+)', $route);
        // Escape forward slashes for regex
        return str_replace('/', '\/', $routeRegex);
    }
    /**
     * @param string $routeRegex
     * @param string $currentUrl
     * @return array
     */
    private function match($routeRegex, $currentUrl) {
        if (preg_match('/^' . $routeRegex . '$/', $currentUrl, $matches)) {
            return $matches;
        }
        return array();
    }
    /**
     * @param string $route
     * @param array $matches
     * @return array
     */
    private function extract($route, array $matches = array( ) ) {
        if( count($matches)){
            // Remove the full match from the matches array
            array_shift($matches);
            // Extract variable names from the route
            preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $route, $variables );

            // Combine variable names with their corresponding values
            return array_combine($variables[1], $matches);
        }
        return array();
    }
}


/**
 * 
 */
abstract class Controller {
    private $_action;
    private $_variables;
    /**
     * 
     * @param string $action
     * @param array $variables
     */
    public function __construct($action, array $variables = array()) {
        
        $this->_action = $action;
        $this->_variables = $variables;
    }
    /**
     * @return string
     */
    public final function action(){
        return $this->_action;
    }
    /**
     * @return array
     */
    public final function variables(){
        return $this->_variables;
    }
    /**
     * 
     * @param string $controller
     * @param array $variables
     * @return \controllerName
     * @throws Exception
     */
    public final static function create( $controller, array $variables = array( ) ) {

        $parts = explode('@', $controller);
        $path = $parts[0];
        $class = $parts[0] . 'Controller';
        $action = $parts[1] ?? 'default';
        
        if(file_exists($path)){
            require_once $path;
        }
        else{
            throw new Exception("Controller Path {$path} not found");
        }

        if (class_exists($class) && is_subclass_of($class, self::class,true)) {
            return new $class($action, $variables);
        }
        else {
            throw new Exception("Controller Class {$class} not found");
        }
    }
    /**
     * @throws Exception
     */
    public function run() {
        $actionMethod = $this->_action . 'Action';
        if (method_exists($this, $actionMethod)) {
            call_user_func_array([$this, $actionMethod], $this->_variables);
        } else {
            throw new Exception("Action {$this->_action} not found in controller " . get_class($this));
        }
    }
    /**
     * 
     */
    abstract public function defaultAction();
}


/**
 * TEST AREA
 */

class UsersController extends Controller {
    public function defaultAction() {
        print  "Default action for UsersController";
    }

    public function editAction($user_id) {
        print "Editing user with ID: $user_id";
    }
}

// Initialize Router and define routes with chaining
$router = new Router();
$router->add('users/{user_id}/edit', 'Users@edit')
       ->add('products/{product_id}', 'Products@show')
       // Add more routes as needed
       ;

// Route using a custom route input (e.g., for testing)
$customRoute = 'users/123/edit';
$router->route($customRoute);

// Route using the current URL from the server request URI
$router->route();
