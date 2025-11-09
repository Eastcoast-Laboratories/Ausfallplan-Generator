<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\User $currentUser
 * @var string $plan
 * @var string $paypalClientId
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
        <p>
            <a href="mailto:fairnestplan-kontakt@it.z11.de?subject=<?= urlencode(__('Enterprise Subscription Inquiry')) ?>&body=<?= urlencode(__('Hello,

I am interested in an Enterprise subscription for FairnestPlan.

Organization: 
Number of users: 
Number of children: 

Please provide me with more information about pricing and features.

Best regards')) ?>">
                fairnestplan-kontakt@it.z11.de
            </a>
        </p>
        <p><?= $this->Html->link(__('Back to Plans'), ['action' => 'index'], ['class' => 'button']) ?></p>
    <?php else: ?>
        <?= $this->Form->create(null, ['type' => 'post']) ?>
        <fieldset>
            <legend><?= __('Payment Method') ?></legend>
            
            <div style="margin: 1rem 0;">
                <label style="display: block; margin-bottom: 1rem; cursor: pointer;">
                    <input type="radio" name="payment_method" value="paypal" id="payment-paypal" required>
                    <span style="margin-left: 0.5rem;">
                        <strong>PayPal</strong> - <?= __('Pay securely with PayPal') ?>
                    </span>
                </label>
                
                <div id="paypal-button-container" style="margin: 1rem 0 1rem 2rem; display: none;"></div>
                
                <label style="display: block; margin-bottom: 1rem; cursor: pointer;">
                    <input type="radio" name="payment_method" value="bank_transfer" id="payment-bank" required>
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
            <?= $this->Form->button(__('Complete Upgrade'), ['class' => 'button', 'id' => 'submit-upgrade']) ?>
            <?= $this->Html->link(__('Cancel'), ['action' => 'index'], ['class' => 'button', 'style' => 'background: #6c757d; margin-left: 1rem;']) ?>
        </div>
        <?= $this->Form->end() ?>
    <?php endif; ?>
</div>

<?php if ($plan !== 'enterprise'): ?>
<!-- PayPal SDK -->
<script src="https://www.paypal.com/sdk/js?client-id=<?= h($paypalClientId) ?>&currency=EUR"></script>

<script>
const paypalRadio = document.getElementById('payment-paypal');
const bankRadio = document.getElementById('payment-bank');
const paypalContainer = document.getElementById('paypal-button-container');
const submitButton = document.getElementById('submit-upgrade');

// Show/hide PayPal button based on selection
paypalRadio.addEventListener('change', function() {
    if (this.checked) {
        paypalContainer.style.display = 'block';
        submitButton.style.display = 'none';
    }
});

bankRadio.addEventListener('change', function() {
    if (this.checked) {
        paypalContainer.style.display = 'none';
        submitButton.style.display = 'inline-block';
    }
});

// PayPal Buttons
paypal.Buttons({
    createOrder: function(data, actions) {
        return actions.order.create({
            purchase_units: [{
                amount: {
                    value: '5.00',
                    currency_code: 'EUR'
                },
                description: 'FairNestPlan Pro Subscription'
            }]
        });
    },
    onApprove: function(data, actions) {
        return actions.order.capture().then(function(details) {
            // Send order details to server
            fetch('/subscriptions/paypal-success', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('input[name="_csrfToken"]').value
                },
                body: JSON.stringify({
                    orderID: data.orderID,
                    plan: '<?= h($plan) ?>',
                    payerID: data.payerID,
                    details: details
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '/subscriptions?success=1';
                } else {
                    alert('Payment processing failed. Please contact support.');
                }
            });
        });
    },
    onError: function(err) {
        console.error('PayPal Error:', err);
        alert('An error occurred with PayPal. Please try again or use bank transfer.');
    }
}).render('#paypal-button-container');
</script>
<?php endif; ?>
