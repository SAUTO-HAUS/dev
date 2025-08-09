# Telegram Advertising Landing Page

## Route and Purpose
- Accessible at `/[lang]/telegram_adv`.
- Presents a single-screen landing page promoting the Auto Moldova Telegram channel for advertising purposes.

## Managing Languages and Texts
- Localized strings are defined in `content/site/page/new_pages/telegram_adv/lang_tel.php`.
- The template loads this file into a single `$telegram_lang` array before any output.
- It reads the locale from the URL (`/ro`, `/ru`, `/en`) and uses that block from the array.
- If a key is missing, the page falls back in the order `ru → ro → en`.
- Each language block contains `title`, `sub`, `list_label`, `list` (array of `icon` and `text` pairs), `button_text`, `button_aria`, and `meta_description` entries.
- To add or update texts, modify or extend the array in this file.

## Included Global Scripts
- Google Tag Manager `GTM-KRRLB4X`
- Google Ads tag `AW-964347386`
- Google Analytics `G-TP4GJ51GSL`
- Facebook Pixel `701415057290990`

## Styling
- Uses standard site styles plus `content/site/page/new_pages/telegram_adv/telegram_adv.css` for layout and responsive design scoped under `.tg-landing`.
- Ensure changes keep all content and the single call-to-action button visible on one mobile screen.

## Testing
- Run the smoke test: `php tests/telegram_adv_page_test.php`.
- The test checks routing, CSS inclusion, localized texts, absence of the word “lottery”, and ensures no navigation or footer is rendered.

