# Kiosk Analytics and Abandoned-Session Reporting

This feature adds organization-scoped analytics for public signing stations.

## New reporting

- Total kiosk review flows.
- Completed consents.
- Explicit inactivity-timeout abandonments.
- Manual cancellations.
- Active and potentially stale flows.
- Completion and abandonment rates.
- Average time from review start to submission.
- Daily completed, abandoned, and cancelled trends.
- Abandonment stage: review, signer details, or signature.
- Per-station and per-template performance.
- Recent abandoned flows.
- CSV export using the selected date and station filters.

## Accurate abandonment tracking

A new `signing_station_flows` table follows the public kiosk journey independently from consent-record status.

The two-minute timeout now records:

- `review` when the person leaves the consent-review screen.
- `details` when the person leaves the name/email screen.
- `signing` when the person leaves an unfinished consent or signature screen.

A normal Cancel button is recorded as `cancelled`, not `abandoned`.

## Historical data

The migration backfills existing public-station consent records so past completed and cancelled records appear in totals. Existing cancellations cannot be reliably identified as old timeouts, so they remain classified as historical cancellations. Exact abandonment-stage reporting starts after this feature is installed.

## Routes

- `GET /signing-stations/analytics`
- `GET /signing-stations/analytics/export`

The routes are installed before `/signing-stations/{signingStation}` to prevent route-model binding conflicts.

## Install

Run the installer from the Laravel project root, then migrate:

```bash
bash ~/Downloads/install_kiosk_analytics_abandonment.sh
php artisan migrate
php artisan optimize:clear
```

No queue worker is required for analytics tracking.
