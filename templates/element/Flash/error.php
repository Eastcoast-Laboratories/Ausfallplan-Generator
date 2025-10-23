<?php
/**
 * Error Flash Message Template
 * Uses the default template with error class
 */
$params['class'] = 'error';
echo $this->element('Flash/default', compact('message', 'params'));
