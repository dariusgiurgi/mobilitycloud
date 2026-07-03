# MobilityCloud email setup

MobilityCloud currently uses one mailbox for every platform contact flow.
This keeps the launch setup cheap and simple.

## Recommended addresses

- `contact@mobilitycloud.eu` — owner account, public contact, support, billing and transactional sender.

## Business inboxes

Use GoDaddy email for the single mailbox `contact@mobilitycloud.eu`.
Configure the MX, SPF, DKIM and DMARC records provided by GoDaddy in DNS.

## Transactional email

Use the mailbox SMTP settings for application emails such as password resets,
workspace invitations and account notifications. Later, we can split this into
dedicated `@mobilitycloud.eu` inboxes or a transactional provider if volume grows.

Production `.env` values:

```dotenv
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtpout.secureserver.net
MAIL_PORT=587
MAIL_USERNAME=contact@mobilitycloud.eu
MAIL_PASSWORD=your-mailbox-password-or-app-password
MAIL_FROM_ADDRESS=contact@mobilitycloud.eu
MAIL_FROM_NAME=MobilityCloud
MAIL_REPLY_TO_ADDRESS=contact@mobilitycloud.eu
MAIL_REPLY_TO_NAME="MobilityCloud Support"

MOBILITYCLOUD_OWNER_EMAIL=contact@mobilitycloud.eu
MOBILITYCLOUD_CONTACT_EMAIL=contact@mobilitycloud.eu
MOBILITYCLOUD_SUPPORT_EMAIL=contact@mobilitycloud.eu
MOBILITYCLOUD_BILLING_EMAIL=contact@mobilitycloud.eu
```

After editing `.env` on production, run:

```bash
sudo -u deploy php artisan optimize:clear
sudo -u deploy php artisan config:cache
```

## DNS checklist

1. Create the `contact@mobilitycloud.eu` mailbox in GoDaddy.
2. Copy the GoDaddy SMTP host, port, username and password/app-password.
3. Add or confirm the GoDaddy MX/SPF/DKIM records for `xeotype.com`.
4. Keep one DMARC record only, for example:

```dns
_dmarc TXT v=DMARC1; p=quarantine; adkim=s; aspf=s; rua=mailto:contact@mobilitycloud.eu
```

5. Send a test password-reset email and a workspace invitation.

Do not host inbound mail directly on the application server unless there is a
specific operational reason. Mail deliverability is safer with a dedicated email
provider.
