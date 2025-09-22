<?php

namespace MaksMartyn\LaravelIdeHelper\Console;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Illuminate\Support\Str;
use Larastan\Larastan\Properties\MigrationHelper;
use Larastan\Larastan\Properties\SchemaTable;
use PHPStan\Command\CommandHelper;
use PHPStan\Command\InceptionNotSuccessfulException;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ModelsFromMigrationsCommand extends ModelsCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'ide-helper:models-from-migrations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate autocompletion for models from migrations';

    /** @var array<string, SchemaTable> */
    private array $tables = [];

    /**
     * @return MigrationHelper
     * @throws InceptionNotSuccessfulException
     */
    private function makeLarastanMigrationHelperInstance(): MigrationHelper
    {
        $refClass = new ReflectionClass(CommandHelper::class);
        $refMethod = $refClass->getMethod('begin');
        $refParams = $refMethod->getParameters();

        $inputClass = (string)$refParams[0]->getType();
        $outputClass = (string)$refParams[1]->getType();
        $pharInputPrefix = '';
        $pharOutputPrefix = '';

        if (str_starts_with($inputClass, '_PHPStan_')) {
            $pharInputPrefix = explode('\\', $inputClass)[0];
        }

        if (str_starts_with($outputClass, '_PHPStan_')) {
            $pharOutputPrefix = explode('\\', $outputClass)[0];
        }

        $inputClass = $pharInputPrefix . '\\' . ArgvInput::class;
        $outputClass = $pharOutputPrefix . '\\' . ConsoleOutput::class;

        if (! class_exists($inputClass) || ! class_exists($outputClass)) {
            throw new RuntimeException('Unable to find Symfony Console classes in phar');
        }

        if (! is_file($autoload = __DIR__ . '/../../../../autoload.php')) {
            throw new RuntimeException('Unable to find autoload.php');
        }

        /** @phpstan-ignore-next-line */
        $inceptionResult = CommandHelper::begin(
            /** @phpstan-ignore-next-line */
            new $inputClass(),
            /** @phpstan-ignore-next-line */
            new $outputClass($this->verbosity),
            [],
            ini_get('memory_limit'),
            $autoload,
            [],
            null,
            null,
            null,
            true,
            $this->verbosity === OutputInterface::VERBOSITY_DEBUG
        );

        /** @phpstan-ignore-next-line */
        $container = $inceptionResult->getContainer();

        return $container->getByType(MigrationHelper::class);
    }

    /**
     * @inheritDoc
     * @throws InceptionNotSuccessfulException
     */
    public function handle()
    {
        $this->tables = $this->makeLarastanMigrationHelperInstance()->initializeTables();

        parent::handle();
    }

    /**
     * @inheritDoc
     */
    public function getPropertiesFromTable($model): void
    {
        $table = $model->getConnection()->getTablePrefix() . $model->getTable();
        $schema = $this->tables[$table] ?? null;
        $key = $model->getKeyName();
        $missingKey = true;

        if (is_null($schema)) {
            return;
        }

        foreach ($schema->columns as $column) {
            $writable = true;
            $type = $column->readableType;

            if (
                class_exists($column->readableType)
                && ! array_key_exists($column->name, $model->getCasts())
            ) {
                $writable = false;
            }

            if (in_array($column->name, $model->getDates())) {
                $type = $this->dateClass;
            }

            if ($column->name === $key) {
                $missingKey = false;
            }

            $this->setProperty(
                name: $column->name,
                type: $type,
                read: true,
                write: $writable,
                nullable: $column->nullable,
            );

            if ($this->write_model_magic_where) {
                $builderClass = $this->write_model_external_builder_methods
                    ? get_class($model->newModelQuery())
                    : '\Illuminate\Database\Eloquent\Builder';

                $this->setMethod(
                    Str::camel('where_' . $column->name),
                    $this->getClassNameInDestinationFile($model, $builderClass)
                    . '|'
                    . $this->getClassNameInDestinationFile($model, get_class($model)),
                    ['$value']
                );
            }
        }

        if ($missingKey && $type = $model->getKeyType()) {
            $this->setProperty(
                name: $key,
                type: $type,
                read: true,
                write: true,
                nullable: false,
            );
        }
    }
}
