#!/bin/bash

# Install the latest version of THT

MIN_PHP_VERSION="8.3.0"
THT_REMOTE_URL="https://tht.dev/downloads/tht_v0_8_0.zip"
THT_TEMP_FILE=~/tht_v0_8_0.zip
THT_TEMP_DIR=~/tht_install
THT_INSTALL_DIR=~/tht
THT_COMMAND="$THT_INSTALL_DIR/run/tht.php"
THT_ALIAS="alias tht='php $THT_COMMAND'"

function error {
    # $1 = message
    printf "\n$1\n\n";
}

# $1 = bash profile fileName
function add_alias {

    if grep -q "alias tht" "$1"; then
        printf "[ OK ] 'tht' command already found in: $1\n"
        return
    fi

    # append to file and reload
    echo $THT_ALIAS >> $1
    . $1

    printf "[ OK ] 'tht' command added to: $1\n"
}

function install {

    printf "\n"
    printf "-----------------\n";
    printf "   INSTALL THT   \n";
    printf "-----------------\n";


    # Check that unzip is installed
    command -v unzip >/dev/null 2>&1 || {
        error "'unzip' command needed to continue.\n\nTry running: sudo apt-get install unzip"; return;
    }

    # Check that PHP is installed
    command -v php >/dev/null 2>&1 || {
        error "PHP must be installed to continue."; return;
    }

    # Check PHP version
    php -r "version_compare(PHP_VERSION, '$MIN_PHP_VERSION') < 0 ? exit(1) : exit(0);" || {
        error "PHP version must be >= $MIN_PHP_VERSION"; return;
    }

    # Download
    printf "\nDownloading $THT_REMOTE_URL...\n"
    curl -# -o $THT_TEMP_FILE $THT_REMOTE_URL
    [ -f $THT_TEMP_FILE ] || {
        error "Unable to download file."; return;
    }

    # Unzip
    unzip -q -o $THT_TEMP_FILE -d $THT_TEMP_DIR
    [ -d $THT_TEMP_DIR ] || {
        error "Unable to unzip '$THT_TEMP_FILE' to '$THT_TEMP_DIR'."; return;
    }

    # Remove previous
    [ -d $THT_INSTALL_DIR ] && { rm -rf $THT_INSTALL_DIR; }

    # Deploy and clean up
    mv $THT_TEMP_DIR/tht $THT_INSTALL_DIR
    rm -r $THT_TEMP_DIR
    rm $THT_TEMP_FILE


    printf "\n[ OK ] Unzip and deploy files to: $THT_INSTALL_DIR\n"


    # Create alias

    if [ -f ~/.bash_aliases ]
    then
        add_alias ~/.bash_aliases
    elif [ -f ~/.bash_profile ]
    then
        add_alias ~/.bash_profile
    elif [ -f ~/.profile ]
    then
        add_alias ~/.profile
    elif [ -f ~/.bashrc ]
    then
        add_alias ~/.bashrc
    elif [ -f ~/.zshrc ]
    then
        add_alias ~/.zshrc
    elif [ -f ~/.zprofile ]
    then
        add_alias ~/.zprofile
    else
        error "Unable to find your shell profile to create the `tht` alias."; return;
    fi

    printf "[ OK ] 'tht' command created\n"


    # Check that THT is installed
    command -v tht >/dev/null 2>&1 || {
        error "Test of 'tht' command failed."; return;
    }

    printf "[ OK ] 'tht' command verified\n"


    printf "\n--- SUCCESS! ---\n\n"
}


install


# end of script