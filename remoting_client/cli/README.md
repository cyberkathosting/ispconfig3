# ISPConfig CLI
Command line for ISPConfig remote user REST API using either smart functions or raw methods.

## Getting Started
This tool can be used and packaged stand alone, without requiring ISPConfig to be installed locally. It is designed to have as few dependencies as possible.

The script has two main modes: smart functions and raw methods.

Raw methods simply wrap your JSON and use the arbitrary method name you've given when calling the API. As such, it works with any method, and makes properly formatted requests with curl. It is a handy tool for custom requests, testing, advanced scripting and integration work. The actual data is provided through a file, stdin or as an argument.

Functions, on the other hand, are combinations of methods and checks that act more like an intelligent tool and does not require the user to understand JSON. The functions are designed based on their method equivalent, but requiring only a single command and automating the rest (login, get ID's, check existing records etc.). This is handy for manual interaction or for scripting. Unlike methods, functions are limited to those methods implemented in the script itself. Functions are named as their method counterparts. The exception are ```login``` and ```logout``` in order to avoid collisions with system commands. They are called log_in and log_out, respectively.

Functions can also be processed as a batch from file or stdin, optimizing performance by using a single session and single bash instance.

> **Note:**
Consider using ```-q``` for scripting, this will suppress everything but results and errors on the output.

### Example smart function usage:
    ispconfig-cli -f "dns_a_add example.com. www 192.168.0.2"
    DNS zone example.com. has id 1.
    DNS A www exists with id 228, updating...
    Updated records: 1

### Example raw method usage:
    ispconfig-cli -m login -j credentials.json
    {"code":"ok","message":"","response":"dc39619b0ac9694cb85e93d8b3ac8258"}

> **Note:**
The whole function has to be quoted as one due to how bash manages the command line arguments.

### Config file
The script uses an optional config file, allowing commands as short as in the examples above.

## Dependencies
- ```jq``` for working with JSON
- ```curl``` for talking to the endpoint

On debian-based distributions such as ubuntu, you can ensure these are installed by running

    sudo apt install jq curl

## Installing
1. Place this script in your path. For example, placing it in your ```~/bin``` folder (and logging out and back in if you just created the folder) works on many distributions.
2. Make it executable by running ```chmod 755 ispconfig-cli```.
3. Optionally create a config file in ```/etc/ispconfig-cli.conf``` or ```~/.ispconfig-cli```.

## Details on usage
Run the script without arguments or with ```-h``` for the full list of functionality and config file creation instructions.
