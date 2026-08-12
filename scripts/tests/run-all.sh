#!/usr/bin/env bash
#
# Runs every PHP test in this directory and reports the result as a whole.
#
# The tests are piped through stdin because host paths do not exist inside the DDEV
# container — `ddev wp eval-file <path>` fails with "does not exist".
#
# Usage:
#   scripts/tests/run-all.sh
#
set -uo pipefail

cd "$(dirname "$0")/../.." || exit 1

pass=0
fail=0

for t in scripts/tests/kzt-*.php; do
	name="$(basename "$t" .php)"
	out="$(ddev wp eval-file - < "$t" 2>&1 | grep -vE 'Include of|^ Container|^Building|^Waiting|^Starting')"

	if printf '%s' "$out" | grep -q '^PASS'; then
		printf '  PASS  %-16s %s\n' "$name" "$(printf '%s' "$out" | grep -oE '^PASS.*' | cut -c7-)"
		pass=$((pass + 1))
	else
		printf '  FAIL  %s\n' "$name"
		printf '%s\n' "$out" | sed 's/^/          /'
		fail=$((fail + 1))
	fi
done

printf '\n  %d/%d passed\n' "$pass" "$((pass + fail))"
[ "$fail" -eq 0 ] || exit 1
