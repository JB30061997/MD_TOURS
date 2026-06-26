# TourOps Manager Marketing Screens

This folder contains clean demo/marketing screens for promoting a tourism operations platform to agencies, transport companies and tour operators.

## Privacy

All data in these screens is fake/demo data.

The screens do not use:

- Real MD Tours data
- The MD Tours logo
- Real client names
- Real phone numbers
- Real emails
- Real prices
- Real addresses
- Real dossier references
- Private production information

Branding is replaced with the generic product name `TourOps Manager`.

## Recreated Demo Screens

- Cover / product overview
- Dashboard KPIs and analytics
- Planning list
- Dossier details
- Drivers, guides and vehicles
- Clients and suppliers
- Reservations portal
- Reports and statistics
- Mobile driver road sheet screens

## Colors

The demo branding uses a separate palette:

- Primary: `#2563EB`
- Secondary: `#0F172A`
- Accent: `#F59E0B`
- Background: `#F8FAFC`

## How To Open

Open this file in a browser:

```bash
open marketing-screens/index.html
```

Or from this folder:

```bash
open index.html
```

## Export PNG Screenshots

The `exports` folder is prepared for generated PNG files.

Recommended formats:

- Square post: `1080x1080`
- Story/Reel: `1080x1920`
- Wide post: `1200x628`

To export automatically, install Playwright in the project if it is not already installed:

```bash
npm install --save-dev playwright
npx playwright install chromium
```

Then run:

```bash
node marketing-screens/export-screens.mjs
```

Generated files will be saved in:

```text
marketing-screens/exports
```

Each exported image includes the watermark:

```text
Demo interface - No real client data
```
