<?php

namespace MakinaCorpus\Umenu\Tests\Functionnal\Drupal;

use Drupal\Core\Cache\CacheBackendInterface;
use MakinaCorpus\Umenu\ItemStorageInterface;
use MakinaCorpus\Umenu\MenuStorageInterface;
use MakinaCorpus\Umenu\TreeProviderInterface;
use MakinaCorpus\Umenu\Bridge\Drupal\ItemStorage;
use MakinaCorpus\Umenu\Bridge\Drupal\MenuStorage;
use MakinaCorpus\Umenu\Bridge\Drupal\TreeProvider;
use MakinaCorpus\Umenu\Tests\Functionnal\MenuTestTrait;
use MakinaCorpus\Umenu\Tests\Functionnal\MockPage;

trait DrupalMenuTestTrait
{
    use MenuTestTrait {
        MenuTestTrait::createPage as parentCreatePage;
        MenuTestTrait::createSite as parentCreateSite;
    }

    /**
     * A database connection object from Drupal
     *
     * @var mixed
     */
    static private $databaseConnection;

    /**
     * _drupal_bootstrap_configuration() override
     */
    static private function bootstrapDrupalEnv()
    {
        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = '127.0.0.1';
        }
        if (!isset($_SERVER['HTTP_REFERER'])) {
            $_SERVER['HTTP_REFERER'] = '';
        }
        if (!isset($_SERVER['SERVER_PROTOCOL']) || ($_SERVER['SERVER_PROTOCOL'] != 'HTTP/1.0' && $_SERVER['SERVER_PROTOCOL'] != 'HTTP/1.1')) {
            $_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.0';
        }
        if (!isset($_SERVER['REMOTE_ADDR'])) {
            $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        }
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
        }
    }

    /**
     * Find if a Drupal instance is configured for testing and bootstrap it if
     * found.
     */
    static private function findDrupalDatabase()
    {
        if (self::$databaseConnection) {
            return self::$databaseConnection;
        }

        $variableName = 'DRUPAL_PATH';

        // Try to find out the right site root.
        $directory = getenv($variableName);

        if (!is_dir($directory)) {
            throw new \RuntimeException(sprintf("%s: directory does not exists", $directory));
        }
        if (!file_exists($directory . '/index.php')) {
            throw new \RuntimeException(sprintf("%s: directory is not a PHP application directory", $directory));
        }

        $bootstrapInc = $directory . '/includes/bootstrap.inc';
        if (!is_file($bootstrapInc)) {
            throw new \RuntimeException(sprintf("%s: is a not a Drupal installation or version mismatch", $directory));
        }

        if (!$handle = fopen($bootstrapInc, 'r')) {
            throw new \RuntimeException(sprintf("%s: cannot open for reading", $bootstrapInc));
        }

        $buffer = fread($handle, 512);
        fclose($handle);

        $matches = [];
        if (preg_match("/^\s*define\('VERSION', '([^']+)'/ims", $buffer, $matches)) {
            list($parsedMajor) = explode('.', $matches[1]);
        }
        if (!isset($parsedMajor) || empty($parsedMajor)) {
            throw new \RuntimeException(sprintf("%s: could not parse core version", $bootstrapInc));
        }

        // realpath() is necessary in order to avoid symlinks messing up with
        // Drupal path when testing in a console which hadn't hardened the env
        // using a chroot() unlink PHP-FPM
        if (defined('DRUPAL_ROOT')) {
            if (DRUPAL_ROOT !== realpath($directory)) {
                throw new \LogicException(sprintf("'DRUPAL_ROOT' is already defined and does not point toward the same root"));
            }
        } else {
            define('DRUPAL_ROOT', realpath($directory));
        }

        require_once $bootstrapInc;

        self::bootstrapDrupalEnv();

        drupal_bootstrap(DRUPAL_BOOTSTRAP_DATABASE);

        return self::$databaseConnection = \Database::getConnection();
    }

    private function getDrupal7Cache() : \DrupalCacheInterface
    {
        return new Drupal7PsrCacheBackend($this->getCacheBackend());
    }

    private function getDrupal8Cache() : CacheBackendInterface
    {
        return new Drupal8PsrCacheBackend($this->getCacheBackend());
    }

    private function getDatabaseConnection() : \DatabaseConnection
    {
        return self::findDrupalDatabase();
    }

    protected function createSite() : int
    {
        if (!db_table_exists('ucms_site')) {
            return $this->parentCreateSite();
        }

        $database = $this->getDatabaseConnection();
        $siteHash = uniqid('test-');
        $groupId  = db_query("select id from {ucms_group} limit 1")->fetchField();

        return (int)$database
            ->insert('ucms_site')
            ->fields([
                'title'       => $siteHash,
                'title_admin' => $siteHash,
                'http_host'   => $siteHash.'.example.com',
                'home_nid'    => null,
                'group_id'    => $groupId,
            ])
            ->execute()
        ;
    }

    protected function createPage(string $title, ?int $siteId = null) : MockPage
    {
        if (!db_table_exists('node')) {
            return $this->parentCreatePage();
        }

        $database = $this->getDatabaseConnection();

        $id = (int)$database
            ->insert('node')
            ->fields([
                'title'   => $title,
                'type'    => 'test',
                'site_id' => $siteId,
            ])
            ->execute()
        ;

        if ($siteId) {
            $database
                ->insert('ucms_site_node')
                ->fields(['nid' => $id, 'site_id' => $siteId])
                ->execute()
            ;
        }

        return new MockPage($id, $title, $siteId);
    }

    protected function getItemStorage() : ItemStorageInterface
    {
        return new ItemStorage($this->getDatabaseConnection());
    }

    protected function getMenuStorage() : MenuStorageInterface
    {
        return new MenuStorage($this->getDatabaseConnection());
    }

    protected function getTreeProvider() : TreeProviderInterface
    {
        return new TreeProvider($this->getDatabaseConnection());
    }
}
