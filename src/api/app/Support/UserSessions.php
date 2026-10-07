<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Revokes a user's other sessions after a password change or an admin reset.
 *
 * Only possible with SESSION_DRIVER=database (the default), where sessions are
 * rows tagged with user_id. With other drivers it is a no-op and old sessions
 * live until they expire.
 */
class UserSessions
{
    public static function revoke(User $user, ?string $exceptSessionId = null): int
    {
        if (config('session.driver') !== 'database') {
            return 0;
        }

        return DB::connection(config('session.connection'))
            ->table(config('session.table', 'sessions'))
            ->where('user_id', $user->getKey())
            ->when($exceptSessionId, fn ($q) => $q->where('id', '!=', $exceptSessionId))
            ->delete();
    }
}
