#!/usr/bin/env bash
#
# Uruchamia wszystkie testy PHP silnika tlumaczacego i zbiorczo raportuje wynik.
#
# Testy podawane sa przez stdin, bo sciezki hosta nie istnieja w kontenerze DDEV
# — `ddev wp eval-file <sciezka>` konczy sie bledem "does not exist".
#
# Uzycie:
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

printf '\n  %d/%d przeszlo\n' "$pass" "$((pass + fail))"
[ "$fail" -eq 0 ] || exit 1
