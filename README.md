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
| `-n, --no-interaction` | Skip prompts; keep TodoList example and auto-generate `APP_SECRET` |

## What it does

1. Runs `composer create-project modular-monolith/symfony-application <directory>`
2. Optionally sets `APP_SECRET` in `.env`
3. Optionally removes the TodoList example module from `src/` and `tests/`

## Local development

```bash
composer install
./bin/modulith new /tmp/my-test-project
```

For building and releasing see [docs/releasing.md](docs/releasing.md).

## License

MIT © 2026 Florian Krämer
