<a href="#"><?php __('Media Storages'); ?></a>
<ul>
  <li><?php echo $html->link(__('Local', true), array('plugin' => 'storage', 'controller' => 'local', 'action' => 'index'));?></li>
  <li><?php echo $html->link(__('AWS S3', true), array('plugin' => 'storage', 'controller' => 's3media', 'action' => 'index'));?></li>
  <li><?php echo $html->link(__('Configure', true), array('plugin' => '', 'controller' => 'settings', 'action' => 'prefix', 'Service')); ?></li>
</ul>