<?php
namespace Concrete\Package\PhpDebugbar;

use DebugBar\StandardDebugBar;
use Illuminate\Filesystem\Filesystem;
use Request;

class Controller extends \Package
{

    protected $pkgHandle = 'php_debugbar';
    protected $appVersionRequired = '5.7.4';
    protected $pkgVersion = '0.9.1';

    public function getPackageDescription()
    {
        return t("Enables the use of PHP Debugbar in concrete5.");
    }

    public function getPackageName()
    {
        return t("PHP Debugbar");
    }

    public function install()
    {
        if (!file_exists(__DIR__ . '/vendor')) {
            throw new \Exception('Please install composer dependencies before you install this package. ' .
                'Run `cd "' . __DIR__ . '" && composer install`');
        }

        parent::install();

        // Make sure we load everything.
        $this->on_start();

    }

    public function on_start()
    {
        $req = Request::getInstance();
        if(!$req->isXmlHttpRequest()) {
            $filesystem = new Filesystem();
            $filesystem->getRequire(__DIR__ . '/vendor/autoload.php');

            \Core::make('app')->instance('debugbar', $bar = new StandardDebugBar());
            $debugStack = new \Doctrine\DBAL\Logging\DebugStack();

            // Cache javascript renderer object.
            $renderer = $bar->getJavascriptRenderer(DIR_REL . '/packages/php_debugbar/vendor/maximebf/debugbar/src/DebugBar/Resources');

            \Database::connection()->getConfiguration()->setSQLLogger($debugStack);

            $bar->addCollector(new \DebugBar\Bridge\DoctrineCollector($debugStack));
            \View::getInstance()->addHeaderItem($renderer->renderHead());

            \Events::addListener('on_page_output', function ($event) use ($renderer) {
                if(is_object(\Page::getCurrentPage())) {
                    $output = $renderer->render();
                    $originalContent = $event->getArgument('contents');
                    $newContent = str_replace('</body>', $output . '</body>', $originalContent);
                    $event->setArgument('contents', $newContent);
                }
            });
        }
    }

}
