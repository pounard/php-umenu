<?php

namespace MakinaCorpus\Umenu\Bridge\Drupal;

/**
 * @codeCoverageIgnore
 */
final class DrupalSchema
{
    /**
     * Get a Drupal 7 compatible version of the schema
     */
    public static function getSchemaForDrupal7() : array
    {
        $schema = [];

        $schema['umenu'] = [
            'description' => 'Holds definitions for top-level custom menus (for example, Main menu).',
            'fields' => [
                'id' => [
                    'description' => 'Internal primary key',
                    'type'        => 'serial',
                    'unsigned'    => true,
                    'not null'    => true,
                ],
                'site_id' => [
                    'description' => 'Site identifier',
                    'type'        => 'int',
                    'unsigned'    => true,
                    'not null'    => false,
                    'default'     => null,
                ],
                'is_main' => [
                    'description' => 'Is this menu the main menu for the given site',
                    'type'        => 'int',
                    'unsigned'    => true,
                    'not null'    => true,
                    'default'     => 0,
                ],
                'role' => [
                    'description' => 'Menu role, for business layer this could mean anything',
                    'type'        => 'varchar',
                    'length'      => 64,
                    'not null'    => false,
                    'default'     => null,
                ],
                'name' => [
                    'description' => 'Primary Key: Unique key for menu. This is used as a block delta so length is 32',
                    'type'        => 'varchar',
                    'length'      => 32,
                    'not null'    => true,
                ],
                'title' => [
                    'description' => 'Menu title; displayed at top of block',
                    'type'        => 'varchar',
                    'length'      => 255,
                    'not null'    => true,
                ],
                'description' => [
                    'description' => 'Menu description',
                    'type'        => 'text',
                    'not null'    => false,
                ],
            ],
            'primary key' => ['id'],
            'unique keys' => [
                'uk_umenu_name' => ['name'],
            ],
            'foreign keys' => [
                'item_site' => [
                    'table'   => 'ucms_site',
                    'columns' => ['site_id' => 'id'],
                    'delete'  => 'cascade',
                ],
            ],
        ];

        $schema['umenu_item'] = [
            'description' => 'Holds menu items',
            'fields' => [
                'id' => [
                    'description' => 'Primary key',
                    'type'        => 'serial',
                    'unsigned'    => true,
                    'not null'    => true,
                ],
                'menu_id' => [
                    'description' => 'Menu identifier',
                    'type'        => 'int',
                    'unsigned'    => true,
                    'not null'    => true,
                ],
                'site_id' => [
                    'description' => 'Site identifier denormalization, used for foreign key constraints',
                    'type'        => 'int',
                    'unsigned'    => true,
                    'not null'    => false,
                    'default'     => null,
                ],
                'node_id' => [
                    'description' => 'Node (content) identifier',
                    'type'        => 'int',
                    'unsigned'    => true,
                    'not null'    => true,
                ],
                'parent_id' => [
                    'description' => 'Parent identifier refering to this table primary key',
                    'type'        => 'int',
                    'unsigned'    => true,
                    'not null'    => false,
                    'default'     => null,
                ],
                'weight' => [
                    'description' => 'Menu item order relative to its parent',
                    'type'        => 'int',
                    'unsigned'    => false,
                    'not null'    => true,
                    'default'     => 0,
                ],
                'title' => [
                    'description' => 'Menu item title, used for display, content title is used if null',
                    'type'        => 'varchar',
                    'length'      => 255,
                    'not null'    => false,
                    'default'     => null,
                ],
                'description' => [
                    'description' => 'Menu item description, may be used for display.',
                    'type'        => 'text',
                    'not null'    => false,
                    'default'     => null,
                ],
            ],
            'primary key' => ['id'],
            'foreign keys' => [
                'item_menu' => [
                    'table'   => 'umenu',
                    'columns' => ['menu_id' => 'id'],
                    'delete'  => 'cascade',
                ],
                'item_parent' => [
                    'table'   => 'umenu_item',
                    'columns' => ['parent_id' => 'id'],
                    'delete'  => 'set null',
                ],
                'item_node_site' => [
                    'table'   => 'ucms_site_node',
                    'columns' => [
                        'site_id' => 'site_id',
                        'node_id' => 'nid',
                    ],
                    'delete'  => 'cascade',
                ],
            ]
        ];

        return $schema;
    }
}
