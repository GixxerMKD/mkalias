<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Content.mkalias
 *
 * @license     MIT
 */

defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Universal alias generator (MK Cyrillic -> Latin transliteration) for extensions that use
 * a title/name field and an alias/slug field.
 *
 * Transliteration rules:
 * gj = ѓ | zh = ж | kj = ќ | ch = ч | lj = љ | nj = њ | dz = ѕ | dzj = џ | sh = ш
 *
 * Behavior:
 * - Runs only if the user left alias empty (based on submitted $data when available)
 * - Never overwrites a non-empty user-provided alias
 * - Supports common field names:
 *     Title: title, name
 *     Alias: alias, slug
 */
class PlgContentMkalias extends CMSPlugin
{
    public function onContentBeforeSave($context, &$item, $isNew, $data = []): bool
    {
        // Find title field
        $titleField = $this->pickFirstField($item, ['title', 'name']);
        if ($titleField === null) {
            return true;
        }

        $title = trim((string) $item->{$titleField});
        if ($title === '') {
            return true;
        }

        // Find alias field
        $aliasField = $this->pickFirstField($item, ['alias', 'slug']);
        if ($aliasField === null) {
            return true;
        }

        // Determine whether user submitted an alias (if form data provides it)
        $submittedAlias = null;

        // Most components use 'alias' in $data
        if (is_array($data)) {
            if (array_key_exists('alias', $data)) {
                $submittedAlias = trim((string) $data['alias']);
            } elseif (array_key_exists('slug', $data)) {
                $submittedAlias = trim((string) $data['slug']);
            }
        }

        // If we can see the submitted alias and it's not empty, do not override.
        if ($submittedAlias !== null && $submittedAlias !== '') {
            return true;
        }

        // Some models pre-fill alias before plugins run; that's OK as long as user left it empty.
        // If there is no submitted alias info (null), be conservative: only set when current alias is empty-ish OR looks like an auto date stamp.
        $currentAlias = trim((string) $item->{$aliasField});

        if ($submittedAlias === null) {
            // Heuristic: allow override if empty or looks like YYYY-MM-DD-HH-MM-SS / YYYY-MM-DD-HH-MM
            if ($currentAlias !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}-\d{2}-\d{2}(-\d{2})?$/', $currentAlias)) {
                return true;
            }
        }

        $latin = $this->mkToLatin($title);
        $safe  = OutputFilter::stringURLSafe($latin);

        if ($safe === '') {
            return true;
        }

        $item->{$aliasField} = $safe;

        return true;
    }

    private function pickFirstField($obj, array $candidates): ?string
    {
        foreach ($candidates as $field) {
            if (is_object($obj) && property_exists($obj, $field)) {
                return $field;
            }
        }
        return null;
    }

    private function mkToLatin(string $text): string
    {
        // Normalize ѐ/ѝ sometimes used in MK texts
        $text = str_replace(['ѐ', 'Ѐ', 'ѝ', 'Ѝ'], ['е', 'Е', 'и', 'И'], $text);

        $map = [
            // Macedonian-specific
            'ѓ' => 'gj', 'Ѓ' => 'GJ',
            'ж' => 'zh', 'Ж' => 'ZH',
            'ќ' => 'kj', 'Ќ' => 'KJ',
            'ч' => 'ch', 'Ч' => 'CH',
            'љ' => 'lj', 'Љ' => 'LJ',
            'њ' => 'nj', 'Њ' => 'NJ',
            'ѕ' => 'dz', 'Ѕ' => 'DZ',
            'џ' => 'dzj','Џ' => 'DZJ',
            'ш' => 'sh', 'Ш' => 'SH',

            // Standard letters (MK)
            'а' => 'a', 'А' => 'A',
            'б' => 'b', 'Б' => 'B',
            'в' => 'v', 'В' => 'V',
            'г' => 'g', 'Г' => 'G',
            'д' => 'd', 'Д' => 'D',
            'е' => 'e', 'Е' => 'E',
            'з' => 'z', 'З' => 'Z',
            'и' => 'i', 'И' => 'I',
            'ј' => 'j', 'Ј' => 'J',
            'к' => 'k', 'К' => 'K',
            'л' => 'l', 'Л' => 'L',
            'м' => 'm', 'М' => 'M',
            'н' => 'n', 'Н' => 'N',
            'о' => 'o', 'О' => 'O',
            'п' => 'p', 'П' => 'P',
            'р' => 'r', 'Р' => 'R',
            'с' => 's', 'С' => 'S',
            'т' => 't', 'Т' => 'T',
            'у' => 'u', 'У' => 'U',
            'ф' => 'f', 'Ф' => 'F',
            'х' => 'h', 'Х' => 'H',
            'ц' => 'c', 'Ц' => 'C',
        ];

        return strtr($text, $map);
    }
}
