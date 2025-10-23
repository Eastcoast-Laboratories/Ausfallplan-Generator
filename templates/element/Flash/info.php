<?php
/**
 * Info Flash Message Template
 * Uses the default template with info class
 */
$params['class'] = 'info';
echo $this->element('Flash/default', compact('message', 'params'));
