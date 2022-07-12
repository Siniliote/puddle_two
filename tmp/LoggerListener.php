<?php


use Gedmo\Loggable\LoggableListener;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Sets the username from the security context by listening on kernel.request
 *
 * @author Christophe Coevoet <stof@notk.org>
 */
class LoggerListener implements EventSubscriberInterface
{

    public function __construct(
        private readonly LoggableListener $loggableListener,
        private readonly ?TokenStorageInterface $tokenStorage = null,
        private readonly ?AuthorizationCheckerInterface $authorizationChecker = null,
    )
    { }

    /**
     * @internal
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if (HttpKernelInterface::MAIN_REQUEST !== $event->getRequestType()) {
            return;
        }

        if (null === $this->tokenStorage || null === $this->authorizationChecker) {
            return;
        }

        $token = $this->tokenStorage->getToken();

        if (null !== $token && $this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            $this->loggableListener->setUsername($token);
        }
    }

    #[ArrayShape([KernelEvents::REQUEST => "string"])]
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }
}
