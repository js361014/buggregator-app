<?php

declare(strict_types=1);

namespace Infrastructure\CycleOrm;

use Cycle\Database\DatabaseProviderInterface;
use Cycle\Migrations\FileRepository;
use Cycle\Migrations\RepositoryInterface;
use Cycle\ORM\Collection\IlluminateCollectionFactory;
use Cycle\ORM\Factory;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\ORM;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Transaction;
use Cycle\ORM\TransactionInterface;
use Illuminate\Support\ServiceProvider;
use Infrastructure\CycleOrm\Auth\UserProvider;
use Spiral\Core\Container as SpiralContainer;

final class CycleOrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->initContainer();
        $this->initFactory();
        $this->initOrm();
        $this->app->bind(TransactionInterface::class, Transaction::class);
        $this->app->singleton(RepositoryInterface::class, FileRepository::class);
        $this->registerAuthUserProvider();
    }

    private function initContainer(): void
    {
        $this->app[SpiralContainer::class]->bind(TransactionInterface::class, Transaction::class);

        $this->app[SpiralContainer::class]->bindSingleton(
            ORMInterface::class,
            fn () => $this->app[ORMInterface::class]
        );

        $this->app[SpiralContainer::class]->bindSingleton(
            FactoryInterface::class,
            fn () => $this->app[FactoryInterface::class]
        );

        $this->app[SpiralContainer::class]->bindSingleton(
            DatabaseProviderInterface::class,
            fn () => $this->app[DatabaseProviderInterface::class]
        );
    }

    private function initFactory(): void
    {
        $this->app->singleton(FactoryInterface::class, static function ($app) {
            return new Factory(
                dbal: $app[DatabaseProviderInterface::class],
                factory: $app[SpiralContainer::class],
                defaultCollectionFactory: new IlluminateCollectionFactory()
            );
        });
    }

    private function initOrm(): void
    {
        $this->app->singleton(ORMInterface::class, static function ($app): ORM {
            return new ORM(
                $app[FactoryInterface::class],
                $app[SchemaInterface::class]
            );
        });
    }

    private function registerAuthUserProvider(): void
    {
        $this->app['auth']->provider('cycle', function ($app, $config) {
            return new UserProvider(
                $app[ORMInterface::class],
                $app[TransactionInterface::class],
                $config['model'],
                $app['hash'],
            );
        });
    }
}