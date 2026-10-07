<?php

namespace App\Support;

use App\Attributes\AppCommand;
use App\Enums\CommandLifecycle;
use Illuminate\Support\Facades\Artisan;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

/**
 * Catalog of the app's own artisan commands, read from the code by reflection.
 *
 * Generated, not written: every command already declares its description and,
 * in $signature, each argument and option with its help and default. A hand
 * list would duplicate that and go stale with the first new command.
 *
 * Commands are selected by file location (app/Console/Commands), not by name
 * prefix, so a new command with a new prefix shows up on its own.
 */
class CommandCatalog
{
    /** Symfony adds these to every command; listing them is noise. */
    private const GLOBAL_OPTIONS = ['help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi', 'no-interaction', 'env', 'silent'];

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        $scheduled = collect(ScheduleCatalog::all())->keyBy('name');
        $commands = [];

        foreach (Artisan::all() as $name => $command) {
            if (! self::isOurs($command)) {
                continue;
            }

            $meta = self::meta($command);
            $task = $scheduled->get($name);

            $commands[] = [
                'name' => $name,
                'description' => $command->getDescription(),
                'domain' => JobRunRecorder::domain($name),
                'arguments' => self::arguments($command),
                'options' => self::options($command),
                'example' => self::example($name, $command),
                'lifecycle' => $meta->lifecycle->value,
                'lifecycle_label' => $meta->lifecycle->label(),
                'note' => $meta->note,
                // Scheduled ones also say when they run: the next question
                // after "what does it do?".
                'schedule' => $task ? [
                    'expression' => $task['expression'],
                    'next_run' => $task['next_run'],
                    'last_run' => $task['last_run'],
                ] : null,
            ];
        }

        usort($commands, fn ($a, $b) => [$a['domain'], $a['name']] <=> [$b['domain'], $b['name']]);

        return $commands;
    }

    private static function meta(Command $command): AppCommand
    {
        $attributes = (new ReflectionClass($command))->getAttributes(AppCommand::class);

        return $attributes ? $attributes[0]->newInstance() : new AppCommand(CommandLifecycle::Recurring);
    }

    private static function isOurs(Command $command): bool
    {
        $file = (new ReflectionClass($command))->getFileName();

        return $file !== false && str_contains($file, 'app'.DIRECTORY_SEPARATOR.'Console'.DIRECTORY_SEPARATOR.'Commands');
    }

    /** @return list<array<string, mixed>> */
    private static function arguments(Command $command): array
    {
        return array_values(array_map(fn (InputArgument $a) => [
            'name' => $a->getName(),
            'description' => $a->getDescription(),
            'required' => $a->isRequired(),
            'default' => $a->getDefault(),
        ], $command->getDefinition()->getArguments()));
    }

    /** @return list<array<string, mixed>> */
    private static function options(Command $command): array
    {
        return array_values(array_map(fn (InputOption $o) => [
            'name' => $o->getName(),
            'description' => $o->getDescription(),
            'accepts_value' => $o->acceptValue(),
            'default' => $o->acceptValue() ? $o->getDefault() : null,
        ], array_filter(
            $command->getDefinition()->getOptions(),
            fn (InputOption $o) => ! in_array($o->getName(), self::GLOBAL_OPTIONS, true),
        )));
    }

    /** Ready-to-paste line with the required arguments. */
    private static function example(string $name, Command $command): string
    {
        $line = 'php artisan '.$name;
        foreach ($command->getDefinition()->getArguments() as $argument) {
            if ($argument->isRequired()) {
                $line .= ' <'.$argument->getName().'>';
            }
        }

        return $line;
    }
}
