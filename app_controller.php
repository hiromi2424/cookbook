<?php
/**
 * Short description for app_controller.php
 *
 * Long description for app_controller.php
 *
 * PHP version 4 and 5
 *
 * Copyright (c) 2009, Rapid Development Framework <http://www.cakephp.org/>
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @filesource
 * @copyright     Copyright (c) 2009, Rapid Development Framework <http://www.cakephp.org/>
 * @link          http://www.cakephp.org
 * @package       cakebook
 * @subpackage    cakebook
 * @since         CakePHP v 0.2.9
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */
/**
 * AppController class
 *
 * @uses          Controller
 * @package       cookbook
 * @subpackage    cookbook
 */
class AppController extends Controller {
/**
 * components variable
 *
 * @var array
 * @access public
 */
	var $components = array(
		'Users.Bakery',
		'Auth',
		'Session',
		'Cookie',
		'RequestHandler'
	);
/**
 * helpers variable
 *
 * @var array
 * @access public
 */
	var $helpers = array(
		'Mi.Tree',
		'Mi.Menu',
		'MiAsset.Asset',
		'Form', 'Time', 'Javascript',
		'Cache',
		'Session'
	);
/**
 * currentNode variable
 *
 * @var bool
 * @access public
 */
	var $currentNode = false;
/**
 * currentPath variable
 *
 * @var bool
 * @access public
 */
	var $currentPath = array();
/**
 * beforeFilter function
 *
 * @access public
 * @return void
 */
	function beforeFilter() {
		// Store where they came from
		$realReferer = $this->referer(null, true);
		$sessionReferer = $this->Session->read('referer');
		if ($this->name == 'App') {
		} elseif (empty ($this->data) && !isset($this->params['requested'])) {
			if ($realReferer) {
				if ((!$sessionReferer) || ($realReferer != '/' . $this->params['url']['url'])) {
					$this->Session->write('referer', $realReferer);
				}
			} elseif (!$sessionReferer) {
				$this->Session->write('referer', $this->referer(array('action' => 'index')));
			}
		} elseif (!$sessionReferer) {
			$this->Session->write('referer', $this->referer(array('action' => 'index')));
		}
		$defaultLang = Configure::read('Languages.default');
		$this->params['theme'] = isset($this->params['theme'])?$this->params['theme']:'default';
		$this->params['lang'] = isset($this->params['lang'])?$this->params['lang']:$defaultLang;
		Configure::write('Config.language', $this->params['lang']);
		if (($this->name != 'App') && !in_array($this->params['lang'], Configure::read('Languages.all'))) {
			$this->Session->setFlash(__('Whoops, not a valid language.', true));
			return $this->redirect($this->Session->read('referer'), 301, true);
		}
		if (isset($this->Node)) {
			$this->Node->setLanguage($this->params['lang']);
		} elseif (isset($this->{$this->modelClass}->Node)) {
			$this->{$this->modelClass}->Node->setLanguage($this->params['lang']);
		}
		if (!$this->Auth->user()) {
			if(!in_array($this->name, array('Users', 'CakeError')) && !isset($this->params['requested'])) {
				$this->Session->write('Auth.redirect', '/' . $this->params['url']['url']);
			}
			$this->Auth->authError = __('Please login to continue', true);
		}
		$this->Auth->loginAction = array('lang' => $this->params['lang'], 'theme' => $this->params['theme'],
			'admin' => false, 'plugin' => 'users', 'controller' => 'users', 'action' => 'login');
		$this->Auth->autoRedirect = false;
		$this->Auth->allow('display');
		$this->Auth->ajaxLogin =  'login';
		$this->{$this->modelClass}->currentUserId = $this->Auth->user('id');
	}
/**
 * beforeRender function
 *
 * @access public
 * @return void
 */
	function beforeRender() {
		if (!isset ($this->viewVars['data'])) {
			$this->set('data', $this->data);
		}
		$this->set('modelClass', $this->modelClass);
		$this->set('isAdmin', isset($this->params['admin']));
		if (!$this->RequestHandler->isAjax()) {
			$this->layout = $this->params['theme'];
		}
		if ($this->name == 'App' && Configure::read()) {
			$this->layout = 'error';
		}
		$this->set('defaultLang', Configure::read('Languages.default'));
	}
/**
 * redirect function
 *
 * @param mixed $url
 * @param mixed $code
 * @param bool $exit
 * @access public
 * @return void
 */
	function redirect($url, $code = null, $exit = true, $force = false) {
		if ($force && !empty($this->params['isAjax'])) {
			$this->set(compact('url'));
			$this->output = '';
			return $this->render('/elements/force_redirect', 'ajax');
		}
		if (!is_array($url)) {
			return parent::redirect($url, $code, $exit);
		}
		$defaults = array(
			'controller' => Inflector::underscore($this->name),
			'action' => $this->action,
			'admin' => !empty($this->params['admin']),
			'lang' => $this->params['lang'],
			'theme' => $this->params['theme'],
		);
		$url = am($defaults, $url);
		return parent::redirect($url, $code, $exit);
	}
/**
 * admin_add function
 *
 * @access public
 * @return void
 */
	function admin_add() {
		if (!empty ($this->data)) {
			$this->data['Revision']['user_id'] = $this->Auth->user('id');
			if ($this->{$this->modelClass}->save($this->data)) {
				$this->Session->setFlash($this->{$this->modelClass}->name . ' added');
				$this->redirect($this->Session->read('referer'), null, true);
			} else {
				$this->Session->setFlash('Please correct the errors below.');
			}
		}
		// Populate belongTo select list vars
		foreach (array('belongsTo', 'hasAndBelongsToMany') as $type) {
			foreach (array_keys($this->{$this->modelClass}->$type) as $model) {
				if (is_array($this->{$this->modelClass}->$model->actsAs) && array_key_exists('Tree', $this->{$this->modelClass}->$model->actsAs)) {
					$items = $this->{$this->modelClass}->$model->generateTreeList();
				} else {
					if (is_array($this->{$this->modelClass}->$model->displayField)) {
						$order = implode($this->{$this->modelClass}->$model->displayField , ', ');
					} else {
						$order = $this->{$this->modelClass}->$model->alias . '.' . $this->{$this->modelClass}->$model->displayField;
					}
					$items = $this->{$this->modelClass}->$model->find('list', compact('order'));
				}
				$this->set(Inflector::underscore(Inflector::pluralize($model)), $items);
			}
		}
		$this->render('admin_edit');
	}
/**
 * admin_delete function
 *
 * @param mixed $id
 * @access public
 * @return void
 */
	function admin_delete($id) {
		if ($this->{$this->modelClass}->del($id)) {
			$this->Session->setFlash($this->modelClass . ' with id ' . $id . ' deleted');
		} else {
			$this->Session->setFlash('Can\'t delete ' . $this->modelClass . ' with id ' . $id);
		}
		$this->redirect($this->Session->read('referer'), null, true);
	}
/**
 * admin_edit function
 *
 * @param mixed $id
 * @access public
 * @return void
 */
	function admin_edit($id) {
		if (!$this->{$this->modelClass}->hasAny(array($this->{$this->modelClass}->primaryKey => $id))) {
			$this->redirect(array('action' => 'index'), null, true);
		}
		if (!empty ($this->data)) {
			//$this->data['Revision']['user_id'] = $this->Auth->user('id');
			if ($this->{$this->modelClass}->save($this->data)) {
				$this->Session->setFlash($this->{$this->modelClass}->alias . ' updated');
				$this->redirect($this->Session->read('referer'), null, true);
			} else {
				$this->Session->setFlash('Please correct the errors below.');
			}
		} else {
			$this->data = $this->{$this->modelClass}->read(null, $id);
		}
		// Populate belongTo select list vars
		foreach (array('belongsTo', 'hasAndBelongsToMany') as $type) {
			foreach (array_keys($this->{$this->modelClass}->$type) as $model) {
				if (is_array($this->{$this->modelClass}->$model->actsAs) && array_key_exists('Tree', $this->{$this->modelClass}->$model->actsAs)) {
					$items = $this->{$this->modelClass}->$model->generateTreeList();
				} else {
					if (is_array($this->{$this->modelClass}->$model->displayField)) {
						$order = implode($this->{$this->modelClass}->$model->displayField , ', ');
					} else {
						$order = $this->{$this->modelClass}->$model->alias . '.' . $this->{$this->modelClass}->$model->displayField;
					}
					$items = $this->{$this->modelClass}->$model->find('list', compact('order'));
				}
				$this->set(Inflector::underscore(Inflector::pluralize($model)), $items);
			}
		}
	}
/**
 * admin_view function
 *
 * @param mixed $id
 * @access public
 * @return void
 */
	function admin_view($id) {
		if (!$this->{$this->modelClass}->hasAny(array($this->{$this->modelClass}->primaryKey => $id))) {
			$this->redirect(array('action' => 'index'), null, true);
		}
		$this->data = $this->{$this->modelClass}->read(null, $id);
		if(!$this->data) {
			$this->Session->setFlash('Invalid ' . $this->modelClass);
			return $this->redirect($this->Session->read('referer'), null, true);
		}
	}
/**
 * admin_index function
 *
 * @access public
 * @return void
 */
	function admin_index() {
		if (!isset($this->__conditions)) {
			App::import('Component', 'Filter');
			$this->Filter = new FilterComponent();
			$this->Component->_loadComponents($this->Filter);
			$this->Filter->startup($this);
			$conditions = $this->Filter->parse();
		} else {
			$conditions = $this->__conditions;
		}
		unset($conditions[$this->modelClass . '.theme']);
		unset($conditions[$this->modelClass . '.language']);
		if (isset($conditions['Node.lang'])) {
			$conditions['Revision.lang'] = $conditions['Node.lang'];
			unset($conditions['Node.lang']);
		}
		$Node = ClassRegistry::init('Node');
		$collections = $Node->find('all', array('conditions' => array('Node.parent_id' => 1), 'fields' => 'Node.*, Revision.title'));
		$books = $Node->find('all', array('conditions' => array('Node.depth' => 2), 'fields' => 'Node.*, Revision.title'));
		$this->set(compact('collections', 'books'));
		$this->data = $this->paginate($conditions);
	}
/**
 * admin_search method
 *
 * @param mixed $term
 * @access public
 * @return void
 */
	function admin_search($term = null) {
		if ($this->data) {
			$term = trim($this->data[$this->modelClass]['query']);
			$this->redirect(array(urlencode($term)));
		}
		if (!$term) {
			$this->redirect(array('action' => 'index'));
		}
		$this->__conditions = $this->{$this->modelClass}->searchConditions($term);
		$this->Session->setFlash(sprintf(__('All %1$s matching the term "%2$s"', true), Inflector::humanize($this->name), h($term)));
		$this->admin_index();
		$this->render('admin_index');
	}
}
?>