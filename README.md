# tool

```
List of available commands

COMMAND         version  [--quiet | -q] [--verbose | -v] [--no-colors] [--help | -h] [--compact-output | --minimal-output]
SUMMARY         Display Current version
ARGUMENTS
    bool        --quiet | -q
        Suppress all command output

    bool        --verbose | -v
        Indicate that the command may output extended information

    bool        --no-colors
        Disable all color outputs

    bool        --help | -h
        Display help info

    bool        --compact-output | --minimal-output
        Indicate that only minimal output should be emitted by the command



COMMAND         init  [--quiet | -q] [--verbose | -v] [--no-colors] [--help | -h] [--compact-output | --minimal-output]
SUMMARY         Initialize onion project
ARGUMENTS
    bool        --quiet | -q
        Suppress all command output

    bool        --verbose | -v
        Indicate that the command may output extended information

    bool        --no-colors
        Disable all color outputs

    bool        --help | -h
        Display help info

    bool        --compact-output | --minimal-output
        Indicate that only minimal output should be emitted by the command



COMMAND         build  [--filename | -f = <vendor_package>] [--location | --dir] [--bump] [--compression | -c = none] [--signature | -s = sha512] [--pre] [--quiet | -q] [--verbose | -v] [--no-colors] [--help | -h] [--compact-output | --minimal-output]
SUMMARY         Build the current package
ARGUMENTS
    string      --filename | -f=<vendor_package>
        The filename with with which to save the built package

    string      --location | --dir
        Directory in which to put the build artifact

    string      --bump
        Define which part of the package version to bump: `major`, `minor` or `fix`

    string      --compression | -c=none
        Type of compression to use, either `gz`, `bz` or `none`

    string      --signature | -s=sha512
        Signature algo to use for the generated file.

    string      --pre
        Indicate this build is pre-release

    bool        --quiet | -q
        Suppress all command output

    bool        --verbose | -v
        Indicate that the command may output extended information

    bool        --no-colors
        Disable all color outputs

    bool        --help | -h
        Display help info

    bool        --compact-output | --minimal-output
        Indicate that only minimal output should be emitted by the command



COMMAND         link  [--add | -a] [--list | -l] [--quiet | -q] [--verbose | -v] [--no-colors] [--help | -h] [--compact-output | --minimal-output]
SUMMARY         Perform actions on lists
ARGUMENTS
    bool        --add | -a
        Add a new link entry

    bool        --list | -l
        List all defined links

    bool        --quiet | -q
        Suppress all command output

    bool        --verbose | -v
        Indicate that the command may output extended information

    bool        --no-colors
        Disable all color outputs

    bool        --help | -h
        Display help info

    bool        --compact-output | --minimal-output
        Indicate that only minimal output should be emitted by the command



COMMAND         command  [--add | -a] [--delete | -d] [--list | -l] [--quiet | -q] [--verbose | -v] [--no-colors] [--help | -h] [--compact-output | --minimal-output]
SUMMARY         Manipulate commands
ARGUMENTS
    bool        --add | -a
        Add a command

    bool        --delete | -d
        Delete a command

    bool        --list | -l
        List all commands

    bool        --quiet | -q
        Suppress all command output

    bool        --verbose | -v
        Indicate that the command may output extended information

    bool        --no-colors
        Disable all color outputs

    bool        --help | -h
        Display help info

    bool        --compact-output | --minimal-output
        Indicate that only minimal output should be emitted by the command

```
