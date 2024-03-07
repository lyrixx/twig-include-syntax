<?php

use Castor\Attribute\AsListener;
use Castor\Attribute\AsTask;
use Castor\Event\AfterApplicationInitializationEvent;
use Castor\TaskDescriptorCollection;
use Symfony\Component\Finder\Finder;

use function Castor\io;
use function Castor\run;

#[AsTask(description: 'Replace Twig Include Syntax')]
function replace(string $path): void
{
    $files = Finder::create()
        ->in($path)
        ->files()
    ;

    foreach ($files as $file) {
        $content = file_get_contents($file);
        if (!str_contains($content, 'include')) {
            continue;
        }

        $callback = function (array $matches) {
            $layout = '{{ include(\'%s\'%s%s) }}';

            $templateName = $matches['templateName'];
            $templateName = str_replace(':', '/', $templateName);
            $templateName = ltrim($templateName, '/');

            $variables = $only = '';

            if (isset($matches['variables'])) {
                $variables = ', {'.$matches['variables'].'}';
            }

            if (isset($matches['only'])) {
                $only = ', with_context = false';
            }

            return sprintf($layout, $templateName, $variables, $only);
        };

        $pattern = '#{%[\s\\n]+include(?:\()?[\s\\n\(]+[\'"\(](?<templateName>[\w+\.\-_/@]+)[\'"](?:\))?(?:[\s\\n]+with[\s\\n]+{(?<variables>(?:.|\n)*)})?(?:[\s\\n]+(?<only>only)[\s\\n]+)?[\s\\n]*%}#U';

        $content = preg_replace_callback($pattern, $callback, $content);

        file_put_contents($file, $content);
    }
}

#[Internal]
#[AsTask(description: "Package the application in a static binary file")]
function package(): void
{
    if (Phar::running(false)) {
        throw new RuntimeException('This task must be run outside a phar.');
    }

    io()->title('Packaging application');

    io()->section('Installing vendor');
    run(['composer', 'install', '--no-dev', '--optimize-autoloader']);

    io()->section('Compiling phar');
    run(['castor', 'repack', '--app-name', 'twig-include-syntax']);

    io()->section('Compiling static binary');
    run(['castor', 'compile', 'twig-include-syntax.linux.phar']);
}

#[Attribute(Attribute::TARGET_FUNCTION)]
class Internal
{
}

#[AsListener(AfterApplicationInitializationEvent::class)]
function afterApplicationInitialization(AfterApplicationInitializationEvent $event): void
{
    if (!Phar::running(false)) {
        return;
    }

    $tasks = $event->taskDescriptorCollection->taskDescriptors;
    $tasks = array_filter($tasks, fn ($task) => !(bool) ($task->function->getAttributes(Internal::class)[0] ?? false));

    $event->taskDescriptorCollection = new TaskDescriptorCollection($tasks, []);
}
