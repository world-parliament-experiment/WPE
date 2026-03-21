<?php

namespace App\EventListener;

use App\Doctrine\Type\EncryptedStringType;
use App\Service\UserEncryptionService;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class EncryptionServiceInjector implements EventSubscriberInterface
{
    private UserEncryptionService $encryptionService;

    public function __construct(UserEncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $this->injectService();
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        $this->injectService();
    }

    private function injectService(): void
    {
        EncryptedStringType::setEncryptionService($this->encryptionService);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // We give it a high priority (2048) to ensure this runs
            // before any Doctrine code needs the type.
            KernelEvents::REQUEST => [['onKernelRequest', 2048]],
            ConsoleEvents::COMMAND => [['onConsoleCommand', 2048]],
        ];
    }
}
