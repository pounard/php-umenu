<?php

declare(strict_types=1);

namespace MakinaCorpus\Umenu\Bridge\Goat;

use Goat\Bundle\Installer\Updater;
use Goat\Runner\RunnerInterface;
use Goat\Runner\Transaction;

/**
 * As of now, this schema is PostgreSQL only.
 */
class MenuUpdater extends Updater
{
    /**
     * {@inheritdoc}
     */
    public function getAlias() : string
    {
        return 'umenu';
    }

    /**
     * {@inheritdoc}
     */
    public function installSchema(RunnerInterface $runner, Transaction $transaction)
    {
        $runner->query(<<<EOT
CREATE TABLE umenu (
    id SERIAL PRIMARY KEY,
    name VARCHAR(32) NOT NULL,
    site_id INTEGER DEFAULT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT '',
    is_main BOOL DEFAULT false NOT NULL,
    role VARCHAR(64) DEFAULT NULL
);
EOT
        );

        $runner->query(<<<EOT
CREATE TABLE umenu_item (
    id SERIAL PRIMARY KEY,
    menu_id INTEGER NOT NULL,
    site_id INTEGER DEFAULT NULL,
    page_id INTEGER NOT NULL,
    parent_id INTEGER DEFAULT NULL,
    weight INTEGER DEFAULT 0 NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL DEFAULT '',
    FOREIGN KEY (menu_id) REFERENCES umenu (id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES umenu_item (id) ON DELETE SET NULL
);
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    public function uninstallSchema(RunnerInterface $runner, Transaction $transaction)
    {
        $runner->query("DROP TABLE umenu_item");
        $runner->query("DROP TABLE umenu");
    }
}
