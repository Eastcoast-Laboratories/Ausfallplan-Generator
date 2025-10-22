<?php
/**
 * @var \App\View\AppView $this
 */
?>
<div class="users form content">
    <?= $this->Form->create() ?>
    <fieldset>
        <legend><?= __('Login') ?></legend>
        <p><?= __('Please enter your email and password to access your account.') ?></p>
        
        <?php
            echo $this->Form->control('email', [
                'type' => 'email',
                'required' => true,
                'placeholder' => 'email@example.com'
            ]);
            echo $this->Form->control('password', [
                'type' => 'password',
                'required' => true
            ]);
        ?>
    </fieldset>
    
    <div class="form-actions">
        <?= $this->Form->button(__('Login'), ['class' => 'button-primary']) ?>
        <?= $this->Html->link(__('Create new account'), ['action' => 'register'], ['class' => 'button']) ?>
    </div>
    <?= $this->Form->end() ?>
</div>

<style>
.users.form {
    max-width: 500px;
    margin: 2rem auto;
    padding: 2rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.users.form fieldset {
    border: none;
    padding: 0;
}

.users.form legend {
    font-size: 1.8rem;
    color: #333;
    margin-bottom: 0.5rem;
}

.users.form p {
    color: #666;
    margin-bottom: 1.5rem;
}

.form-actions {
    margin-top: 1.5rem;
    display: flex;
    gap: 1rem;
}

.form-actions .button {
    flex: 1;
}
</style>
