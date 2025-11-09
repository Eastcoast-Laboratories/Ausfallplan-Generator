<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Core\Configure;

/**
 * Subscriptions Controller
 *
 * Handles subscription plan management and payment processing
 */
class SubscriptionsController extends AppController
{
    /**
     * Show subscription plans and current subscription
     *
     * @return \Cake\Http\Response|null|void
     */
    public function index()
    {
        $user = $this->Authentication->getIdentity();
        if (!$user) {
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }
        
        $usersTable = $this->fetchTable('Users');
        $currentUser = $usersTable->get($user->id);
        
        // Define subscription plans
        $plans = [
            'test' => [
                'name' => 'Test Plan',
                'price' => 0,
                'price_display' => __('Free'),
                'features' => [
                    __('1 Organization'),
                    __('1 active schedule'),
                    __('PDF Export'),
                    __('Community Support')
                ],
                'limits' => [
                    'organizations' => 1,
                    'schedules' => 1
                ]
            ],
            'pro' => [
                'name' => 'Pro',
                'price' => 5,
                'price_display' => '€5/' . __('month'),
                'features' => [
                    __('Unlimited organizations'),
                    __('Unlimited schedules'),
                    __('PDF Export'),
                    __('Priority Support')
                ],
                'limits' => [
                    'organizations' => null,
                    'schedules' => null
                ]
            ],
            'enterprise' => [
                'name' => 'Enterprise',
                'price' => null,
                'price_display' => __('On request'),
                'features' => [
                    __('SSO/SAML Integration'),
                    __('SLA Agreement'),
                    __('Dedicated Support'),
                    __('Custom Features')
                ],
                'limits' => [
                    'organizations' => null,
                    'schedules' => null
                ]
            ]
        ];
        
        $this->set(compact('currentUser', 'plans'));
    }
    
    /**
     * Upgrade to a new plan
     *
     * @param string|null $plan Plan name (pro, enterprise)
     * @return \Cake\Http\Response|null|void
     */
    public function upgrade($plan = null)
    {
        $this->request->allowMethod(['get', 'post']);
        
        $user = $this->Authentication->getIdentity();
        if (!$user) {
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }
        
        $usersTable = $this->fetchTable('Users');
        $currentUser = $usersTable->get($user->id);
        
        // Validate plan
        $validPlans = ['pro', 'enterprise'];
        if (!in_array($plan, $validPlans)) {
            $this->Flash->error(__('Invalid subscription plan.'));
            return $this->redirect(['action' => 'index']);
        }
        
        if ($this->request->is('post')) {
            $paymentMethod = $this->request->getData('payment_method');
            
            // Validate payment method
            if (!in_array($paymentMethod, ['paypal', 'bank_transfer'])) {
                $this->Flash->error(__('Please select a valid payment method.'));
                $this->set(compact('plan', 'currentUser'));
                return;
            }
            
            // Update subscription
            $currentUser->subscription_plan = $plan;
            $currentUser->subscription_status = 'pending';
            $currentUser->payment_method = $paymentMethod;
            $currentUser->subscription_started_at = new \DateTime();
            
            // Set expiration date (30 days for pro)
            if ($plan === 'pro') {
                $expiresAt = new \DateTime();
                $expiresAt->modify('+30 days');
                $currentUser->subscription_expires_at = $expiresAt;
            }
            
            if ($usersTable->save($currentUser)) {
                // Bank transfer only - PayPal is handled via AJAX
                if ($paymentMethod === 'bank_transfer') {
                    $this->Flash->success(__('Your subscription upgrade request has been received. Please complete the payment.'));
                    $this->Flash->info(__('Please transfer the amount to our bank account. Details have been sent to your email.'));
                    return $this->redirect(['action' => 'index']);
                }
            }
            
            $this->Flash->error(__('Could not process subscription upgrade. Please try again.'));
        }
        
        // Get PayPal Client ID from environment
        $paypalClientId = env('PAYPAL_CLIENT_ID', 'test');
        
        $this->set(compact('plan', 'currentUser', 'paypalClientId'));
    }
    
    /**
     * Handle PayPal payment success
     *
     * @return \Cake\Http\Response|null|void
     */
    public function paypalSuccess()
    {
        $this->request->allowMethod(['post']);
        $this->autoRender = false;
        
        $user = $this->Authentication->getIdentity();
        if (!$user) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode(['success' => false, 'error' => 'Not authenticated']));
        }
        
        $data = $this->request->getData();
        $orderID = $data['orderID'] ?? null;
        $plan = $data['plan'] ?? null;
        $details = $data['details'] ?? [];
        
        if (!$orderID || !$plan) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode(['success' => false, 'error' => 'Missing data']));
        }
        
        // Update user subscription
        $usersTable = $this->fetchTable('Users');
        $currentUser = $usersTable->get($user->id);
        
        $currentUser->subscription_plan = $plan;
        $currentUser->subscription_status = 'active';
        $currentUser->payment_method = 'paypal';
        $currentUser->subscription_started_at = new \DateTime();
        
        // Set expiration date (30 days for pro)
        if ($plan === 'pro') {
            $expiresAt = new \DateTime();
            $expiresAt->modify('+30 days');
            $currentUser->subscription_expires_at = $expiresAt;
        }
        
        if ($usersTable->save($currentUser)) {
            // Log the transaction
            $this->log('PayPal payment successful: Order ' . $orderID . ' for user ' . $user->id, 'info');
            
            return $this->response->withType('application/json')
                ->withStringBody(json_encode(['success' => true]));
        }
        
        return $this->response->withType('application/json')
            ->withStringBody(json_encode(['success' => false, 'error' => 'Failed to update subscription']));
    }
    
    /**
     * Cancel subscription
     *
     * @return \Cake\Http\Response|null|void
     */
    public function cancel()
    {
        $this->request->allowMethod(['post']);
        
        $user = $this->Authentication->getIdentity();
        if (!$user) {
            return $this->redirect(['controller' => 'Users', 'action' => 'login']);
        }
        
        $usersTable = $this->fetchTable('Users');
        $currentUser = $usersTable->get($user->id);
        
        // Cannot cancel test plan
        if ($currentUser->subscription_plan === 'test') {
            $this->Flash->error(__('You are already on the free test plan.'));
            return $this->redirect(['action' => 'index']);
        }
        
        // Downgrade to test plan
        $currentUser->subscription_plan = 'test';
        $currentUser->subscription_status = 'active';
        $currentUser->payment_method = null;
        $currentUser->subscription_expires_at = null;
        
        if ($usersTable->save($currentUser)) {
            $this->Flash->success(__('Your subscription has been cancelled. You are now on the free test plan.'));
        } else {
            $this->Flash->error(__('Could not cancel subscription. Please try again.'));
        }
        
        return $this->redirect(['action' => 'index']);
    }
}
