# Individual Consent Workflow + Sharing

This merged feature keeps the template usage workflow and restores/retains the
individual-consent sharing panel.

## Individual consent flow

1. Open **Consent Records**.
2. Click **New Individual Consent**.
3. Select a published template enabled for **Individual consent**.
4. Enter the signer's details and optional signing deadline.
5. Create the consent.
6. On the consent record page, use **Copy link**, **Share**, **Email signer**,
   **Share on WhatsApp**, or **Open signing page**.

The sharing panel appears only for direct individual consents that are still
pending. It remains hidden for public signing-station records and completed,
cancelled, or expired records.

## Template workflows

Each template can be enabled for:

- Individual consent
- Public signing station
- Both workflows

The individual-consent template picker only lists published templates enabled
for individual consent. Signing-station forms only list templates enabled for
public signing stations.

## Install

Run the installer from the Laravel project root:

```bash
bash ~/Downloads/install_individual_consent_workflow_sharing.sh
php artisan migrate
```

## Public-link requirement

The WhatsApp link uses Laravel's `APP_URL`. For links shared to phones or other
computers, set `APP_URL` to the public HTTPS domain rather than localhost, then
run:

```bash
php artisan config:clear
```
