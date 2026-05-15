{ pkgs ? import <nixpkgs> {} }:

let
    php = pkgs.php84.buildEnv {
        extensions = ({ enabled, all }: enabled ++ (with all; [
            pdo_mysql
            intl
            zip
            mbstring
            opcache
            sodium
            xsl
            gd
            bcmath
        ]));
        extraConfig = ''
            memory_limit = 512M
            date.timezone = Europe/Prague
        '';
    };
in
pkgs.mkShell {
    packages = [
        php
        php.packages.composer
        pkgs.symfony-cli
        pkgs.nodejs_22
    ];

    shellHook = ''
        echo "TrainLog dev shell — PHP $(php -r 'echo PHP_VERSION;')"
    '';
}
