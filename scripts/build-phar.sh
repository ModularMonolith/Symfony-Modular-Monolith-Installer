#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${ROOT_DIR}"

BOX_PHAR="${ROOT_DIR}/.tools/box.phar"
BOX_VERSION="4.7.0"
INSTALLER_VERSION="${INSTALLER_VERSION:-dev}"

generate_box_config() {
    php -r '
        $configPath = "box.json.dist";
        $outputPath = "box.json";
        $version = getenv("INSTALLER_VERSION") ?: "dev";
        $config = json_decode(file_get_contents($configPath), true, 512, JSON_THROW_ON_ERROR);
        $config["replacements"]["git-version"] = $version;
        file_put_contents(
            $outputPath,
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        );
    '
}

run_box() {
    if command -v box >/dev/null 2>&1; then
        box "$@"
        return
    fi

    if [[ ! -f "${BOX_PHAR}" ]]; then
        mkdir -p "$(dirname "${BOX_PHAR}")"
        curl -fsSL \
            -o "${BOX_PHAR}" \
            "https://github.com/box-project/box/releases/download/${BOX_VERSION}/box.phar"
    fi

    php "${BOX_PHAR}" "$@"
}

composer install \
    --no-dev \
    --prefer-dist \
    --no-progress \
    --no-interaction \
    --optimize-autoloader

rm -rf build
mkdir -p build

generate_box_config

run_box compile --config=box.json

if [[ ! -f build/modular-monolith.phar ]]; then
    echo "PHAR was not created at build/modular-monolith.phar" >&2
    exit 1
fi

chmod +x build/modular-monolith.phar

php build/modular-monolith.phar list >/dev/null

echo "Built ${ROOT_DIR}/build/modular-monolith.phar"
