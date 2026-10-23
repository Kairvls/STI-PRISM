<?php

namespace App\Support;

/**
 * Status lines name the role while one person holds it.
 * The person's name is used only when two or more people hold that role.
 */
class RoleHolderLabel
{
    /** @var array<string, bool> */
    private static array $multiple = [];

    public static function multiple(string $role): bool
    {
        if (! function_exists('app') || ! app()->bound('db')) {
            return false;
        }

        if (! array_key_exists($role, self::$multiple)) {
            self::$multiple[$role] = count(WorkflowNotifier::userIdsForRole($role)) >= 2;
        }

        return self::$multiple[$role];
    }

    public static function who(string $role, ?string $personName, string $roleLabel): string
    {
        $name = trim((string) $personName);
        if ($name === '' || ! self::multiple($role)) {
            return $roleLabel;
        }

        return $name;
    }

    public static function line(string $role, ?string $personName, string $roleLine, string $nameLine): string
    {
        $name = self::who($role, $personName, '');
        if ($name === '') {
            return $roleLine;
        }

        return sprintf($nameLine, $name);
    }
}
