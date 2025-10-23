<div class="users form content">
    <?= $this->Form->create(null) ?>
    <fieldset>
        <legend><?= __('Forgot Password?') ?></legend>
        <p><?= __('Enter your email address and we\'ll send you a reset code.') ?></p>
        
        <?php
            echo $this->Form->control('email', [
                'type' => 'email',
                'label' => __('Email'),
                'required' => true,
                'autofocus' => true
            ]);
        ?>
    </fieldset>
    
    <div class="form-actions">
        <?= $this->Form->button(__('Send Reset Code'), ['class' => 'button-primary']) ?>
        <?= $this->Html->link(__('Back to Login'), ['action' => 'login'], ['class' => 'button']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
