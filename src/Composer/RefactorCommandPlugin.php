<?php

declare(strict_types=1);

namespace CodebarAg\CodingGuidelines\Composer;

use CodebarAg\CodingGuidelines\Support\RefactorCommandSynchronizer;
use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

final class RefactorCommandPlugin implements EventSubscriberInterface, PluginInterface
{
    private ?Composer $composer = null;

    private ?IOInterface $io = null;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // Nothing to clean up.
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // Keep user's .cursor/commands/refactor.md file untouched on uninstall.
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'syncRefactorCommand',
            ScriptEvents::POST_UPDATE_CMD => 'syncRefactorCommand',
            ScriptEvents::POST_AUTOLOAD_DUMP => 'syncRefactorCommand',
        ];
    }

    public function syncRefactorCommand(Event $event): void
    {
        $composer = $this->composer ?? $event->getComposer();
        $io = $this->io ?? $event->getIO();

        $vendorDir = (string) $composer->getConfig()->get('vendor-dir');
        $projectRoot = dirname($vendorDir);
        $packageRoot = dirname(__DIR__, 2);

        RefactorCommandSynchronizer::sync($projectRoot, $packageRoot, $io);
    }
}
