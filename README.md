# Modular Monolith Installer

Interactive CLI for creating projects from [`modular-monolith/symfony-application`](../).

## Install globally

```bash
composer global require modular-monolith/installer
```

Ensure Composer's global `bin` directory is on your `PATH`.

## Install PHAR (no Composer)

Download the PHAR from [GitHub Releases](https://github.com/ModularMonolith/Symfony-Application/releases):

```bash
curl -fsSL -o modulith.phar \
  https://github.com/ModularMonolith/Symfony-Application/releases/latest/download/modulith.phar
chmod +x modulith.phar
./modulith.phar new my-project
```

## Usage

```bash
modulith new my-project
```

Options:

| Option | Description |
|--------|-------------|
| `--repository=URL` | VCS URL when the template is not on Packagist (default: GitHub template repo) |
| `-n, --no-interaction` | Skip prompts; keep TodoList example, auto-generate `APP_SECRET`, use computed container prefix |

## Interactive prompts

The installer asks:

1. **Remove the TodoList example module?** — strips `src/Capability/TodoList` and `tests/Capability/TodoList`
2. **APP_SECRET** — leave empty to auto-generate a random value
3. **Docker container name prefix** — used as `COMPOSE_PROJECT_NAME` in `.env`; defaults to the directory name sanitized to lowercase alphanumeric-and-hyphens (e.g. `my-project` → `my-project`)

## What it does

1. Runs `composer create-project modular-monolith/symfony-application <directory>`
2. Sets `APP_SECRET` in `.env`
3. Sets `COMPOSE_PROJECT_NAME` in `.env` (controls Docker container name prefix)
4. Optionally removes the TodoList example module from `src/` and `tests/`

## Local development

```bash
composer install
./bin/modulith new /tmp/my-test-project
```

For building and releasing see [docs/releasing.md](docs/releasing.md).

## License

MIT © 2026 Florian Krämer
