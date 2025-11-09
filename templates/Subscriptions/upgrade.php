<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $currentUser
 * @var string $plan
 */
$this->assign('title', __('Upgrade Subscription'));

$planNames = [
    'pro' => 'Pro',
    'enterprise' => 'Enterprise'
];

$planPrices = [
    'pro' => '€5/' . __('month'),
    'enterprise' => __('On request')
];
?>

<div class="subscriptions form content">
    <h3><?= __('Upgrade to {0}', $planNames[$plan] ?? $plan) ?></h3>
    
    <div style="margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
        <h4 style="margin-top: 0;"><?= __('Plan Details') ?></h4>
        <p><strong><?= __('Plan') ?>:</strong> <?= h($planNames[$plan] ?? $plan) ?></p>
        <p><strong><?= __('Price') ?>:</strong> <?= h($planPrices[$plan] ?? '') ?></p>
        
        <?php if ($plan === 'pro'): ?>
            <p><?= __('Billed monthly. Cancel anytime.') ?></p>
        <?php else: ?>
            <p><?= __('Please contact our sales team for enterprise pricing.') ?></p>
        <?php endif; ?>
    </div>
    
    <?php if ($plan === 'enterprise'): ?>
        <p><?= __('For enterprise subscriptions, please contact us directly:') ?></p>
        <p><a href="mailto:fairnestplan-kontakt@it.z11.de">fairnestplan-kontakt@it.z11.de</a></p>
        <p><?= $this->Html->link(__('Back to Plans'), ['action' => 'index'], ['class' => 'button']) ?></p>
    <?php else: ?>
        <?= $this->Form->create(null, ['type' => 'post']) ?>
        <fieldset>
            <legend><?= __('Payment Method') ?></legend>
            
            <div style="margin: 1rem 0;">
                <label style="display: block; margin-bottom: 1rem;">
                    <input type="radio" name="payment_method" value="paypal" required>
                    <span style="margin-left: 0.5rem;">
                        <strong>PayPal</strong> - <?= __('Pay securely with PayPal') ?>
                    </span>
                </label>
                
                <label style="display: block; margin-bottom: 1rem;">
                    <input type="radio" name="payment_method" value="bank_transfer" required>
                    <span style="margin-left: 0.5rem;">
                        <strong><?= __('Bank Transfer') ?></strong> - <?= __('Transfer to our bank account') ?>
                    </span>
                </label>
            </div>
            
            <div style="margin-top: 1.5rem; padding: 1rem; background: #fff3cd; border-radius: 4px; border-left: 4px solid #ffc107;">
                <p style="margin: 0;">
                    <strong><?= __('Note') ?>:</strong> 
                    <?= __('Your subscription will be activated after payment confirmation.') ?>
                </p>
            </div>
        </fieldset>
        
        <div style="margin-top: 1.5rem;">
            <?= $this->Form->button(__('Complete Upgrade'), ['class' => 'button']) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'button', 'style' => 'background: #6c757d; margin-left: 1rem;']) ?>
        </div>
        <?= $this->Form->end() ?>
    <?php endif; ?>
</div>
