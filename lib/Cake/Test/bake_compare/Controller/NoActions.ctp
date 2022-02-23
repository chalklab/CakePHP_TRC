<?php
App::uses('AppController', 'Controller');
/**
 * Articles Controller
 *
 * @property Article $Article
 * @property AclComponent $Acl
 * @property AuthComponent $Auth
 * @property PaginatorComponent $Paginator
 */
class ArticlesController extends AppController {

/**
 * Helpers
 *
 * @var array
 */
	public array $helpers = array('Js', 'Time');

/**
 * Components
 *
 * @var array
 */
	public array $components = array('Acl', 'Auth', 'Paginator');

}
