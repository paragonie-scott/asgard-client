# Installing ASGard

## Quick Install

Clone our git repository, then run the appropriate shell script in the `install` 
directory in the root directory of the `asgard-client` project, depending on 
your Operating System.

For example:

```sh
git clone https://github.com/paragonie/asgard-client.git
git checkout -b stable
# TODO: Add a note about verifying git tags with our GnuPG public key here!
cd asgard-client
cd install
sudo ./debian.sh
```

The scripts in this directory will install of the necessary dependencies.