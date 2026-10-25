# Changelog

This changelog covers only the two most recent **published** releases of `local_xpcelebration`, verified on October 24, 2026 (America/Sao_Paulo). Each entry reflects the changes between a published Git release tag and the immediately preceding published release tag. No `1.0.5` release appears between published releases `1.0.4` and `1.0.6`. The October 20 `Build` and `-core_text` commits were made after `1.0.7` and are intentionally excluded.

## [1.0.7] - 2026-10-15

### CI maintenance
- Updated the PostgreSQL CI service image from version 16 to 17.
- Updated the MariaDB CI service image from version 10 to 11.4, replaced the previous environment settings with `MARIADB_ALLOW_EMPTY_ROOT_PASSWORD`, and updated the health check to `healthcheck.sh --connect --innodb_initialized`.

### Hook and release maintenance
- Imported `core\hook\output\before_standard_head_html_generation` explicitly in `classes/hook_callbacks.php`, using the imported class in the callback signature and PHPDoc. This is a type-reference/style cleanup, not a change to the hook's behavior.
- Updated `version.php` from internal version `2026101200`, release `1.0.6`, to internal version `2026101500`, release `1.0.7`.

**Full comparison:** [1.0.6...1.0.7](https://github.com/EduardoKrausME/moodle-local_xpcelebration/compare/1.0.6...1.0.7)

## [1.0.6] - 2026-10-12

### Fixed
- Prevented the `before_standard_head_html_generation` callback from checking `local/xpcelebration:view` until that capability is registered. The hook can be called on Moodle installation and upgrade screens, so this avoids failures before `db/access.php` registration has completed.

### CI, release security and assets
- Updated the PHP 8.3 CI test matrix from `MOODLE_502_STABLE` to `MOODLE_503_STABLE` (Moodle 5.3).
- Added an explicit CI checkout of the `moodle-local_personalxp` dependency into `extra-plugins/personalxp`, and supplied `--extra-plugins ./extra-plugins` to the Moodle plugin CI installer.
- Changed the historical release workflow to supply the triggering commit message through a `RELEASE_MESSAGE` environment variable and parse that variable in the shell script, avoiding direct interpolation of untrusted commit text into shell commands.
- Added the plugin icon asset `pix/icon.png`.

### Release metadata
- Updated `version.php` from internal version `2026100503`, release `1.0.4`, to internal version `2026101200`, release `1.0.6`.

**Full comparison:** [1.0.4...1.0.6](https://github.com/EduardoKrausME/moodle-local_xpcelebration/compare/1.0.4...1.0.6)
