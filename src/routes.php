<?php

declare(strict_types=1);

use Lits\Action\CatalogsAction;
use Lits\Action\IndexAction;
use Lits\Action\ShelvesAction;
use Lits\Action\TraysAction;
use Lits\Command\ProcessCommand;
use Lits\Command\TrayIdsCommand;
use Lits\Framework;

return function (Framework $framework): void {
    $framework->app()->get('/process', ProcessCommand::class);
    $framework->app()->get('/tray_ids', TrayIdsCommand::class);

    $framework->app()
        ->get('/', IndexAction::class)
        ->setName('index');

    $framework->app()
        ->get('/catalogs', CatalogsAction::class)
        ->setName('catalogs');

    $framework->app()
        ->post('/catalogs', [CatalogsAction::class, 'post'])
        ->setArgument('auth', 'admin');

    $framework->app()
        ->get('/shelves[/{module}[/{side}]]', ShelvesAction::class)
        ->setName('shelves');

    $framework->app()
        ->post('/shelves', [ShelvesAction::class, 'post'])
        ->setArgument('auth', 'admin');

    $framework->app()
        ->get('/trays', TraysAction::class)
        ->setName('trays');

    $framework->app()
        ->post('/trays', [TraysAction::class, 'post'])
        ->setArgument('auth', 'admin');
};
