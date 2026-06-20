#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

usage() {
    cat <<'EOF'
Publish a modular-monolith installer GitHub release with a PHAR asset.

Usage:
  publish-release.sh <version>
  publish-release.sh --from-tag <tag>

Examples:
  publish-release.sh 1.0.0
  publish-release.sh --from-tag installer-v1.0.0

Environment:
  GITHUB_TOKEN   Required for creating/updating the GitHub release
  GITHUB_REPOSITORY  Owner/repo (auto-detected in GitHub Actions)
EOF
}

resolve_version() {
    local input="${1}"

    if [[ "${input}" =~ ^installer-v([0-9]+\.[0-9]+\.[0-9]+.*)$ ]]; then
        echo "${BASH_REMATCH[1]}"
        return 0
    fi

    if [[ "${input}" =~ ^v?([0-9]+\.[0-9]+\.[0-9]+.*)$ ]]; then
        echo "${BASH_REMATCH[1]}"
        return 0
    fi

    echo "Unsupported version or tag format: ${input}" >&2
    exit 1
}

VERSION=""
FROM_TAG=""

while [[ $# -gt 0 ]]; do
    case "$1" in
        --from-tag)
            FROM_TAG="${2:-}"
            shift 2
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            if [[ -z "${VERSION}" ]]; then
                VERSION="$1"
                shift
            else
                echo "Unexpected argument: $1" >&2
                usage
                exit 1
            fi
            ;;
    esac
done

if [[ -n "${FROM_TAG}" ]]; then
    VERSION="$(resolve_version "${FROM_TAG}")"
elif [[ -z "${VERSION}" ]]; then
    if [[ -n "${GITHUB_REF_NAME:-}" ]]; then
        VERSION="$(resolve_version "${GITHUB_REF_NAME}")"
    else
        echo "Version or --from-tag is required." >&2
        usage
        exit 1
    fi
fi

TAG="installer-v${VERSION}"
RELEASE_TITLE="Installer v${VERSION}"
PHAR_PATH="${ROOT_DIR}/build/modular-monolith.phar"
ASSET_NAME="modular-monolith.phar"

echo "Publishing ${RELEASE_TITLE} (tag: ${TAG})"

INSTALLER_VERSION="${VERSION}" "${ROOT_DIR}/scripts/build-phar.sh"

if [[ -z "${GITHUB_TOKEN:-}" ]]; then
    echo "GITHUB_TOKEN is not set. PHAR built at ${PHAR_PATH}" >&2
    echo "Set GITHUB_TOKEN to upload the release asset." >&2
    exit 1
fi

if ! command -v gh >/dev/null 2>&1; then
    echo "GitHub CLI (gh) is required to create releases." >&2
    exit 1
fi

if [[ -z "${GITHUB_REPOSITORY:-}" ]]; then
    GITHUB_REPOSITORY="$(gh repo view --json nameWithOwner --jq .nameWithOwner)"
fi

cd "${ROOT_DIR}"

if gh release view "${TAG}" >/dev/null 2>&1; then
    gh release upload "${TAG}" "${PHAR_PATH}#${ASSET_NAME}" --clobber
    echo "Uploaded ${ASSET_NAME} to existing release ${TAG}"
else
    gh release create "${TAG}" \
        --repo "${GITHUB_REPOSITORY}" \
        --title "${RELEASE_TITLE}" \
        --notes "Modular Monolith installer PHAR release.

Install:
\`\`\`bash
curl -fsSL -o modular-monolith.phar \\
  https://github.com/${GITHUB_REPOSITORY}/releases/download/${TAG}/${ASSET_NAME}
chmod +x modular-monolith.phar
./modular-monolith.phar new my-project
\`\`\`

Or via Composer:
\`\`\`bash
composer global require modular-monolith/installer:${VERSION}
\`\`\`" \
        "${PHAR_PATH}#${ASSET_NAME}"
    echo "Created release ${TAG} with ${ASSET_NAME}"
fi
