# Implementation Status - TODO Features

## ✅ Already Implemented

### Database Schema
- ✅ `children.organization_id` - EXISTS
- ✅ `users.organization_id` - EXISTS  
- ✅ `users.role` (viewer/editor/admin) - EXISTS

### Basic Features
- ✅ User Registration with organization
- ✅ Role selection during registration

---

## ❌ NOT Implemented (Need to implement)

### 1. Organization Autocomplete ❌
**Requirement:** Bei Registration Organization-Namen vorschlagen
- [ ] API endpoint `/api/organizations/autocomplete`
- [ ] Frontend JavaScript autocomplete
- [ ] Visual indicator für neue vs. existierende Organization
- [ ] Hinweis "Neue Organisation erstellen"

### 2. Email Confirmation ❌
**Requirement:** Email-Bestätigung für neue User
- [ ] `users.email_verified` column
- [ ] `users.email_token` column
- [ ] Email template für Bestätigungs-Link
- [ ] Confirmation controller action
- [ ] Prevent login if email not verified

### 3. Admin Approval ❌
**Requirement:** Admin muss neue User freischalten (bei existierender Org)
- [ ] `users.status` column (pending/active/inactive)
- [ ] `users.approved_at` column
- [ ] `users.approved_by` column
- [ ] Admin notification email when new user registers
- [ ] Admin interface to approve/reject users
- [ ] Prevent login if not approved

### 4. Password Recovery ❌
**Requirement:** Password reset mit Confirmation Code
- [ ] `password_resets` table
- [ ] Forgot password form
- [ ] Email mit Reset-Code
- [ ] Reset password form
- [ ] Code verification

### 5. Role-Based Permissions ❌
**Requirement:** Unterschiedliche Berechtigungen

**Viewer:**
- [ ] Can only VIEW data of own organization
- [ ] Cannot edit anything
- [ ] Middleware to enforce

**Editor:**
- [ ] Can VIEW & EDIT data of own organization
- [ ] Can add/edit/delete children of own org
- [ ] Cannot edit schedules/users
- [ ] Middleware to enforce

**Admin:**
- [ ] Can see ALL organizations
- [ ] Can edit EVERYTHING
- [ ] User management interface
- [ ] Can activate/deactivate users

### 6. User Management Interface ❌
**Requirement:** Admin can manage users
- [ ] List all users (admin only)
- [ ] Activate/Deactivate users
- [ ] Change user roles
- [ ] View pending approvals

---

## 🔄 Implementation Plan

### Phase 1: Database Schema (30 min)
1. Migration: Add email verification fields
2. Migration: Add user status fields
3. Migration: Create password_resets table

### Phase 2: Email System (60 min)
4. Setup email config
5. Create email templates
6. Implement confirmation system
7. Implement password reset

### Phase 3: Permissions (45 min)
8. Create authorization middleware
9. Implement viewer restrictions
10. Implement editor restrictions
11. Test all permission levels

### Phase 4: Organization Autocomplete (30 min)
12. API endpoint
13. Frontend autocomplete
14. Visual feedback

### Phase 5: Admin Interface (45 min)
15. User management controller
16. User list view
17. Approval actions

### Phase 6: Testing (60 min)
18. Unit tests for all features
19. Integration tests
20. E2E tests with Playwright

**Total Estimated Time:** ~4.5 hours
