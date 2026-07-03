# MobilityCloud email setup

MobilityCloud starts with a compact set of real inboxes and uses the support
address for transactional email until volume justifies a dedicated sender.

## Recommended addresses

- `darius@mobilitycloud.eu` — owner account and direct founder inbox.
- `contact@mobilitycloud.eu` — public/general contact inbox.
- `support@mobilitycloud.eu` — customer support inbox, reply-to address and initial transactional sender.
- `billing@mobilitycloud.eu` — billing and subscription inbox.

## Business inboxes

Use Zoho Mail for real inboxes: `darius@`, `contact@`, `support@` and
`billing@`. Configure the MX, SPF, DKIM and DMARC records provided by Zoho in
GoDaddy DNS.

## Transactional email

Use Resend for application emails such as password resets, workspace invitations
and account notifications. At launch, send them from `support@mobilitycloud.eu`
to keep the setup simple. Later, we can split transactional messages into
`noreply@mobilitycloud.eu` if volume grows.

Production `.env` values:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=tls
MAIL_HOST=smtp.resend.com
MAIL_PORT=587
MAIL_USERNAME=resend
MAIL_PASSWORD=your-resend-api-key
MAIL_FROM_ADDRESS=support@mobilitycloud.eu
MAIL_FROM_NAME=MobilityCloud
MAIL_REPLY_TO_ADDRESS=support@mobilitycloud.eu
MAIL_REPLY_TO_NAME="MobilityCloud Support"

MOBILITYCLOUD_OWNER_EMAIL=darius@mobilitycloud.eu
MOBILITYCLOUD_CONTACT_EMAIL=contact@mobilitycloud.eu
MOBILITYCLOUD_SUPPORT_EMAIL=support@mobilitycloud.eu
MOBILITYCLOUD_BILLING_EMAIL=billing@mobilitycloud.eu
```

After editing `.env` on production, run:

```bash
sudo -u deploy php artisan optimize:clear
sudo -u deploy php artisan config:cache
```

## DNS checklist

1. In Resend, add and verify `mobilitycloud.eu`.
2. Add the Resend SPF/DKIM records in GoDaddy.
3. In Zoho Mail, add `mobilitycloud.eu` and create the inboxes.
4. Add the Zoho MX/SPF/DKIM records in GoDaddy.
5. Keep one DMARC record only, for example:

```dns
_dmarc TXT v=DMARC1; p=quarantine; adkim=s; aspf=s; rua=mailto:support@mobilitycloud.eu
```

6. Send a test password-reset email and a workspace invitation.

Do not host inbound mail directly on the application server unless there is a
specific operational reason. Mail deliverability is safer with a dedicated email
provider.
