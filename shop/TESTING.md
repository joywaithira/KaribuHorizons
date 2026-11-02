# Shop Testing Checklist

- [ ] `/shop/` loads over HTTPS
- [ ] Existing `/index.html` loads unchanged
- [ ] Desktop navigation contains Shop
- [ ] Mobile drawer contains Shop
- [ ] Product listing loads
- [ ] Search finds matching products
- [ ] Category filtering works
- [ ] Price/newest sorting works
- [ ] Product details loads
- [ ] Add-to-cart works
- [ ] Cart item count updates
- [ ] Refresh retains session cart
- [ ] Quantity update works
- [ ] Remove item works
- [ ] Stock quantity cannot be exceeded
- [ ] Checkout rejects missing required fields
- [ ] Checkout rejects invalid email
- [ ] Checkout rejects invalid phone
- [ ] Address becomes required for local/courier delivery
- [ ] Pickup requires no address
- [ ] Delivery fee changes the total
- [ ] Browser-submitted prices do not change order totals
- [ ] Duplicate submission does not create a second order
- [ ] CSRF token is required for cart/checkout changes
- [ ] Sample order status is `pending_payment`
- [ ] Confirmation displays no claim that payment succeeded
- [ ] Confirmation does not expose another session's order
- [ ] Direct access to `data/orders.json` is denied
- [ ] No API key or password appears in page source
- [ ] PHP errors are hidden from visitors
- [ ] Layout works in iPhone Safari, iPad Safari, Android and desktop
- [ ] Touch controls are usable and keyboard navigation works
- [ ] JavaScript-disabled notice appears appropriately

## Safari/mobile test note
Verify in real device browsers where possible. Also test a throttled mobile network and confirm image loading does not block controls.
