<?php
/**
 * Warning Flash Message Template
 * Uses the default template with warning class
 */
$params['class'] = 'warning';
echo $this->element('Flash/default', compact('message', 'params'));
