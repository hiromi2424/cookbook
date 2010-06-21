<?php
echo $form->create(null, array('type' => 'file', 'url' => '/' . $this->params['url']['url']));
echo $form->inputs(array(
	'legend' => 'Import contents from another install',
	'file' => array('type' => 'file', 'label' => 'XML file to import'),
	'delete_exists' => array('type' => 'checkbox', 'checked' => 'checked', 'label' => 'Delete any nodes at first?'),
	'take_backup' => array('type' => 'checkbox', 'label' => 'Backup current contents before import?'),
));
echo $form->submit();
echo $form->end();
?>