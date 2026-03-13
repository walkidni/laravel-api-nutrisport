# Reporting

## Scope

The reporting feature covers one scheduled daily administrator email:

- one plain-text French daily report email
- one recipient from `config('reporting.administrator_email')`
- one `J-1` reporting window based on the Laravel app timezone
- one scheduler registration at midnight through Laravel's built-in scheduler

This slice does not include:

- HTTP reporting endpoints
- report persistence/history tables
- queued delivery
- multiple recipients

## Runtime flow

The midnight scheduler entry in `bootstrap/app.php` runs `App\Domain\Reporting\Jobs\SendDailyReportJob`.

`SendDailyReportJob`:

- runs synchronously and does not implement `ShouldQueue`
- resolves `BuildDailyReportAction`
- builds the previous calendar day's report
- sends `DailyReportMail` to `config('reporting.administrator_email')`

## Report content

The daily email is plain text and sectioned in French.

Current sections:

- `Date`
- `Produit le plus vendu`
- `Produit le moins vendu`
- `Produit au CA maximum`
- `Produit au CA minimum`
- `CA par site`

The heading uses the concrete report date, for example `Rapport du 11 mars 2026`.

Empty-day behavior is explicit rather than silent:

- product metrics render `aucun`
- every known site is still listed under `CA par site`
- zero-turnover sites render as `0.00`

## Data assembly

Reporting code lives under `app/Domain/Reporting` and is split into:

- `Services/DailyReportingWindowFactory.php` for the `J-1` time window
- `Queries/` for quantity, turnover, and per-site aggregates
- `Actions/BuildDailyReportAction.php` for report assembly
- `Mail/DailyReportMail.php` for the plain-text mail contract
- `Jobs/SendDailyReportJob.php` for scheduled delivery

Money formatting in the mail uses the shared `MoneyFormatterService`.

## Test coverage

Primary reporting coverage currently lives in:

- `tests/Feature/Reporting/BuildDailyReportActionTest.php`
- `tests/Feature/Reporting/SendDailyReportJobTest.php`
- `tests/Feature/Reporting/ScheduleDailyReportTest.php`

These tests cover:

- previous-calendar-day window behavior
- aggregate correctness
- empty-day behavior
- mail delivery to the configured administrator
- midnight scheduler registration
