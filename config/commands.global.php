<?php

use Onion\Tool\Initialize\Command as InitCommand;
use Onion\Tool\Version\Command as VersionCommand;
use Onion\Tool\Package\Command as PackageCommand;
use Onion\Tool\Compile\Command as CompileCommand;
use Onion\Tool\Watch\Command as WatchCommand;

return [
    'commands' => [
        [
            'definition' => 'version',
            'handler' => VersionCommand::class,
            'summary' => 'Display the current tool version',
        ], [
            'definition' => 'init',
            'handler' => InitCommand::class,
            'summary' => 'Initialize an onion project',
            'parameters' => [
                [
                    'name' => '--no-prompt',
                    'type' => 'bool',
                    'description' => 'Create manifest file without asking for user input',
                ]
            ],
        ], [
            'definition' => 'package',
            'handler' => PackageCommand::class,
            'summary' => 'Package the current project into a PHAR',
            'parameters' => [
                [
                    'name' => '--location | --dir | -l',
                    'type' => 'string',
                    'description' => 'Output directory',
                    'default' => './build/',
                ], [
                    'name' => '--compression | -c',
                    'type' => 'string',
                    'description' => 'The compression algorithm to use for compression. Allowed values are gz|bz|none',
                    'default' => 'none',
                ], [
                    'name' => '--signature | -s',
                    'type' => 'string',
                    'description' => 'The signature algorithm to use for signature generation. Allowed values are sha1|sha256|sha512',
                    'default' => 'sha256',
                ], [
                    'name' => '--standalone',
                    'type' => 'bool',
                    'description' => 'Compile the package as a standalone package ready to be executed instead of a module',
                    'default' => false,
                ], [
                    'name' => '--debug | -d',
                    'type' => 'bool',
                    'description' => 'Mark the build as development build',
                    'default' => false,
                ]
            ],
        ], [
            'definition' => 'compile',
            'handler' => CompileCommand::class,
            'summary' => 'Compile project configuration files for static optimization',
            'parameters' => [
                [
                    'name' => '--environment | --env | -e',
                    'type' => 'string',
                    'description' => 'Environment files to load',
                ], [
                    'name' => '--config | --cfg | -c',
                    'type' => 'string',
                    'description' => 'Directory in which to look for configuration files',
                ], [
                    'name' => '--dev',
                    'type' => 'bool',
                    'description' => 'Indicate that autoload-dev files should be used',
                    'default' => false,
                ]
            ],
        ], [
            'definition' => 'watch [directory] [command]',
            'handler' => WatchCommand::class,
            'summary' => 'Watch directory for file changes',
            'parameters' => [
                [
                    'name' => '--interval | -n',
                    'type' => 'integer',
                    'description' => 'Interval to check for new files',
                    'default' => 2,
                ], [
                    'name' => 'directory',
                    'type' => 'string',
                    'description' => 'The directory to watch'
                ], [
                    'name' => 'command',
                    'type' => 'string',
                    'description' => 'Command to run when a file is changed',
                ]
            ],
        ]
    ],
];
