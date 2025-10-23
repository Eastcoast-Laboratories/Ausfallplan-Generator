<div class="users form content">
    <?= $this->Form->create(null) ?>
    <fieldset>
        <legend><?= __('Reset Password') ?></legend>
        <p><?= __('Enter the 6-digit code from your email and your new password.') ?></p>
        
        <?php
            echo $this->Form->control('code', [
                'type' => 'text',
                'label' => __('Reset Code'),
                'required' => true,
                'maxlength' => 6,
                'pattern' => '[0-9]{6}',
                'placeholder' => '000000',
                'autofocus' => true
            ]);
            echo $this->Form->control('new_password', [
                'type' => 'password',
                'label' => __('New Password'),
                'required' => true,
                'minlength' => 8
            ]);
        ?>
    </fieldset>
    
    <div class="form-actions">
        <?= $this->Form->button(__('Reset Password'), ['class' => 'button-primary']) ?>
        <?= $this->Html->link(__('Back to Login'), ['action' => 'login'], ['class' => 'button']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>
