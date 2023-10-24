<?php

declare(strict_types=1);

use Lits\Action\CatalogsAction;
use Lits\Action\ChangesAction;
use Lits\Action\IndexAction;
use Lits\Action\ItemsAction;
use Lits\Action\MonthAction;
use Lits\Action\Shelves\MissingShelvesAction;
use Lits\Action\ShelvesAction;
use Lits\Action\Space\TraySpaceAction;
use Lits\Action\SpaceAction;
use Lits\Action\TraysAction;
use Lits\Command\ItemNewestCommand;
use Lits\Command\ItemStateCommand;
use Lits\Command\ProcessCommand;
use Lits\Command\SpaceUpdateCommand;
use Lits\Framework;

return function (Framework $framework): void {
    $framework->app()->get('/process', ProcessCommand::class);
    $framework->app()->get('/item_newest', ItemNewestCommand::class);
    $framework->app()->get('/item_state', ItemStateCommand::class);
    $framework->app()->get('/space_update', SpaceUpdateCommand::class);

    $framework->app()
        ->get('/', IndexAction::class)
        ->setName('index');

    $framework->app()
        ->get('/iframe', IndexAction::class)
        ->setArgument('auth', 'null')
        ->setName('iframe');

    $framework->app()
        ->get('/catalogs', CatalogsAction::class)
        ->setName('catalogs');

    $framework->app()
        ->post('/catalogs', [CatalogsAction::class, 'post'])
        ->setArgument('auth', 'admin');

    $framework->app()
        ->get('/changes', ChangesAction::class)
        ->setName('changes');

    $framework->app()
        ->get('/items[/{barcode}]', ItemsAction::class)
        ->setName('items');

    ItemsAction::addFileRoutes($framework->app(), '/items/{barcode}');

    $framework->app()
        ->get('/month', MonthAction::class);

    $framework->app()
        ->get('/shelves/missing', MissingShelvesAction::class)
        ->setName('shelves/missing');

    MissingShelvesAction::addFileRoutes($framework->app(), 'shelves/missing');

    $framework->app()
        ->get('/shelves[/{module}[/{side}]]', ShelvesAction::class)
        ->setName('shelves');

    $framework->app()
        ->post('/shelves', [ShelvesAction::class, 'post'])
        ->setArgument('auth', 'admin');

    $framework->app()
        ->get('/space/{tray}[/{status}]', TraySpaceAction::class)
        ->setName('space/tray');

    $framework->app()
        ->get('/space', SpaceAction::class)
        ->setName('space');

    $framework->app()
        ->get('/trays', TraysAction::class)
        ->setName('trays');

    $framework->app()
        ->post('/trays', [TraysAction::class, 'post'])
        ->setArgument('auth', 'admin');
};
