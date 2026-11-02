# Hostinger Deployment Checklist

## Before deployment
- Backup the entire current `public_html` directory.
- Confirm HTTPS is active and the domain certificate is valid.
- Replace sample products or remove shop publishing if the catalogue is not verified.
- Replace placeholder delivery fees in `includes/config.php`.
- Review privacy, terms and delivery/returns copy.
- Confirm PHP version 8.1 or newer.

## Deploy only the shop
Upload this new directory from Git into:

`public_html/shop`

Do not upload or replace the rest of `public_html`. The existing site uses `/assets`, and the only intended shared edit is the Shop link in `assets/js/site.js`.

Set `public_html/shop/data` writable only to the PHP process if Hostinger permits it. Do not make it publicly writable. Keep `.htaccess` protection. Confirm server logs are not placed under the public web root.

## Verification
- Open `/shop/`
- Test `/index.html`
- Test desktop and mobile Shop navigation
- Test listing, search, category filter and sorting
- Add/edit/remove cart items
- Test quantity limits and zero quantity removal
- Submit an invalid and valid checkout
- Confirm an order is written and displayed as `pending_payment`
- Confirm `/shop/data/orders.json` is not accessible directly
- Confirm payment credentials are absent from browser source

## Payment
Do not enable Pesapal until credentials are available through Hostinger environment configuration or a protected server file. Use test/sandbox mode first.

## Rollback
1. Stop serving `/shop/` by renaming or removing the deployed `shop` directory.
2. Restore the existing site backup.
3. Revert only `assets/js/site.js` if the shared Shop navigation must be removed.
4. No existing page or database needs deletion.

## Updating from Git
On the development machine:

```bash
git pull origin main
```

Review changes, test locally, then upload only `shop/` and, if intended, the single navigation script change. In Hostinger Git Deployment, exclude secrets and review deployment previews before publishing.
