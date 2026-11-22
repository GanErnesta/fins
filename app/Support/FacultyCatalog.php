<?php

namespace App\Support;

final class FacultyCatalog
{
    /** @return array<string, array<int, string>> */
    public static function all(): array
    {
        return config('fins_academics.faculties', []);
    }

    public static function canonical(?string $faculty): ?string
    {
        $value = trim((string) $faculty);
        if ($value === '') {
            return null;
        }

        if (array_key_exists($value, self::all())) {
            return $value;
        }

        $aliases = config('fins_academics.aliases', []);
        return $aliases[$value] ?? null;
    }

    /** @return array<int, string> */
    public static function programsFor(?string $faculty): array
    {
        $canonical = self::canonical($faculty);
        return $canonical ? (self::all()[$canonical] ?? []) : [];
    }

    public static function hasProgram(?string $faculty, ?string $program): bool
    {
        return in_array((string) $program, self::programsFor($faculty), true);
    }
}
