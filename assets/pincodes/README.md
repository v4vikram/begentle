# India PIN code data

`NN.json` files map a 6-digit PIN (grouped by its first two digits) to `[city, WooCommerce state code]`.
Used by `assets/js/pincode.js` to fill City and State at checkout.

- **Source:** All India Pincode Directory, India Post, published on data.gov.in (snapshot dated 30 May 2019).
- **Licence:** Government Open Data License – India (GODL). Attribution: "Contains data from India Post / data.gov.in".
- **City** is the PIN's district (India Post naming), so a few read differently from everyday use (e.g. Noida → "Gautam Buddha Nagar").
- PINs created after the snapshot are not included; customers fill City/State by hand for those.

Rebuild from a newer CSV:

```
node tools/build-pincodes.mjs path/to/pincode.csv
```
