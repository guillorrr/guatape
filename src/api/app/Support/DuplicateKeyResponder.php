<?php

namespace App\Support;

use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;

/**
 * Turns a unique-constraint violation (MySQL 1062) into a structured 409.
 *
 * Laravel's default is a 500 with the raw SQL in the message, which the SPA can
 * only show as "Server Error". This extracts the table, the column and the
 * duplicated value, and — when a resolver is registered for that table — the
 * record that already holds the value, so the UI can say what collides:
 *
 *   {
 *     "message": "A record with email = 'a@b.c' already exists (users #3 \"Ana\").",
 *     "code": "duplicate_unique_key",
 *     "details": {
 *       "table": "users", "column": "email", "key_name": "users_email_unique",
 *       "value": "a@b.c",
 *       "owner": {"type": "users", "id": 3, "name": "Ana"}   // null if unresolved
 *     }
 *   }
 *
 * Register owner resolvers per table (e.g. in AppServiceProvider::boot):
 *
 *   DuplicateKeyResponder::resolveOwnerUsing('users', fn (string $column, string $value) =>
 *       User::where($column, $value)->first(['id', 'name']));
 */
final class DuplicateKeyResponder
{
    /** @var array<string, Closure(string, string): mixed> */
    private static array $resolvers = [];

    /**
     * @param  Closure(string $column, string $value): mixed  $resolver  returns a model (id + name) or null
     */
    public static function resolveOwnerUsing(string $table, Closure $resolver): void
    {
        self::$resolvers[$table] = $resolver;
    }

    public static function respond(UniqueConstraintViolationException $e): JsonResponse
    {
        $info = self::extract($e);

        return response()->json([
            'message' => self::buildMessage($info),
            'code' => 'duplicate_unique_key',
            'details' => $info,
        ], 409);
    }

    /**
     * @return array{table: ?string, column: ?string, key_name: ?string, value: ?string, owner: ?array{type: string, id: mixed, name: ?string}}
     */
    private static function extract(UniqueConstraintViolationException $e): array
    {
        $msg = $e->getMessage();

        // MySQL: "Duplicate entry 'X' for key 'table.index_name'"
        $value = preg_match("/Duplicate entry '(.*?)' for key '/", $msg, $m) ? $m[1] : null;
        $keyName = preg_match("/for key '(?:[^']*\\.)?([^']+)'/", $msg, $m) ? $m[1] : null;

        // Table from the SQL: insert into `x` / update `x` / replace into `x`.
        $table = preg_match('/^\s*(?:insert into|update|replace into)\s+`?([a-zA-Z0-9_]+)`?/i', $e->getSql(), $m)
            ? $m[1]
            : null;

        $column = self::guessColumn($keyName, $table);

        return [
            'table' => $table,
            'column' => $column,
            'key_name' => $keyName,
            'value' => $value,
            'owner' => self::resolveOwner($table, $column, $value),
        ];
    }

    /**
     * Laravel names unique indexes `{table}_{column}_unique`; strip both ends.
     * Composite or hand-named indexes come back as-is, as a hint.
     */
    private static function guessColumn(?string $keyName, ?string $table): ?string
    {
        if ($keyName === null) {
            return null;
        }

        $base = $keyName;
        if ($table !== null && str_starts_with($base, $table.'_')) {
            $base = substr($base, strlen($table) + 1);
        }
        if (str_ends_with($base, '_unique')) {
            $base = substr($base, 0, -strlen('_unique'));
        }

        return $base !== '' ? $base : null;
    }

    /**
     * @return ?array{type: string, id: mixed, name: ?string}
     */
    private static function resolveOwner(?string $table, ?string $column, ?string $value): ?array
    {
        if ($table === null || $column === null || $value === null || ! isset(self::$resolvers[$table])) {
            return null;
        }

        try {
            $owner = (self::$resolvers[$table])($column, $value);
        } catch (\Throwable) {
            return null;
        }

        return $owner ? [
            'type' => $table,
            'id' => $owner->getKey(),
            'name' => $owner->name ?? null,
        ] : null;
    }

    /**
     * @param  array{column: ?string, value: ?string, owner: ?array{type: string, id: mixed, name: ?string}}  $info
     */
    private static function buildMessage(array $info): string
    {
        $column = $info['column'] ?? 'unique field';
        $value = $info['value'] ?? '';
        $owner = $info['owner'];

        if ($owner !== null) {
            $label = $owner['name'] ?? '#'.$owner['id'];

            return __("A record with :column = ':value' already exists (:type #:id \":label\").", [
                'column' => $column, 'value' => $value, 'type' => $owner['type'], 'id' => $owner['id'], 'label' => $label,
            ]);
        }

        return __("A record with :column = ':value' already exists.", ['column' => $column, 'value' => $value]);
    }
}
