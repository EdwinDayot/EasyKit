<?php

/**
 * AdminController
 *
 * @author Edwin Dayot <edwin.dayot@sfr.fr>
 * @copyright 2014
 */

namespace App\Controllers;

use Core;
use Core\Controller;
use Core\Exceptions\NotFoundHTTPException;
use Core\Session;
use Core\View;
use Core\Cookie;
use \Exception;

/**
 * AdminController Class
 *
 * @package App\Controllers
 * @property mixed Comments
 */
class CommentsController extends AppController
{

    /**
     * Errors
     *
     * @var array
     */
    private $errors = [];

    /**
     * Data for Model
     *
     * @var $pack
     * @var $content
     * @var $token
     */
    private $pack, $content, $token;

    /**
     * Get Pack
     *
     * @return mixed
     */
    public function getPack()
    {
        return $this->pack;
    }

    /**
     * Set Pack
     *
     * @param mixed $pack
     */
    public function setPack($pack)
    {
        $this->pack = $pack;
    }

    /**
     * Get Content
     *
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set Content
     *
     * @param mixed $content
     */
    public function setContent($content)
    {
        $this->content = $content;
    }

    /**
     * Get Token
     *
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set Token
     *
     * @param mixed $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     *
     */
    function api_get()
    {
        $data['errors'] = $this->errors;
        $data['success'] = !empty($this->errors) ? false : true;

        View::make('api.index', json_encode($data), false, 'application/json');
    }

    /**
     * Create
     *
     * @param null $id
     * @throws NotFoundHTTPException
     */
    function api_create($id = null)
    {
        if (!empty($_POST)) {
            $this->loadModel('Comments');

            if ($id != null) {
                $this->setPack($id);
            } else {
                $this->errors['content'] = 'Empty pack.';
            }

            if (isset($_POST['content']) && $_POST['content'] != null) {
                $this->setContent($_POST['content']);
            } else {
                $this->errors['content'] = 'Empty content.';
            }

            if (isset($_POST['token']) && $_POST['token'] != null) {
                $this->setToken($_POST['token']);
            } else {
                $this->errors['token'] = 'No token given.';
            }

            $user = $this->getJSON($this->link('api/users/checkToken/' . $this->getToken() . '/' . $_SERVER['REMOTE_ADDR']));
            if ($user->valid) {
                $user_id = $user->user->tokens_users_id;
            } else {
                $this->errors['user'] = $user->errors;
            }

            $this->loadModel('Packs');

            $pack = $this->Packs->select([
                'conditions'    => [
                    'id'            => $this->getPack()
                ]
            ]);

            if (empty($this->errors) && count($pack) == 1) {
                $this->Comments->save([
                    'users_id'  => $user_id,
                    'packs_id'  => $this->getPack(),
                    'content'   => $this->getContent()
                ]);
            }
        } else {
            $this->errors['post'] = 'No POST received.';
        }

        $data['success'] = !empty($this->errors) ? false : true;
        $data['errors'] = $this->errors;

        View::make('api.index', json_encode($data), false, 'application/json');
    }
}
