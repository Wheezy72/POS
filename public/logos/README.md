# Product / brand logos

The POS product grid (`resources/js/Pages/PosTerminal.vue`) loads brand and product
images from this directory at runtime. Files are referenced by their public URL path,
e.g. `/logos/coca-cola.svg`.

## How it works

- Each mock product has a `logo_url` pointing at a file in this folder.
- If the file is missing or fails to load, the UI falls back to a category-coloured
  Lucide icon.
- Real products returned from `/api/pos/search` can carry a `logo_url` field too.

## Replacing placeholders with the real brand marks

1. Drop the official PNG/SVG/WebP into `/public/logos/` using the same filename, e.g.
   replace `coca-cola.svg` with the official Coca-Cola logo at the same path.
2. Hard-refresh the POS terminal — Vite serves `/public/*` directly so no rebuild is
   needed.
3. Keep filenames lowercase and hyphenated (`brookside-milk.svg`, not `BrookSide.SVG`).

## Licence note

Brand logos are generally trademark-protected. Make sure your store has the right to
display each brand mark before shipping a build with real logos. The `.svg` files in
this repo are intentionally generic placeholders so they ship safely.
