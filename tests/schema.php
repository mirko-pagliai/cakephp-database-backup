<?php
declare(strict_types=1);

return [
    'users' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'username' => [
                'type' => 'string',
                'null' => true,
            ],
            'password' => [
                'type' => 'string',
                'null' => true,
            ],
            'created' => [
                'type' => 'timestamp',
                'null' => true,
            ],
            'updated' => [
                'type' => 'timestamp',
                'null' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    'auth_users' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'username' => [
                'type' => 'string',
                'null' => false,
            ],
            'password' => [
                'type' => 'string',
                'null' => false,
            ],
            'created' => 'datetime',
            'updated' => 'datetime',
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    'date_keys' => [
        'columns' => [
            'id' => [
                'type' => 'date',
            ],
            'title' => [
                'type' => 'string',
                'null' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    'members' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'section_count' => [
                'type' => 'integer',
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    'articles' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'author_id' => [
                'type' => 'integer',
                'null' => true,
            ],
            'title' => [
                'type' => 'string',
                'null' => true,
            ],
            'body' => 'text',
            'published' => [
                'type' => 'string',
                'length' => 1,
                'default' => 'N',
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    'products' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'category' => [
                'type' => 'integer',
                'null' => false,
            ],
            'name' => [
                'type' => 'string',
                'null' => false,
            ],
            'price' => [
                'type' => 'integer',
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'category',
                    'id',
                ],
            ],
        ],
    ],
    'orders' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'product_category' => [
                'type' => 'integer',
                'null' => false,
            ],
            'product_id' => [
                'type' => 'integer',
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
            'product_category_fk' => [
                'type' => 'foreign',
                'columns' => [
                    'product_category',
                    'product_id',
                ],
                'references' => [
                    'products',
                    [
                        'category',
                        'id',
                    ],
                ],
                'update' => 'cascade',
                'delete' => 'cascade',
            ],
        ],
        'indexes' => [
            'product_category' => [
                'type' => 'index',
                'columns' => [
                    'product_category',
                    'product_id',
                ],
            ],
        ],
    ],
    'comments' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'article_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'user_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'comment' => [
                'type' => 'text',
            ],
            'published' => [
                'type' => 'string',
                'length' => 1,
                'default' => 'N',
            ],
            'created' => [
                'type' => 'datetime',
            ],
            'updated' => [
                'type' => 'datetime',
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    'authors' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'name' => [
                'type' => 'string',
                'default' => null,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    'tags' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'name' => [
                'type' => 'string',
                'null' => false,
            ],
            'description' => [
                'type' => 'text',
                'length' => 16777215,
            ],
            'created' => [
                'type' => 'datetime',
                'null' => true,
                'default' => null,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    'articles_tags' => [
        'columns' => [
            'article_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'tag_id' => [
                'type' => 'integer',
                'null' => false,
            ],
        ],
        'constraints' => [
            'unique_tag' => [
                'type' => 'primary',
                'columns' => [
                    'article_id',
                    'tag_id',
                ],
            ],
        ],
    ],
    'profiles' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
                'null' => false,
                'autoIncrement' => true,
            ],
            'user_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'first_name' => [
                'type' => 'string',
                'null' => true,
            ],
            'last_name' => [
                'type' => 'string',
                'null' => true,
            ],
            'is_active' => [
                'type' => 'boolean',
                'null' => false,
                'default' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    'sessions' => [
        'columns' => [
            'id' => [
                'type' => 'string',
                'length' => 128,
            ],
            'data' => [
                'type' => 'binary',
                'length' => 16777215,
                'null' => true,
            ],
            'expires' => [
                'type' => 'integer',
                'length' => 11,
                'null' => true,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    'menu_link_trees' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'menu' => [
                'type' => 'string',
                'null' => false,
            ],
            'lft' => [
                'type' => 'integer',
            ],
            'rght' => [
                'type' => 'integer',
            ],
            'parent_id' => 'integer',
            'url' => [
                'type' => 'string',
                'null' => false,
            ],
            'title' => [
                'type' => 'string',
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    'things' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'title' => [
                'type' => 'string',
                'length' => 20,
            ],
            'body' => [
                'type' => 'string',
                'length' => 50,
            ],
        ],
    ],
    'site_articles' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'author_id' => [
                'type' => 'integer',
                'null' => true,
            ],
            'site_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'title' => [
                'type' => 'string',
                'null' => true,
            ],
            'body' => 'text',
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                    'site_id',
                ],
            ],
        ],
    ],
    'authors_tags' => [
        'columns' => [
            'author_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'tag_id' => [
                'type' => 'integer',
                'null' => false,
            ],
        ],
        'constraints' => [
            'unique_tag' => [
                'type' => 'primary',
                'columns' => [
                    'author_id',
                    'tag_id',
                ],
            ],
            'author_id_fk' => [
                'type' => 'foreign',
                'columns' => ['author_id'],
                'references' => ['authors', 'id'],
                'update' => 'cascade',
                'delete' => 'cascade',
            ],
        ],
    ],
    'site_authors' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'name' => [
                'type' => 'string',
                'default' => null,
            ],
            'site_id' => [
                'type' => 'integer',
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                    'site_id',
                ],
            ],
        ],
    ],
    'posts' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'author_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'title' => [
                'type' => 'string',
                'null' => false,
            ],
            'body' => 'text',
            'published' => [
                'type' => 'string',
                'length' => 1,
                'default' => 'N',
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    'attachments' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'comment_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'attachment' => [
                'type' => 'string',
                'null' => false,
            ],
            'created' => 'datetime',
            'updated' => 'datetime',
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    'categories' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'parent_id' => [
                'type' => 'integer',
                'null' => false,
            ],
            'name' => [
                'type' => 'string',
                'null' => false,
            ],
            'created' => 'datetime',
            'updated' => 'datetime',
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    'sections' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'title' => [
                'type' => 'string',
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                ],
            ],
        ],
    ],
    'site_tags' => [
        'columns' => [
            'id' => [
                'type' => 'integer',
            ],
            'site_id' => [
                'type' => 'integer',
            ],
            'name' => [
                'type' => 'string',
                'null' => false,
            ],
        ],
        'constraints' => [
            'primary' => [
                'type' => 'primary',
                'columns' => [
                    'id',
                    'site_id',
                ],
            ],
        ],
    ],
];
