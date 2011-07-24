<?php
/**
 * Example Activation
 *
 * Activation class for Example plugin.
 * This is optional, and is required only if you want to perform tasks when your plugin is activated/deactivated.
 *
 * @package  Croogo
 * @author   Fahad Ibnay Heylaal <contact@fahad19.com>
 * @license  http://www.opensource.org/licenses/mit-license.php The MIT License
 * @link     http://www.croogo.org
 */
class StorageActivation {
/**
 * onActivate will be called if this returns true
 *
 * @param  object $controller Controller
 * @return boolean
 */
    public function beforeActivation(&$controller) {
        return true;
    }
/**
 * Called after activating the plugin in ExtensionsPluginsController::admin_toggle()
 *
 * @param object $controller Controller
 * @return void
 */
    public function onActivation(&$controller) {
      $controller->Croogo->addAco('Local');
      $controller->Croogo->addAco('Local/admin_index');
      $controller->Croogo->addAco('Local/admin_add');
      $controller->Croogo->addAco('Local/admin_edit');
      $controller->Croogo->addAco('Local/admin_delete');
      $controller->Croogo->addAco('S3');
      $controller->Croogo->addAco('S3/admin_index');
      $controller->Croogo->addAco('S3/admin_add');
      $controller->Croogo->addAco('S3/admin_edit');
      $controller->Croogo->addAco('S3/admin_delete');
 
      //init settings for S3
      $controller->Setting->write('Service.mediaPath', 'uploads', array('editable' => 1, 'description' => 'Path to upload media'));
      $controller->Setting->write('Service.s3bucketName', 'Provide Your bucket name', array('editable' => 1, 'description' => 'Amazon S3 bucket name'));
      $controller->Setting->write('Service.s3accessKey', 'Provide Your Access Key ID', array('editable' => 1, 'description' => 'Amazon S3 Access Key ID'));
      $controller->Setting->write('Service.s3secretKey', 'Provide Your Secret Access Key', array('editable' => 1, 'description' => 'Amazon S3 Secret Access Key'));

    }
/**
 * onDeactivate will be called if this returns true
 *
 * @param  object $controller Controller
 * @return boolean
 */
    public function beforeDeactivation(&$controller) {
        return true;
    }
/**
 * Called after deactivating the plugin in ExtensionsPluginsController::admin_toggle()
 *
 * @param object $controller Controller
 * @return void
 */
    public function onDeactivation(&$controller) {
      // ACL: remove ACOs with permissions
      $controller->Croogo->removeAco('Local');
      $controller->Croogo->removeAco('S3');

      //remove S3 settings
      $controller->Setting->deleteKey('Service.mediaPath');
      $controller->Setting->deleteKey('Service.s3bucketName');
      $controller->Setting->deleteKey('Service.s3accessKey');
      $controller->Setting->deleteKey('Service.s3secretKey');
    }
}
?>