# tool

```
List of available commands

COMMAND         version
SUMMARY         Display current version
ARGUMENTS
    bool        --quiet | -q

         Suppress all command output


    bool        --verbose | -vvv

         Indicate that the command may output extended information


    bool        --no-colors | --no-color

         Indicate that the command output should not include colors




COMMAND         update
SUMMARY         Update tool to latest version
ARGUMENTS
    bool        --rollback

         Rollback to previous version


    bool        --force

         Force update to latest version


    bool        --quiet | -q

         Suppress all command output


    bool        --verbose | -vvv

         Indicate that the command may output extended information


    bool        --no-colors | --no-color

         Indicate that the command output should not include colors




COMMAND         init
SUMMARY         Initialize an empty onion project
ARGUMENTS
    bool        --no-prompt

         Create manifest file without asking for user input


    bool        --quiet | -q

         Suppress all command output


    bool        --verbose | -vvv

         Indicate that the command may output extended information


    bool        --no-colors | --no-color

         Indicate that the command output should not include colors




COMMAND         package
SUMMARY         Package the current project
ARGUMENTS
    string      --location | --dir | -l  ./build/

         The directory in which to put the artefact


    string      --compression | -c       none

         The compression to use. Allowed one of 'gz', 'bz' or 'none'


    string      --signature | -s         sha256

         The signature algorithm to use Allowed one of 'sha1', 'sha256' or 'sha512'


    bool        --standalone

         Mark build as standalone


    bool        --debug

         Mark build as debug


    bool        --quiet | -q

         Suppress all command output


    bool        --verbose | -vvv

         Indicate that the command may output extended information


    bool        --no-colors | --no-color

         Indicate that the command output should not include colors




COMMAND         publish
SUMMARY         Publish current package
ARGUMENTS
    string      --auth   password

         Authentication method to use


    string      --secret

         The secret/token to use for authentication


    string      --credential

         The credential (if any) used for authentication


    bool        --draft | -d

         mark release as pre-release


    bool        --quiet | -q

         Suppress all command output


    bool        --verbose | -vvv

         Indicate that the command may output extended information


    bool        --no-colors | --no-color

         Indicate that the command output should not include colors




COMMAND         link <operation>
SUMMARY         Manipulate manifest links
ARGUMENTS
    bool        --quiet | -q

         Suppress all command output


    bool        --verbose | -vvv

         Indicate that the command may output extended information


    bool        --no-colors | --no-color

         Indicate that the command output should not include colors




COMMAND         compile
SUMMARY         Compile project configurations
ARGUMENTS
    string      --environment | --env | -e

         Environment configs to load


    string      --config-dir | --dir | -c

         Directory in which to look for configuration files


    bool        --dev

         Whether to include `autoload-dev` handling


    bool        --quiet | -q

         Suppress all command output


    bool        --verbose | -vvv

         Indicate that the command may output extended information


    bool        --no-colors | --no-color

         Indicate that the command output should not include colors




COMMAND         module <action> <module>
SUMMARY         Manage modules
ARGUMENTS
    string      --constraint | -c

         Constraint to use when installing/updating a module


    string      --alias | --as | -a

         Alias for when loading a module


    string      --repository | --repo | -r

         The repository name to use when installing/updating a module


    bool        --quiet | -q

         Suppress all command output


    bool        --verbose | -vvv

         Indicate that the command may output extended information


    bool        --no-colors | --no-color

         Indicate that the command output should not include colors




COMMAND         play <stage>
SUMMARY         Execute recipe steps
ARGUMENTS
    string      --config | -c    onion.recipe.yml

         The recipe configuration file


    bool        --quiet | -q

         Suppress all command output


    bool        --verbose | -vvv

         Indicate that the command may output extended information


    bool        --no-colors | --no-color

         Indicate that the command output should not include colors


```
