<?php
/**
 * @var \App\View\AppView $this
 * @var string $message
 * @var string $url
 */
use Cake\Core\Configure;
use Cake\Error\Debugger;

$this->layout = 'error';

if (Configure::read('debug')) :
    $this->layout = 'dev_error';

    $this->assign('title', $message);
    $this->assign('templateName', 'error500.php');

    $this->start('file');
?>
<?php if ($error instanceof Error) : ?>
    <?php $file = $error->getFile() ?>
    <?php $line = $error->getLine() ?>
    <strong>Error in: </strong>
    <?= $this->Html->link(sprintf('%s, line %s', Debugger::trimPath($file), $line), Debugger::editorUrl($file, $line)); ?>
<?php endif; ?>
<?php
    echo $this->element('auto_table_warning');

    $this->end();
endif;
?>
<h2><?= __d('cake', 'An Internal Error Has Occurred.') ?></h2>
<p class="error">
    <strong><?= __d('cake', 'Error') ?>: </strong>
    <?= h($message) ?>
</p>

<?php if (isset($error) && $error): ?>
<div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-left: 4px solid #dc3545; border-radius: 4px;">
    <h3 style="color: #dc3545; margin-top: 0;">📋 Error Details</h3>
    
    <?php if (method_exists($error, 'getMessage')): ?>
    <p><strong>Type:</strong> <?= h(get_class($error)) ?></p>
    <p><strong>Message:</strong> <?= h($error->getMessage()) ?></p>
    <?php endif; ?>
    
    <?php if (method_exists($error, 'getFile') && method_exists($error, 'getLine')): ?>
    <p><strong>Location:</strong> <?= h(basename($error->getFile())) ?>:<?= h($error->getLine()) ?></p>
    <?php endif; ?>
    
    <p style="margin-top: 1rem; font-size: 0.9em; color: #666;">
        <strong>Timestamp:</strong> <?= date('Y-m-d H:i:s') ?><br>
        <strong>Request URL:</strong> <?= h($this->request->getRequestTarget()) ?>
    </p>
</div>

<div style="margin-top: 1.5rem; padding: 1rem; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
    <h4 style="color: #856404; margin-top: 0;">💡 What to do:</h4>
    <ul style="margin-bottom: 0;">
        <li>If you're an administrator, check the <strong>error logs</strong> for more details</li>
        <li>Try refreshing the page or going back</li>
        <li>If the problem persists, contact support with the error details above</li>
    </ul>
</div>
<?php endif; ?>

<p style="margin-top: 2rem;">
    <?= $this->Html->link('← ' . __('Go Back'), 'javascript:history.back()', ['class' => 'button']) ?>
    <?= $this->Html->link('🏠 ' . __('Go to Homepage'), '/', ['class' => 'button', 'style' => 'margin-left: 1rem;']) ?>
</p>
