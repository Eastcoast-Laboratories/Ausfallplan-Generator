<?php
/**
 * Success Flash Message Template
 * Uses the default template with success class
 */
$params['class'] = 'success';
echo $this->element('Flash/default', compact('message', 'params'));
