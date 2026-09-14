Font files in this directory
============================

Source Serif 4 — Variable (weight axis)
  Files:   source-serif-4-latin-wght-normal.woff2
           source-serif-4-latin-ext-wght-normal.woff2
  Design:  Frank Grießhammer / Adobe
  Licence: SIL Open Font License 1.1
  Source:  https://github.com/adobe-fonts/source-serif

Source Sans 3 — Variable (weight axis)
  Files:   source-sans-3-latin-wght-normal.woff2
           source-sans-3-latin-ext-wght-normal.woff2
  Design:  Paul D. Hunt / Adobe
  Licence: SIL Open Font License 1.1
  Source:  https://github.com/adobe-fonts/source-sans

Both families are licensed under the SIL Open Font License, Version 1.1,
which permits redistribution and embedding in a website. The full licence
text is available at https://openfontlicense.org/.

Why these files
---------------
Two variable fonts cover every weight the design uses, so the site loads two
font files rather than the six to eight static weights an equivalent static
setup would need. Only the `latin` subsets are preloaded; `latin-ext` is
fetched by the browser on demand through unicode-range.

Do not add further families. Body reading comfort on a news site matters far
more than typographic variety.
