<?php

namespace App\Enums;

/**
 * Where a command is in its life (the SPA shows a translated badge per value). A name like "backfill" says nothing about
 * whether there is still work to do; this does.
 */
enum CommandLifecycle: string
{
    /** In normal use, by hand or scheduled. The default. */
    case Recurring = 'recurring';

    /** Re-runnable repair: run when something broke or a calculation changed. */
    case Repair = 'repair';

    /** Done: the migration it existed for is finished. Kept as a record; requires a note. */
    case OneShot = 'one_shot';
}
