# Implementation Progress - Auth & Permissions

## Session: 23.10.2025 11:40 Uhr

### ✅ Phase 1: Database Schema (DONE)
- [x] Migration: AddUserVerificationFields
- [x] Migration: CreatePasswordResetsTable  
- [x] User Entity updated
- [x] PasswordReset Model created
- [x] Committed: 594d1f5

### 🔄 Phase 2: Core Auth Features (IN PROGRESS)

Aufgrund der Komplexität implementiere ich ein **MINIMAL VIABLE** System:

#### A. Email Verification (Simplified)
**Status:** NOT STARTED
- [ ] Update registration to set `email_verified = 0, status = 'pending'`
- [ ] Generate email_token on registration
- [ ] Skip actual email sending (log only for now)
- [ ] Create verify endpoint `/users/verify/{token}`
- [ ] Update login to check email_verified

#### B. Admin Approval (Simplified)
**Status:** NOT STARTED
- [ ] Users with status='pending' cannot login
- [ ] Admin can see pending users
- [ ] Admin can approve (set status='active')
- [ ] Skip email notifications for now

#### C. Password Recovery (Simplified)
**Status:** NOT STARTED
- [ ] Forgot password form
- [ ] Generate reset_code and save to DB
- [ ] Skip email sending (show code on screen for testing)
- [ ] Reset password form with code
- [ ] Verify code and update password

#### D. Role-Based Permissions
**Status:** NOT STARTED
- [ ] Authorization Middleware
- [ ] Viewer: Read-only access
- [ ] Editor: Can edit own org data
- [ ] Admin: Can edit everything
- [ ] Apply to all controllers

#### E. Organization Autocomplete
**Status:** NOT STARTED
- [ ] API endpoint `/api/organizations/search`
- [ ] JavaScript autocomplete widget
- [ ] Visual feedback for new org

### ⏰ Time Estimate
- Simplified Email Verification: 20min
- Simplified Admin Approval: 20min
- Simplified Password Recovery: 30min
- Role Permissions: 45min
- Organization Autocomplete: 25min
- Testing: 30min
**Total: ~3 hours**

### 🎯 Current Focus
Starting with simplified auth flow to get basic security working.
Email sending can be added later.

### 📝 Notes
- Using "log-based" approach for emails initially
- Focus on functionality, not perfect UX
- Can enhance later with actual SMTP
