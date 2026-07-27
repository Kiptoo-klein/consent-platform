# Public station signer fields and inactivity timeout

This repair replaces the kiosk signer-details page with a complete form that
always displays the standard full-name and email inputs. Template-selected
fields appear directly below them and no empty additional-information panel is
rendered.

A two-minute inactivity timer is installed on:

- consent review,
- signer details,
- signature.

Typing, clicking, touching, scrolling, changing an input or drawing a signature
restarts the timer. When the timer expires, an unfinished public-station record
is cancelled where necessary and the shared device returns to the kiosk welcome
page.

The default can later be changed in `.env`:

```env
PUBLIC_STATION_INACTIVITY_TIMEOUT_SECONDS=120
```

No migration is required.

The normal public consent review page reached through the signature Back button
is also protected by the same timeout for signing-station records.
