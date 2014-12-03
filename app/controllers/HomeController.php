<?php
    
    /**
     * HomeController
     * 
     * @author Edwin Dayot <edwin.dayot@sfr.fr>
     * @copyright 2014
     */

    namespace App\Controllers;

    use Core;
    use Core\View;
    use Core\Cookie;

    /**
     * HomeController Class
     */
    class HomeController extends Core\Controller
    {
        
        /**
         * Index Action
         * 
         * @return void
         */
        function index() {
            $data = array('lol' => 'lol');

            $this->loadModel('Post');

            $data['posts'] = $this->Post->select();

            if (!isset($_COOKIE['EasyKit_cookie_hello'])) {
                Cookie::set('hello', rand(0,10));
            }

            View::make('home.index', $data, 'default');
        }

        function admin_index() {
            View::$current = 'dash';
            View::$title = 'Dashboard';

            View::make('home.admin_index', null, 'admin');
        }
    }
