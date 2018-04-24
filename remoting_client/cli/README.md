# ISPConfig CLI
Command line for ISPConfig remote user REST API using either smart functions or raw methods.

## Getting Started
This tool can be used and packaged stand alone, without ISPConfig itself. It is designed to have as few dependencies as possible.

The script has two main modes: smart functions and raw methods.

Raw methods simply wrap your JSON to the arbitrary method name you've given. As such, it works with any method, and makes properly formatted requests with curl. It is a handy tool for custom requests, testing, advanced scripting and integration work.

Functions in turn are combinations of methods and checks that act more like an intelligent tool and does not require the user to understand JSON. This is handy for manual interaction or for scripting.

> **Note:**
Consider using -q for scripting, this will suppress everything but results and errors on the output.

### Example function usage:
    ispconfig-cli -f "dns_a_add example.com. www 192.168.0.2"

    DNS zone example.com. has id 1.
    DNS A www exists with id 228, updating...
    Updated records: 1

### Example method usage:
    ispconfig-cli -m login -j credentials.json

    {"code":"ok","message":"","response":"dc39619b0ac9694cb85e93d8b3ac8258"}

> **Note:**
The whole function has to be quoted as one due to how bash manages the command line arguments.

### Config file
The script uses an optional config file, allowing commands as short as above.

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. See deployment for 
notes on how to deploy the project on a live system.

## Dependencies
- ```jq``` for working with JSON
- ```curl``` for talking to the endpoint

On debian-based distributions such as ubuntu, you can ensure these are installed by running
    sudo apt install jq curl

## Installing
1. Place this script in your path, for example in your ```~/bin``` folder on many distros.
2. Make it executable ```chmod 755 ispconfig-cli```
3. Optionally create a config file in ```/etc/ispconfig-cli.conf``` or ```~/.ispconfig-cli```

## Details on usage
Run the script without arguments for the full list of functionality and config file creation instructions.
