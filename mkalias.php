<?php
defined('_JEXEC') or die;

use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * Universal alias generator (MK Cyrillic -> Latin transliteration).
 *
 * v1.6.0: fixes mixed-script titles where Joomla auto-generates alias only from Latin part.
 */
class PlgContentMkalias extends CMSPlugin
{
    public function onContentBeforeSave($context, &$item, $isNew, $data = []): bool
    {
        $titleField = $this->pickFirstField($item, ['title', 'name']);
        if ($titleField === null) {
            return true;
        }

        $title = trim((string) $item->{$titleField});
        if ($title === '') {
            return true;
        }

        $aliasField = $this->pickFirstField($item, ['alias', 'slug']);
        if ($aliasField === null) {
            return true;
        }

        // Submitted alias (if available)
        $submittedAlias = null;
        if (is_array($data)) {
            if (array_key_exists('alias', $data)) {
                $submittedAlias = trim((string) $data['alias']);
            } elseif (array_key_exists('slug', $data)) {
                $submittedAlias = trim((string) $data['slug']);
            }
        }

        // If user provided a non-empty alias, never overwrite.
        if ($submittedAlias !== null && $submittedAlias !== '') {
            return true;
        }

        $currentAlias = trim((string) ($item->{$aliasField} ?? ''));

        // Desired alias from transliterated title
        $latin   = $this->mkToLatin($title);
        $desired = OutputFilter::stringURLSafe($latin);

        if ($desired === '') {
            return true;
        }

        // If submitted alias is explicitly empty => user left it blank => force our desired alias.
        if ($submittedAlias !== null && $submittedAlias === '') {
            $item->{$aliasField} = $desired;
            return true;
        }

        // submitted alias not available (null) => override only when current alias looks auto-generated
        if ($submittedAlias === null) {
            if ($currentAlias === '' || $this->looksLikeDateAlias($currentAlias)) {
                $item->{$aliasField} = $desired;
                return true;
            }

            $autoFromOriginal = OutputFilter::stringURLSafe($title);
            if ($autoFromOriginal !== '' && $currentAlias === $autoFromOriginal) {
                $item->{$aliasField} = $desired;
                return true;
            }

            return true;
        }

        return true;
    }

    private function looksLikeDateAlias(string $alias): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}-\d{2}-\d{2}(-\d{2})?$/', $alias);
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
        $text = str_replace(['ѐ', 'Ѐ', 'ѝ', 'Ѝ'], ['е', 'Е', 'и', 'И'], $text);

        // Normalize common dash characters to hyphen
        $text = str_replace(
            ["\xE2\x80\x90", "\xE2\x80\x91", "\xE2\x80\x92", "\xE2\x80\x93", "\xE2\x80\x94", "\xE2\x80\x95"],
            '-',
            $text
        );

        $map = [
            'ѓ' => 'gj', 'Ѓ' => 'GJ',
            'ж' => 'zh', 'Ж' => 'ZH',
            'ќ' => 'kj', 'Ќ' => 'KJ',
            'ч' => 'ch', 'Ч' => 'CH',
            'љ' => 'lj', 'Љ' => 'LJ',
            'њ' => 'nj', 'Њ' => 'NJ',
            'ѕ' => 'dz', 'Ѕ' => 'DZ',
            'џ' => 'dzj','Џ' => 'DZJ',
            'ш' => 'sh', 'Ш' => 'SH',

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
