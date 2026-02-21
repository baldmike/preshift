#!/usr/bin/env bash
#
# merge.sh — Run tests, merge the current branch's PR into develop, sync local.
#
# Usage: ./scripts/merge.sh
#
# Steps:
#   1. Run backend tests (php artisan test)
#   2. Run frontend tests (npx vitest run)
#   3. Merge the open PR for the current branch via gh
#   4. Checkout develop and pull
#   5. Delete the merged feature branch locally and remotely

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BRANCH=$(git -C "$ROOT" branch --show-current)

if [[ "$BRANCH" == "main" || "$BRANCH" == "develop" ]]; then
  echo "ERROR: You're on '$BRANCH'. Switch to a feature branch first."
  exit 1
fi

echo "═══ Branch: $BRANCH ═══"
echo ""

# Step 1: Backend tests
echo "── Step 1/5: Backend tests ──"
cd "$ROOT/api"
php artisan test || { echo "FAILED: Backend tests did not pass."; exit 1; }
echo ""

# Step 2: Frontend tests
echo "── Step 2/5: Frontend tests ──"
cd "$ROOT/client"
npx vitest run || { echo "FAILED: Frontend tests did not pass."; exit 1; }
echo ""

# Step 3: Push & merge PR
echo "── Step 3/5: Push & merge PR ──"
cd "$ROOT"
git push 2>/dev/null || true
gh pr merge --merge || { echo "FAILED: Could not merge PR."; exit 1; }
echo ""

# Step 4: Sync develop
echo "── Step 4/5: Sync develop ──"
git checkout develop
git pull origin develop
echo ""

# Step 5: Clean up feature branch
echo "── Step 5/5: Clean up $BRANCH ──"
git branch -d "$BRANCH" 2>/dev/null || true
git push origin --delete "$BRANCH" 2>/dev/null || true

echo ""
echo "═══ Merged $BRANCH → develop ═══"
