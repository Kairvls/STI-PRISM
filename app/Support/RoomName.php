<?php

namespace App\Support;

class RoomName
{
    public static function canonical(string $name): string
    {
        $value = mb_strtolower(trim($name));
        $value = str_replace(['&', '/', '-', '_', '.', ',', '(', ')'], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        $value = preg_replace('/laborator(?:ies|y)?/', 'lab', $value) ?? $value;

        $phrases = [
            'computer lab' => 'comlab',
            'comp lab' => 'comlab',
            'com lab' => 'comlab',
            'lecture room' => 'lectureroom',
            'hotel room simulation' => 'hotelroomsimulation',
            'faculty room' => 'facultyroom',
            'school clinic' => 'schoolclinic',
            'admission office' => 'admissionoffice',
        ];

        foreach ($phrases as $from => $to) {
            $value = str_replace($from, $to, $value);
        }

        $value = preg_replace('/[^a-z0-9]+/u', '', $value) ?? $value;
        $value = str_replace(['computerlab', 'complab'], 'comlab', $value);
        $value = preg_replace('/(?<=[a-z])0+(\d+)/', '$1', $value) ?? $value;

        if (preg_match('/^cl(\d+)$/', $value, $match)) {
            $value = 'comlab'.$match[1];
        }

        return $value;
    }

    public static function duplicateGroupCount(iterable $names): int
    {
        $list = [];
        foreach ($names as $name) {
            $trimmed = trim((string) $name);
            if ($trimmed !== '') {
                $list[] = $trimmed;
            }
        }

        $used = [];
        $groups = 0;
        $count = count($list);

        for ($i = 0; $i < $count; $i++) {
            if (isset($used[$i])) {
                continue;
            }

            $size = 1;
            for ($j = $i + 1; $j < $count; $j++) {
                if (isset($used[$j])) {
                    continue;
                }
                if (self::matches($list[$i], $list[$j])) {
                    $used[$j] = true;
                    $size++;
                }
            }

            if ($size > 1) {
                $used[$i] = true;
                $groups++;
            }
        }

        return $groups;
    }

    public static function matches(string $left, string $right): bool
    {
        $a = self::canonical($left);
        $b = self::canonical($right);

        if ($a === '' || $b === '') {
            return false;
        }

        if ($a === $b) {
            return true;
        }

        [$aLetters, $aNumber] = self::splitNumber($a);
        [$bLetters, $bNumber] = self::splitNumber($b);

        if ($aNumber !== $bNumber) {
            return false;
        }

        if ($aLetters === '' || $bLetters === '') {
            return false;
        }

        if (str_contains($aLetters, $bLetters) || str_contains($bLetters, $aLetters)) {
            $shorter = min(strlen($aLetters), strlen($bLetters));
            $longer = max(strlen($aLetters), strlen($bLetters));
            if ($shorter >= 4 && $longer > 0 && ($shorter / $longer) >= 0.7) {
                return true;
            }
        }

        $distance = levenshtein($aLetters, $bLetters);
        $limit = strlen($aLetters) >= 10 || strlen($bLetters) >= 10 ? 3 : 2;

        return $distance >= 0 && $distance <= $limit;
    }

    private static function splitNumber(string $canonical): array
    {
        if (preg_match('/^(.*?)(\d+)$/', $canonical, $match)) {
            return [$match[1], ltrim($match[2], '0') ?: '0'];
        }

        return [$canonical, ''];
    }
}
