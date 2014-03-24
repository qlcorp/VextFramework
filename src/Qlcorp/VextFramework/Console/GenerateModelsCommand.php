<?php namespace Qlcorp\VextFramework\Console;

use Illuminate\Database\Console\Migrations\MigrateCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Database\Migrations\Migrator;

class GenerateModelsCommand extends MigrateCommand {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'migrate:generate';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Generate models via Schema';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct(Migrator $migrator, $packagePath)
	{
		parent::__construct($migrator, $packagePath);
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{

        $build = (bool) $this->input->getOption('build');
        $seed = (bool) $this->input->getOption('seed');
        $pretend = !$build;

        if ($build) {
            $options = $this->input->getOptions();
            $options = array_except($options, array('build'));
            $args = array();
            foreach ($options as $option => $value) {
                $args['--' . $option] = $value;
            }
            $this->call('migrate', $args);
        } else if ($seed) {
            $this->call('db:seed'); //todo: add args
        }

        $path = $this->getMigrationPath();
        $this->migrator->run($path, $pretend);

        // Once the migrator has run we will grab the note output and send it out to
        // the console screen, since the migrator itself functions without having
        // any instances of the OutputInterface contract passed into the class.
        foreach ($this->migrator->getNotes() as $note)
        {
            $this->output->writeln($note);
        }

        $this->call('dump-autoload');

	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			//array('example', InputArgument::REQUIRED, 'An example argument.'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			//array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
            array('bench', null, InputOption::VALUE_OPTIONAL, 'The name of the workbench to migrate.', null),
            array('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use.'),
            array('path', null, InputOption::VALUE_OPTIONAL, 'The path to migration files.', null),
            array('package', null, InputOption::VALUE_OPTIONAL, 'The package to migrate.', null),
            array('build', null, InputOption::VALUE_NONE, 'Build tables after generating models.', null),
            array('seed', null, InputOption::VALUE_NONE, 'Indicates if the seed task should be re-run.'),
		);
	}

}
