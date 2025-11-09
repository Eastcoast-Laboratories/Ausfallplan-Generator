<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $currentUser
 * @var array $plans
 */
$this->assign('title', __('Subscription Plans'));
?>

<div class="subscriptions index content">
    <h3><?= __('Subscription Plans') ?></h3>
    
    <!-- Current Subscription -->
    <div style="margin-bottom: 2rem; padding: 1.5rem; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #28a745;">
        <h4 style="margin-top: 0;"><?= __('Current Subscription') ?></h4>
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <p style="margin: 0.5rem 0;">
                    <strong><?= __('Plan') ?>:</strong> 
                    <?= h(ucfirst($currentUser->subscription_plan)) ?>
                </p>
                <p style="margin: 0.5rem 0;">
                    <strong><?= __('Status') ?>:</strong> 
                    <span style="color: <?= $currentUser->subscription_status === 'active' ? 'green' : 'orange' ?>;">
                        <?= h(ucfirst($currentUser->subscription_status)) ?>
                    </span>
                </p>
                <?php if ($currentUser->subscription_expires_at): ?>
                    <p style="margin: 0.5rem 0;">
                        <strong><?= __('Expires') ?>:</strong> 
                        <?= h($currentUser->subscription_expires_at->format('Y-m-d')) ?>
                    </p>
                <?php endif; ?>
            </div>
            <?php if ($currentUser->subscription_plan !== 'test'): ?>
                <div>
                    <?= $this->Form->postLink(
                        __('Cancel Subscription'),
                        ['action' => 'cancel'],
                        [
                            'confirm' => __('Are you sure you want to cancel your subscription? You will be downgraded to the free test plan.'),
                            'class' => 'button',
                            'style' => 'background: #dc3545;'
                        ]
                    ) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Available Plans -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem;">
        <?php foreach ($plans as $planKey => $plan): ?>
            <div class="price-card" style="border: 2px solid <?= $currentUser->subscription_plan === $planKey ? '#28a745' : '#ddd' ?>; border-radius: 8px; padding: 2rem; text-align: center;">
                <?php if ($currentUser->subscription_plan === $planKey): ?>
                    <div style="background: #28a745; color: white; padding: 0.5rem; margin: -2rem -2rem 1rem -2rem; border-radius: 6px 6px 0 0;">
                        <?= __('Current Plan') ?>
                    </div>
                <?php endif; ?>
                
                <h3 style="margin-top: 0;"><?= h($plan['name']) ?></h3>
                <div style="font-size: 2rem; font-weight: bold; margin: 1rem 0; color: #333;">
                    <?= h($plan['price_display']) ?>
                </div>
                
                <ul style="text-align: left; list-style: none; padding: 0; margin: 1.5rem 0;">
                    <?php foreach ($plan['features'] as $feature): ?>
                        <li style="padding: 0.5rem 0; border-bottom: 1px solid #eee;">
                            ✓ <?= h($feature) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <?php if ($currentUser->subscription_plan !== $planKey && $planKey !== 'test'): ?>
                    <?= $this->Html->link(
                        __('Upgrade to {0}', $plan['name']),
                        ['action' => 'upgrade', $planKey],
                        ['class' => 'button', 'style' => 'background: #007bff; color: white; display: inline-block; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px;']
                    ) ?>
                <?php elseif ($planKey === 'enterprise'): ?>
                    <a href="mailto:fairnestplan-kontakt@it.z11.de" class="button" style="background: #6c757d; color: white; display: inline-block; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px;">
                        <?= __('Contact Sales') ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
