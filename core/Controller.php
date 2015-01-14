<?php

    /**
     * Principal Controller
     * 
     * @author Edwin Dayot <edwin.dayot@sfr.fr>
     * @copyright 2014
     */

    namespace Core;

    use Core\Exceptions\NotFoundHTTPException;
    use Exception;

    /**
     * Class Controller
     *
     * @package Core
     */
    class Controller
    {
        /**
         * Action
         */
        private $actions;

        /**
         * @var
         */
        protected $layout = null;

        private $request_scheme;
        private $server_port;

        /**
         * Construct
         * 
         * @param string $action
         * @param array $params
         * 
         * @return boolean
         */
        function __construct($action, $params = array(), $layout = null) {
            new Session();
            Cookie::init();

            require_once 'HTML.php';

            $this->initLink();

            $this->actions = get_class_methods($this);

            if ($layout == null && $this->layout == null) {
                $layout = 'default';
            } else if ($layout == null && $this->layout != null) {
                $layout = $this->layout;
            }

            if (in_array($action, $this->actions)) {
                $this->httpStatus(200);
                foreach ($this->actions as $actions) {
                    if ($actions == 'constructor') {
                        call_user_func_array(array($this, $actions), $params);
                    }
                }
                call_user_func_array(array($this, $action), $params);
            } else {
                foreach ($this->actions as $actions) {
                    if ($actions == 'defaultAction') {
                        call_user_func_array(array($this, $actions), $params);
                        return true;
                    }
                }

                $class = explode('\\', get_class($this));

                throw new NotFoundHTTPException('Method ' . $action . ' in ' . end($class) . ' doesn\'t exists.', 1, $layout);
            }

            return true;
        }

        /**
         * Launch the 404 page
         * 
         * @return boolean
         */
        static public function pageNotFound($layout = 'default') {
            self::httpStatus(404);

            View::make('errors.404', '', $layout);
            return true;
        }

        /**
         * Launch the header status for each status code
         * 
         * @param int $code
         * 
         * @throws Exception when HTTP Status given isn't planned
         * @return boolean
         */
        static public function httpStatus($code) {
            switch ($code) {
                case 404:
                    header('HTTP/1.1 404 Not Found');
                    break;

                case 200:
                    header('HTTP/1.1 200 OK');
                    break;

                case 500:
                    header('HTTP/1.0 500 Internal Server Error');
                    break;
                
                default:
                    throw new Exception('Unknown HTTP Status', 1);
                    break;
            }

            return true;
        }

        /**
         * Redirect to a URL
         *
         * @param $link string
         */
        protected function redirect($link) {
            header('Location: ' . $this->link($link)); exit();
        }

        /**
         * Defines server informations
         */
        private function initLink(){
            if (!isset($_SERVER['REQUEST_SCHEME'])) {
                $this->request_scheme = 'http';
            } else {
                $this->request_scheme = $_SERVER['REQUEST_SCHEME'];
            }

            if ($_SERVER['SERVER_PORT'] == 80) {
                $this->server_port = '';
            } else {
                $this->server_port = ':' . $_SERVER['SERVER_PORT'];
            }
        }

        /**
         * Set a link as HTML::link does
         *
         * @param $link string
         * @return string
         */
        protected function link($link) {
            $link = !empty($link) ? '/' . trim($link, '/') : '';
            $script_name = trim(dirname(dirname($_SERVER['SCRIPT_NAME']))) != '/' ? '/' . trim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/') : '';

            return $this->request_scheme . '://' . trim($_SERVER['SERVER_NAME'], '/') . $this->server_port . $script_name . $link;
        }

        /**
         * Get the current URL
         *
         * @return string
         */
        protected function getCurrentURL() {
            return $this->request_scheme . '://' . $_SERVER['SERVER_NAME'] . $this->server_port . $_SERVER['REQUEST_URI'];
        }

        /**
         * Get the JSON
         *
         * @param $url
         * @return string
         */
        protected function getJSON($url) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $url);
            $return = curl_exec($ch);
            curl_close($ch);

            return json_decode($return, false);
        }

        /**
         * Load model
         * 
         * @param $model string Name of the model and name of the file
         * 
         * @throws Exception when Model not found
         */
        protected function loadModel($model) {
            if(!isset($this->$model)){
                $filename = __DIR__ . '/../app/models/' . ucfirst($model) . '.php';

                if (file_exists($filename)) {
                    require_once($filename);
                    $class = '\\App\\Models\\' . $model;
                    $this->$model = new $class();
                } else {
                    throw new Exception('Model ' . $model . ' was not found.', 1);
                }
            }
        }

        /**
         * Use the controller
         *
         * @param string $name
         * @param string $prefix
         * @param array $params
         * @param bool $layout
         * @param string $default
         *
         * @throws NotFoundHTTPException
         */
        protected function useController($name, $prefix = '', $params, $layout = false, $default = 'index') {
            if (empty($params)) {
                $action = $default;
            } else {
                $action = $params[0];
                array_shift($params);
            }

            $req = array(
                'controller'    => ucfirst($name),
                'action'        => $prefix . $action,
                'params'        => $params,
                'layout'        => $layout,

            );

            Dispatcher::loadController($req);
        }

        /**
         * Upload file
         *
         * @param $file
         * @param array $permission
         */
        protected function upload($file, $permission = array()) {
            if (in_array($file['type'], $permission)) {
                $directory = __DIR__ . '/../public/uploads/';

                $type = explode('/', $file['type']);

                foreach ($type as $value) {
                    if (!is_dir($directory . $value)) {
                        mkdir($directory . $value, 0755);
                    }

                    $directory .= $value . '/';
                }

                $directory .= '/' . md5(uniqid(mt_rand(), true)) . $file['name'];

                if (move_uploaded_file($file['tmp_name'], $directory)) {
                    $return = [
                        'success'   => true,
                        'directory' => realpath($directory),
                        'extension' => pathinfo($directory, PATHINFO_EXTENSION)
                    ];
                } else {
                    $return = [
                        'success'   => false
                    ];
                }

                return $return;
            } else {
                return false;
            }
        }
    }
