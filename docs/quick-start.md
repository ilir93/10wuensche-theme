# Quick Start - Working with 10wuensche Theme

## Starting a New Session

Copy and paste this message to start working on your WordPress theme:

```
I'm working on my WordPress theme. The theme path is:
/Users/ilirtrstena/Local Sites/10wuenschech/app/public/wp-content/themes/10wuensche-theme

Please check the theme structure and let's continue working.
```

## Translation System

### How it Works

1. **Access Theme Settings**: Go to WordPress Admin → Appearance → Theme Settings
2. **Find Language & Translations section**: Scroll down to the "Language & Translations" section
3. **Select Language**: Choose between German (DE) or English (EN)
4. **Edit Translations**: Modify any of the translatable strings

### Translatable Strings

The following strings can be translated:
- **Wish Number**: "Wunsch #" → "Wish #"
- **Times Shared**: "mal geteilt" → "times shared"
- **Copy**: "Kopieren" → "Copy"
- **Copied**: "Kopiert!" → "Copied!"
- **Copy With Icon**: "📋 Kopieren" → "📋 Copy"
- **Copied With Check**: "✅ Kopiert!" → "✅ Copied!"
- **Share Article**: "Artikel teilen" → "Share Article"
- **Email**: "E-Mail" → "E-Mail"
- **More Information**: "Weitere Informationen" → "More Information"
- **About Author**: "Über den Autor" → "About the Author"
- **Last Updated**: "Letzte Aktualisierung" → "Last Updated"
- **Scroll Left**: "Scroll left" → "Scroll left"
- **Scroll Right**: "Scroll right" → "Scroll right"
- **Birthday Wish**: "Geburtstagswunsch" → "Birthday Wish"

### Adding New Translatable Strings

To add new translatable strings:

1. Add the string key to the `$defaults` array in the `get_theme_translation()` function
2. Add it to both `$default_strings` and `$english_defaults` in the admin interface
3. Use `get_theme_translation('your_key')` in your template files

Example:
```php
echo esc_html(get_theme_translation('your_new_key'));
```

## Files Modified for Translation System

- `functions.php` - Added translation functions and settings
- `page-custom-seo.php` - Updated all German text to use translation functions
- `header.php` - Updated navigation arrow labels

## Other Templates

The following templates still need translation implementation if they contain German text:
- `page-category.php`
- `page-homepage.php`
- `footer.php`
- `404.php`

## WordPress Path Structure

```
/Users/ilirtrstena/Local Sites/10wuenschech/
├── app/
│   └── public/
│       └── wp-content/
│           └── themes/
│               └── 10wuensche-theme/
│                   ├── functions.php
│                   ├── header.php
│                   ├── footer.php
│                   ├── page-custom-seo.php
│                   ├── page-category.php
│                   ├── page-homepage.php
│                   └── style.css
```
