<?php

use Minicli\App;
use Minicli\Command\CommandRegistry;
use Minicli\Config;
use Minicli\Output\OutputHandler;
use Minicli\Output\Adapter\DefaultPrinterAdapter;
use Minicli\Exception\CommandNotFoundException;

it('assert App is created')
    ->expect(fn () => getBasicApp())
    ->toBeInstanceOf(App::class);

it('asserts App sets, gets and prints signature', function () {
    $app = getBasicApp();
    $app->setOutputHandler(new OutputHandler(new DefaultPrinterAdapter()));
    expect($app->getSignature())->toContain("minicli");

    $app->setSignature("Testing minicli");
    expect($app->getSignature())->toBe("Testing minicli");

    $app->printSignature();
})->expectOutputString("\nTesting minicli\n");

it('asserts App has Config Service')
    ->expect(fn () => getBasicApp()->config)
    ->toBeInstanceOf(Config::class);

it('asserts App has CommandRegistry Service')
    ->expect(fn () => getBasicApp()->commandRegistry)
    ->toBeInstanceOf(CommandRegistry::class);

it('asserts App has Printer Service')
    ->expect(fn () => getBasicApp()->printer)
    ->toBeInstanceOf(OutputHandler::class);

it('asserts App returns null when a service is not found')
    ->expect(fn () => getBasicApp()->inexistent_service)
    ->toBeNull();

it('asserts App parses command path with @vendor tag', function () {
    $app = new App([
        'app_path' => '@namespace/command'
    ]);

    $registry = $app->commandRegistry;
    $paths = $registry->getCommandsPath();

    expect($paths)->toBeArray()
        ->toHaveCount(1)
        ->and($paths[0])->toEndWith("namespace/command/Command");
});

it('asserts App can handle a closure as a service', function () {
    $app = getBasicApp();
    $app->addService('closure', function () {
        return 'closure';
    });

    expect($app->closure)->toBe('closure');
});

it('asserts Closure service gets passed the App instance', function () {
    $app = getBasicApp();
    $app->addService('closure', function ($app) {
        return $app;
    });

    expect($app->closure)->toBe($app);
});

it('asserts App registers and executes single command', function () {
    $app = getBasicApp();

    $app->registerCommand('minicli-test', function () use ($app) {
        $app->getPrinter()->rawOutput("testing minicli");
    });

    $command = $app->commandRegistry->getCallable('minicli-test');
    expect($command)->toBeCallable();

    $app->runCommand(['minicli', 'minicli-test']);
})->expectOutputString("testing minicli");

it('asserts App executes command from namespace', function () {
    $app = getBasicApp();

    $app->runCommand(['minicli', 'test']);
})->expectOutputString("test default");

it('asserts App prints signature when no command is specified', function () {
    $app = getBasicApp();
    $app->setOutputHandler(new OutputHandler(new DefaultPrinterAdapter()));

    $app->runCommand(['minicli']);
})->expectOutputString("\n./minicli help\n");

it('asserts App throws exception when single command is not found', function () {
    $app = getBasicApp();

    $app->runCommand(['minicli', 'minicli-test-error']);
})->expectException(CommandNotFoundException::class);

it('asserts App throws exception when command is not callable', function () {
    $app = getBasicApp();
    $app->registerCommand('minicli-test-error', "not a callable");
})->expectException(\TypeError::class);

$app = new App();
$errorNotFound = $app->getPrinter()->filterOutput("Command \"inexistent-command\" not found.", 'error');

it('asserts App shows error when debug is set to false and command is not found', function () {
    $app = getProdApp();

    $app->runCommand(['minicli', 'inexistent-command']);
})->expectOutputString("\n" . $errorNotFound . "\n");
