<?php

namespace App\Attributes;

use App\Enums\CommandLifecycle;
use Attribute;

/**
 * Command metadata that can't be derived from the code.
 *
 * Description, arguments and options come from $signature (CommandCatalog reads
 * them). What no analysis can know is whether a command still has work to do.
 *
 *   #[AppCommand(CommandLifecycle::OneShot, note: 'Ran on 2026-05-02 over all legacy rows.')]
 */
#[Attribute(Attribute::TARGET_CLASS)]
class AppCommand
{
    public function __construct(
        public readonly CommandLifecycle $lifecycle = CommandLifecycle::Recurring,
        /** Required for OneShot: when and over what it ran. */
        public readonly ?string $note = null,
    ) {}
}
