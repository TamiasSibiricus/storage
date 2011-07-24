<?php

  CroogoRouter::connect('/admin/attachments', array('plugin' => 'storage', 'controller' => 'local', 'action' => 'index', 'admin' => true));
  CroogoRouter::connect('/admin/attachments/browse', array('plugin' => 'storage', 'controller' => 'local', 'action' => 'browse', 'admin' => true));
  //CroogoRouter::connect('/admin/attachments', array('plugin' => 'amazon_s3', 'controller' => 'local_media', 'action' => 'index', 'admin' => true));

?>