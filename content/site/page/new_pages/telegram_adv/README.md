# Telegram Advertising Landing Page

## Route and Purpose
- Accessible at `/[lang]/telegram_adv`.
- Presents a single-screen landing page promoting the Auto Moldova Telegram channel for advertising purposes.

## Managing Languages and Texts
- Localized strings are defined in `content/site/page/new_pages/telegram_adv/lang_tel.php`.
- Each language key (`ro`, `ru`, `en`, etc.) contains `title`, `subtitle`, `description`, `button_text`, and `meta_description`.
- To add or update texts, modify or extend the array in this file.

## Included Global Scripts
- Google Tag Manager `GTM-KRRLB4X`
- Google Ads tag `AW-964347386`
- Google Analytics `G-TP4GJ51GSL`
- Facebook Pixel `701415057290990`

## Styling
- Uses standard site styles plus `content/site/page/new_pages/telegram_adv/telegram_adv.css` for layout and responsive design.
- Ensure changes keep all content and the button visible on one mobile screen.

## Testing
- Run the smoke test: `php tests/telegram_adv_page_test.php`.
- The test checks routing, localized texts, presence of the Telegram link, and absence of global navigation and footer.
