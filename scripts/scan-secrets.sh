#!/usr/bin/env bash
#
# Scans every revision in this repository for credentials.
#
# The pre-commit hook guards the next commit; this guards the ones already made.
# A secret removed by a later commit is still readable in the earlier one, so a
# clean working tree says nothing about the history.
#
# There is no gitleaks or trufflehog on this machine, and installing one to run
# it once is a poor trade. `git grep` over every commit reaches the same blobs,
# including files that were later deleted, because a deleted file still lives in
# the tree of the commit that had it.
#
# Deployment host names and logins are not written here: like the pre-commit
# hook, this script reads them from ~/private/git-forbidden-strings.txt, so the
# scanner cannot leak what it looks for.
#
# Usage: bash scripts/scan-secrets.sh
# Exit:  0 clean, 1 something to look at.

set -uo pipefail

cd "$(git rev-parse --show-toplevel)" || exit 1

# WordPress core and third-party plugins are excluded. They ship their own
# example credentials (wp-config-sample.php alone defines eight salts), which
# would bury a real finding in noise. Everything written here is in scope.
excludes=(
	':(exclude)wp-admin/*'
	':(exclude)wp-includes/*'
	':(exclude)wp-content/plugins/litespeed-cache/*'
	':(exclude)wp-content/plugins/wordpress-seo/*'
	':(exclude)wp-content/plugins/polylang/*'
	':(exclude)wp-content/plugins/instagram-feed/*'
	':(exclude)wp-content/themes/twenty*'
	':(exclude)*.min.js'
	':(exclude)*.min.css'
	':(exclude)*.map'
	':(exclude)package-lock.json'
)

# Shapes that are credentials wherever they appear.
patterns=(
	'BEGIN (RSA|OPENSSH|DSA|EC|PGP) PRIVATE KEY'
	'AKIA[0-9A-Z]{16}'
	'gh[pousr]_[A-Za-z0-9]{36}'
	'xox[baprs]-[0-9A-Za-z-]{10,}'
	'sk-[A-Za-z0-9]{32,}'
	'[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}:fx' # DeepL
	"define\\(\\s*['\"](DB_PASSWORD|DB_USER|DB_NAME|DB_HOST)['\"]\\s*,\\s*['\"][^'\"]+['\"]"
	"(password|passwd|api[_-]?key|secret|auth[_-]?token)['\"]?\\s*[:=]\\s*['\"][^'\"[:space:]]{8,}['\"]"
)

forbidden_file="$HOME/private/git-forbidden-strings.txt"
if [ -r "$forbidden_file" ]; then
	while IFS= read -r line; do
		[ -z "$line" ] && continue
		case "$line" in \#*) continue ;; esac
		patterns+=("$line")
	done <"$forbidden_file"
	echo "Patterns: ${#patterns[@]} (including the deployment strings from ~/private/)."
else
	echo "Patterns: ${#patterns[@]}. WARNING: $forbidden_file is unreadable," >&2
	echo "so the deployment host, login and path are NOT being looked for." >&2
fi

revs=$(git rev-list --all)
echo "Revisions: $(wc -w <<<"$revs")."
echo

grep_args=()
for pattern in "${patterns[@]}"; do
	grep_args+=(-e "$pattern")
done

status=0

echo "=== Content of every revision ==="
# -I skips binaries, so images and fonts cost nothing.
if hits=$(git grep -nIE --no-color "${grep_args[@]}" $revs -- "${excludes[@]}" 2>/dev/null); then
	# One blob usually appears in many commits. Report the path and the line
	# once, with a count, or a single finding fills the screen.
	printf '%s\n' "$hits" | awk -F: '{ rest = $2; for (i = 3; i <= NF; i++) rest = rest ":" $i; count[rest]++ }
		END { for (r in count) printf "  %4d rev(s)  %s\n", count[r], r }' | sort -k2
	status=1
else
	echo "  Nothing."
fi

echo
echo "=== Files that ever existed, by risky name ==="
risky='(^|/)(wp-config\.php|\.env|\.env\..*|.*\.sql|.*\.sql\.gz|.*\.sqlite|.*\.pem|.*\.key|.*\.p12|.*\.pfx|id_rsa.*|id_ed25519.*|.*\.bak|.*backup.*|.*dump.*|connection-details.*)$'
if names=$(git log --all --pretty=format: --name-only --diff-filter=A | sort -u | grep -vE '^$' | grep -iE "$risky"); then
	printf '%s\n' "$names" | sed 's/^/  /'
	status=1
else
	echo "  None."
fi

echo
if [ "$status" -eq 0 ]; then
	echo "Clean: no secret pattern and no risky filename in any revision."
else
	echo "Review the findings above. A real secret means rotate the credential FIRST;"
	echo "rewriting the history afterwards does not un-publish what was fetched."
fi
exit "$status"
