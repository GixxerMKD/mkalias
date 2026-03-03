Universal alias generator (MK Cyrillic -> Latin transliteration) for extensions that use a title/name field and an alias/slug field.
 
 Transliteration rules:
 gj = ѓ | zh = ж | kj = ќ | ch = ч | lj = љ | nj = њ | dz = ѕ | dzj = џ | sh = ш
 
 Behavior:
  - Runs only if the user left alias empty (based on submitted $data when available)
  - Never overwrites a non-empty user-provided alias
  - Supports common field names:
      Title: title, name
      Alias: alias, slug

Works in com_content and any other component which uses title/name and alias/slug.

Tested with com_content, DP Calendar, Phoca Gallery on Joomla 6.0.3

Simply type the Title in Cyrillic and leave the alias field empty.

Download the zip file and install it as any other Joomla extensions, then in Plugins, look for MK Alias and enable the plugin.
