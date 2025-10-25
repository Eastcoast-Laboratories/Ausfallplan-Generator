#!/bin/bash

# Fix all test files with old user schema patterns

# List of test files to fix
FILES=(
  "tests/TestCase/Integration/NavigationVisibilityTest.php"
  "tests/TestCase/Controller/SchedulesControllerPermissionsTest.php"
  "tests/TestCase/Controller/UsersControllerTest.php"
  "tests/TestCase/Controller/SchedulesControllerTest.php"
  "tests/TestCase/Controller/SchedulesControllerCapacityTest.php"
  "tests/TestCase/Controller/SiblingGroupsControllerTest.php"
  "tests/TestCase/Controller/Admin/SchedulesAccessTest.php"
  "tests/TestCase/Controller/ChildrenControllerTest.php"
  "tests/TestCase/View/AuthenticatedLayoutTest.php"
)

for file in "${FILES[@]}"; do
  if [ -f "$file" ]; then
    echo "Fixing $file..."
    
    # Add OrganizationUsers fixture if not present
    if ! grep -q "app.OrganizationUsers" "$file"; then
      sed -i "/protected array \$fixtures = \[/a\        'app.OrganizationUsers'," "$file"
    fi
    
    echo "  ✓ Fixed $file"
  fi
done

echo "Done!"
