#!/bin/bash

# Fix Online-Server User/Organization Problem
# Problem: "Sie müssen einer Organisation angehören, um Kinder zu erstellen."
# Lösung: Migrationen ausführen + fehlende OrganizationUser-Einträge erstellen

set -e  # Exit on error

echo "🔧 Fixing Online Server User/Organization Structure..."
echo ""

# 1. Check if we're in the right directory
if [ ! -f "bin/cake" ]; then
    echo "❌ Error: bin/cake not found. Are you in the project root?"
    exit 1
fi

echo "✅ Project directory found"
echo ""

# 2. Run migrations
echo "📦 Step 1: Running migrations..."
bin/cake migrations migrate
echo "✅ Migrations completed"
echo ""

# 3. Check migration status
echo "📋 Step 2: Checking migration status..."
bin/cake migrations status | tail -10
echo ""

# 4. Check if organization_users table exists
echo "🔍 Step 3: Checking if organization_users table exists..."
sqlite3 data/ausfallplan.db "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='organization_users';" > /tmp/table_check.txt
TABLE_COUNT=$(cat /tmp/table_check.txt)

if [ "$TABLE_COUNT" -eq "0" ]; then
    echo "❌ ERROR: organization_users table does NOT exist!"
    echo "   The migrations did not run correctly."
    echo "   Please check the migration files and database permissions."
    exit 1
fi

echo "✅ organization_users table exists"
echo ""

# 5. Check for users without organization_users entries
echo "🔍 Step 4: Checking for users without organization entries..."
ORPHAN_USERS=$(sqlite3 data/ausfallplan.db "
    SELECT COUNT(*) 
    FROM users u 
    LEFT JOIN organization_users ou ON u.id = ou.user_id 
    WHERE ou.id IS NULL;
")

echo "   Found $ORPHAN_USERS users without organization entries"
echo ""

if [ "$ORPHAN_USERS" -eq "0" ]; then
    echo "✅ All users have organization entries - nothing to fix!"
    echo ""
    echo "🎉 Everything is configured correctly!"
    exit 0
fi

# 6. Get organization ID (assume first organization)
ORG_ID=$(sqlite3 data/ausfallplan.db "SELECT id FROM organizations LIMIT 1;")

if [ -z "$ORG_ID" ]; then
    echo "❌ ERROR: No organizations found in database!"
    echo "   Please create an organization first."
    exit 1
fi

echo "   Using organization ID: $ORG_ID"
echo ""

# 7. Fix orphaned users
echo "🔧 Step 5: Creating organization_users entries for orphaned users..."

sqlite3 data/ausfallplan.db <<SQL
INSERT INTO organization_users (user_id, organization_id, role, is_primary, joined_at, created, modified)
SELECT 
    u.id as user_id,
    $ORG_ID as organization_id,
    CASE 
        WHEN u.is_system_admin = 1 THEN 'org_admin'
        ELSE 'editor'
    END as role,
    1 as is_primary,
    datetime('now') as joined_at,
    datetime('now') as created,
    datetime('now') as modified
FROM users u
LEFT JOIN organization_users ou ON u.id = ou.user_id
WHERE ou.id IS NULL;
SQL

echo "✅ Created organization_users entries"
echo ""

# 8. Verify fix
echo "📋 Step 6: Verifying fix..."
echo ""
echo "Users with organization entries:"
sqlite3 -header -column data/ausfallplan.db "
    SELECT 
        u.id,
        u.email,
        u.is_system_admin,
        ou.role,
        o.name as organization
    FROM users u
    LEFT JOIN organization_users ou ON u.id = ou.user_id
    LEFT JOIN organizations o ON ou.organization_id = o.id
    ORDER BY u.email;
"
echo ""

# 9. Clear cache
echo "🗑️  Step 7: Clearing cache..."
bin/cake cache clear_all
echo "✅ Cache cleared"
echo ""

echo "🎉 Fix completed successfully!"
echo ""
echo "Next steps:"
echo "1. Test login as ruben.barkow@eclabs.de"
echo "2. Try to add a child"
echo "3. Should work now!"
