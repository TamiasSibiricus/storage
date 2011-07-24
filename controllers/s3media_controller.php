<?php
/**
 * S3Media Controller
 *
 * This file will take care of file uploads (with rich text editor integration).
 *
 * Big part of code inspired from original Croogo attachments controller
 *  
 * PHP version 5
 *
 * @category Controller
 * @package  Croogo
 * @version  1.0
 * @author   Denys Kyselov <denys.kyselov@gmail.com>
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link     http://www.cmgroup.org.ua
 */

class S3mediaController extends AppController {
/**
 * Controller name
 *
 * @var string
 * @access public
 */
    public $name = 'S3media';
/**
 * Models used by the Controller
 *
 * @var array
 * @access public
 */
    public $uses = array('Node', 'Storage.S3media');
/**
 * Helpers used by the Controller
 *
 * @var array
 * @access public
 */
    public $helpers = array('Filemanager', 'Text', 'Storage.S3image');
/**
 * Node type
 *
 * If the Controller uses Node model,
 * this is, most of the time, the singular of the Controller name in lowercase.
 *
 * @var string
 * @access public
 */
    public $type = 's3media';
/**
 * Uploads directory
 *
 * relative to the webroot.
 *
 * @var string
 * @access public
 */
    public $uploadsDir = 'uploads';

/**
 * Before executing controller actions
 *
 * @return void
 * @access public
 */
    public function beforeFilter() {
        parent::beforeFilter();

        // Comment, Category, Tag not needed
        $this->Node->unbindModel(array('hasMany' => array('Comment'), 'hasAndBelongsToMany' => array('Category', 'Tag')));

        $this->Node->type = $this->type;
        $this->Node->Behaviors->attach('Tree', array('scope' => array('Node.type' => $this->type)));
        $this->set('type', $this->type);
    }
/**
 * Admin index
 *
 * @return void
 * @access public
 */
    public function admin_index() {
      $this->set('title_for_layout', __('S3 storage', true));

      $this->Node->recursive = 0;
      $this->paginate['Node']['order'] = 'Node.created DESC';
      
      $this->set('s3media', $this->paginate());
    }
    
/**
 * Admin add
 *
 * @return void
 * @access public
 */
    public function admin_add() {
        $this->set('title_for_layout', __('Add Amazon S3 Media', true));
        $this->uploadsDir = Configure::read('Service.mediaPath', $this->uploadsDir);


        if (isset($this->params['named']['editor'])) {
            $this->layout = 'admin_full';
        }

        if (!$this->S3media->verifyBucket()){
          $this->Session->setFlash(__('Problems with connection to Amazon S3 bucket. Maybe this is network problem. Please, try again later.', true), 'default', array('class' => 'error'));
        }

        if (!empty($this->data)) {
            $file = $this->data['Node']['file'];
            unset($this->data['Node']['file']);

            // check if file with same path exists
            $destination = WWW_ROOT . $this->uploadsDir . DS . $file['name'];
            if (file_exists($destination)) {
                $newFileName = String::uuid() . '-' . $file['name'];
                $destination = WWW_ROOT . $this->uploadsDir . DS . $newFileName;
            } else {
                $newFileName = $file['name'];
            }

            // remove the extension for title
            if (explode('.', $file['name']) > 0) {
                $fileTitleE = explode('.', $file['name']);
                array_pop($fileTitleE);
                $fileTitle = implode('.', $fileTitleE);
            } else {
                $fileTitle = $file['name'];
            }

            $this->data['Node']['title'] = $fileTitle;
            $this->data['Node']['slug'] = $newFileName;
            $this->data['Node']['mime_type'] = $file['type'];
            //$this->data['Node']['guid'] = Router::url('/' . $this->uploadsDir . '/' . $newFileName, true);
            $this->data['Node']['path'] = '/' . $this->uploadsDir . '/' . $newFileName;

            $this->Node->create();
            if ($this->Node->save($this->data)) {
                // move the file to S3 bucket
                move_uploaded_file($file['tmp_name'], $destination);
                if ($this->S3media->save($this->data['Node'])){
                  unlink(WWW_ROOT . $this->uploadsDir . DS . $this->data['Node']['slug']);
                }

                $this->Session->setFlash(__('The Local Media has been saved', true), 'default', array('class' => 'success'));

                if (isset($this->params['named']['editor'])) {
                    $this->redirect(array('action' => 'browse'));
                } else {
                    $this->redirect(array('action'=>'index'));
                }
            } else {
                $this->Session->setFlash(__('The Local could not be saved. Please, try again.', true), 'default', array('class' => 'error'));
            }
        }
    }

/**
 * Admin edit
 *
 * @param int $id 
 * @return void
 * @access public
 */
    public function admin_edit($id = null) {
        $this->set('title_for_layout', __('Edit Media Info', true));

        if (!$id && empty($this->data)) {
            $this->Session->setFlash(__('Invalid S3 Media', true), 'default', array('class' => 'error'));
            $this->redirect(array('action'=>'index'));
        }
        if (!empty($this->data)) {
            if ($this->Node->save($this->data)) {
                $this->Session->setFlash(__('The S3 Media Info has been saved', true), 'default', array('class' => 'success'));
                $this->redirect(array('action'=>'index'));
            } else {
                $this->Session->setFlash(__('The S3 Media Info could not be saved. Please, try again.', true), 'default', array('class' => 'error'));
            }
        }
        if (empty($this->data)) {
            $this->data = $this->Node->read(null, $id);
        }
    }
    
/**
 * Admin delete
 *
 * @param int $id 
 * @return void
 * @access public
 */
    public function admin_delete($id = null) {
        if (!$id) {
            $this->Session->setFlash(__('Invalid id for S3 Media', true), 'default', array('class' => 'error'));
            $this->redirect(array('controller'=>'s3media', 'action'=>'index'));
        }
        if (!isset($this->params['named']['token']) || ($this->params['named']['token'] != $this->params['_Token']['key'])) {
            $blackHoleCallback = $this->Security->blackHoleCallback;
            $this->$blackHoleCallback();
        }

        $attachment = $this->Node->find('first', array(
            'conditions' => array(
                'Node.id' => $id,
                'Node.type' => $this->type,
            ),
        ));
        if (isset($attachment['Node'])) {
            if ($this->Node->delete($id)) {
                unlink(WWW_ROOT . $this->uploadsDir . DS . $attachment['Node']['slug']);
                $this->Session->setFlash(__('S3 media deleted', true), 'default', array('class' => 'success'));
                $this->redirect(array('controller'=>'s3media', 'action'=>'index'));
            }
        } else {
            $this->Session->setFlash(__('Invalid id for S3 media', true), 'default', array('class' => 'error'));
            $this->redirect(array('controller'=>'s3media', 'action'=>'index'));
        }
    }

    public function admin_browse() {
        $this->layout = 'admin_full';
        $this->admin_index();
    }
    
 }