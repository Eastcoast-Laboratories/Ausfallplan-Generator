# Git Replace in History Script

Script to remove sensitive data (passwords, API keys, etc.) from git commit messages.

## ⚠️ Warning

This script **rewrites git history**! Use with caution.

- Requires force push to remote
- Other developers need to re-clone or reset their local repos
- GitHub may cache old commit messages for up to 24 hours

## Usage

### Basic Usage

```bash
# Replace a word with default [REDACTED]
./dev/git-replace-in-history.sh "password123"

# Replace with custom text
./dev/git-replace-in-history.sh "secret-key" "***REMOVED***"
./dev/git-replace-in-history.sh "api-key-abc" "[CENSORED]"
```

### Examples

**Remove password from commit message:**
```bash
./dev/git-replace-in-history.sh "mySecretPass123" "[REDACTED]"
```

**Remove API key:**
```bash
./dev/git-replace-in-history.sh "sk-abc123xyz789" "***API-KEY-REMOVED***"
```

**Remove email address:**
```bash
./dev/git-replace-in-history.sh "private@email.com" "[EMAIL-REDACTED]"
```

## How It Works

1. **Search:** Finds all commits containing the search word
2. **Confirm:** Shows affected commits and asks for confirmation
3. **Backup:** Creates a backup branch automatically
4. **Replace:** Uses `git filter-branch` to rewrite commit messages
5. **Verify:** Checks that replacement was successful

## What Happens

### Before
```
commit abc123
    feat: Add login with password mySecretPass123
```

### After
```
commit def456  # New commit hash!
    feat: Add login with password [REDACTED]
```

## Scope

By default, the script only rewrites the **last 200 commits** (HEAD~200..HEAD) for safety and speed.

To rewrite **ALL history**, edit the script and change:
```bash
git filter-branch -f --msg-filter "sed 's/${SEARCH_WORD}/${REPLACEMENT}/g'" HEAD~200..HEAD
```
to:
```bash
git filter-branch -f --msg-filter "sed 's/${SEARCH_WORD}/${REPLACEMENT}/g'" -- --all
```

## After Running

### 1. Verify Changes
```bash
# Check that word is gone
git log --all --grep="password123"

# Check that replacement exists
git log --all --grep="REDACTED"
```

### 2. Force Push
```bash
git push origin main --force
```

### 3. If Something Went Wrong
```bash
# Restore from automatic backup
git reset --hard backup-before-filter-YYYYMMDD-HHMMSS
```

## Important Notes

### ✅ What This Script Does
- Removes sensitive data from **commit messages**
- Creates automatic backup branch
- Shows preview before executing
- Verifies replacement was successful

### ❌ What This Script Does NOT Do
- Does **not** remove data from **file contents**
- Does **not** remove data from **file history**
- Does **not** automatically push changes

### For Removing Files from History

If you need to remove sensitive data from **file contents** (not just commit messages), use:

```bash
# Using BFG Repo-Cleaner (recommended)
bfg --delete-files passwords.txt
bfg --replace-text passwords.txt

# Or git-filter-repo
git filter-repo --path-glob 'config/secrets.yml' --invert-paths
```

## Security Best Practices

1. **Change the compromised credential immediately**
   - Even after removing from git history, assume it's compromised
   
2. **Rotate API keys and passwords**
   - Don't just hide them, replace them
   
3. **Use environment variables**
   - Never commit secrets to git
   - Use `.env` files (and add to `.gitignore`)
   
4. **Use secret management tools**
   - HashiCorp Vault
   - AWS Secrets Manager
   - GitHub Secrets (for CI/CD)

## Troubleshooting

### "stale info" error when pushing
```bash
git fetch origin
git push origin main --force  # Use --force instead of --force-with-lease
```

### Word still appears in old commits
The script only rewrites HEAD~200..HEAD by default. To rewrite all history:
```bash
git filter-branch -f --msg-filter "sed 's/WORD/REPLACEMENT/g'" -- --all
```

### Need to undo changes
```bash
# Find your backup branch
git branch | grep backup-before-filter

# Reset to backup
git reset --hard backup-before-filter-20250105-093000
```

## See Also

- [BFG Repo-Cleaner](https://rtyley.github.io/bfg-repo-cleaner/)
- [git-filter-repo](https://github.com/newren/git-filter-repo)
- [GitHub: Removing sensitive data](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository)
