<?php

namespace Tests\PHPCensor\Plugin;

use PHPCensor\Plugin\Phar as PharPlugin;
use Phar as PHPPhar;

class PharTest extends \PHPUnit\Framework\TestCase
{
    protected $directory;

    protected function tearDown()
    {
        $this->cleanSource();
    }

    protected function getPlugin(array $options = [])
    {
        $build = $this
            ->getMockBuilder('PHPCensor\Model\Build')
            ->disableOriginalConstructor()
            ->getMock();

        $builder = $this
            ->getMockBuilder('PHPCensor\Builder')
            ->disableOriginalConstructor()
            ->getMock();

        return new PharPlugin($builder, $build, $options);
    }

    protected function buildTemp()
    {
        $directory = tempnam(ROOT_DIR . 'tests' . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR, 'source');
        unlink($directory);
        return $directory;
    }

    protected function buildSource()
    {
        $directory = $this->buildTemp();
        mkdir($directory);
        file_put_contents($directory . '/one.php', '<?php echo "one";');
        file_put_contents($directory . '/two.php', '<?php echo "two";');
        mkdir($directory . '/config');
        file_put_contents($directory . '/config/config.ini', '[config]');
        mkdir($directory . '/views');
        file_put_contents($directory . '/views/index.phtml', '<?php echo "hello";');
        $this->directory = $directory;
        return $directory;
    }

    protected function cleanSource()
    {
        if ($this->directory) {
            $filenames = [
                '/build.phar',
                '/stub.php',
                '/views/index.phtml',
                '/views',
                '/config/config.ini',
                '/config',
                '/two.php',
                '/one.php',
            ];
            foreach ($filenames as $filename) {
                if (is_dir($this->directory . $filename)) {
                    rmdir($this->directory . $filename);
                } else if (is_file($this->directory . $filename)) {
                    unlink($this->directory . $filename);
                }
            }
            rmdir($this->directory);
            $this->directory = null;
        }
    }

    protected function checkReadonly()
    {
        if (ini_get('phar.readonly')) {
            $this->markTestSkipped('Test skipped because phar writing disabled in php.ini.');
        }
    }

    public function testPlugin()
    {
        $plugin = $this->getPlugin();
        $this->assertInstanceOf('PHPCensor\Plugin', $plugin);
        $this->assertInstanceOf('PHPCensor\Model\Build', $plugin->getBuild());
        $this->assertInstanceOf('PHPCensor\Builder', $plugin->getBuilder());
    }

    public function testDirectory()
    {
        $plugin = $this->getPlugin();
        $plugin->getBuilder()->buildPath = 'foo';
        $this->assertEquals('foo', $plugin->getDirectory());

        $plugin = $this->getPlugin(['directory' => 'dirname']);
        $this->assertEquals('dirname', $plugin->getDirectory());
    }

    public function testFilename()
    {
        $plugin = $this->getPlugin();
        $this->assertEquals('build.phar', $plugin->getFilename());

        $plugin = $this->getPlugin(['filename' => 'another.phar']);
        $this->assertEquals('another.phar', $plugin->getFilename());
    }

    public function testRegExp()
    {
        $plugin = $this->getPlugin();
        $this->assertEquals('/\.php$/', $plugin->getRegExp());

        $plugin = $this->getPlugin(['regexp' => '/\.(php|phtml)$/']);
        $this->assertEquals('/\.(php|phtml)$/', $plugin->getRegExp());
    }

    public function testStub()
    {
        $plugin = $this->getPlugin();
        $this->assertNull($plugin->getStub());

        $plugin = $this->getPlugin(['stub' => 'stub.php']);
        $this->assertEquals('stub.php', $plugin->getStub());
    }

    public function testExecute()
    {
        $this->checkReadonly();

        $plugin = $this->getPlugin();
        $path   = $this->buildSource();
        $plugin->getBuilder()->buildPath = $path;

        $this->assertTrue($plugin->execute());

        $this->assertFileExists($path . '/build.phar');
        PHPPhar::loadPhar($path . '/build.phar');
        $this->assertFileEquals($path . '/one.php', 'phar://build.phar/one.php');
        $this->assertFileEquals($path . '/two.php', 'phar://build.phar/two.php');
        $this->assertFileNotExists('phar://build.phar/config/config.ini');
        $this->assertFileNotExists('phar://build.phar/views/index.phtml');
    }

    public function testExecuteRegExp()
    {
        $this->checkReadonly();

        $plugin = $this->getPlugin(['regexp' => '/\.(php|phtml)$/']);
        $path   = $this->buildSource();
        $plugin->getBuilder()->buildPath = $path;

        $this->assertTrue($plugin->execute());

        $this->assertFileExists($path . '/build.phar');
        PHPPhar::loadPhar($path . '/build.phar');
        $this->assertFileEquals($path . '/one.php', 'phar://build.phar/one.php');
        $this->assertFileEquals($path . '/two.php', 'phar://build.phar/two.php');
        $this->assertFileNotExists('phar://build.phar/config/config.ini');
        $this->assertFileEquals($path . '/views/index.phtml', 'phar://build.phar/views/index.phtml');
    }

    public function testExecuteStub()
    {
        $this->checkReadonly();

        $content = <<<STUB
<?php
Phar::mapPhar();
__HALT_COMPILER(); ?>
STUB;

        $path = $this->buildSource();
        file_put_contents($path . '/stub.php', $content);

        $plugin = $this->getPlugin(['stub' => 'stub.php']);
        $plugin->getBuilder()->buildPath = $path;

        $this->assertTrue($plugin->execute());

        $this->assertFileExists($path . '/build.phar');
        $phar = new PHPPhar($path . '/build.phar');
        $this->assertEquals($content, trim($phar->getStub())); // + trim because PHP adds newline char
    }

    public function testExecuteUnknownDirectory()
    {
        $this->checkReadonly();

        $directory = $this->buildTemp();

        $plugin = $this->getPlugin(['directory' => $directory]);
        $plugin->getBuilder()->buildPath = $this->buildSource();

        $this->assertFalse($plugin->execute());
    }
}
