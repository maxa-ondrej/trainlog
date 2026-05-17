#!/usr/bin/env bash
set -euo pipefail

# Wait for the database to accept connections before running migrations.
# DATABASE_URL is parsed for host:port; fallbacks let the script no-op when
# DATABASE_URL is missing (cache:clear at build time, etc.).
wait_for_db() {
    local url="${DATABASE_URL:-}"
    [[ -z "$url" ]] && return 0

    local host port
    host="$(echo "$url" | sed -E 's#^[a-z]+://[^@]+@([^:/?]+).*#\1#')"
    port="$(echo "$url" | sed -nE 's#^[a-z]+://[^@]+@[^:]+:([0-9]+).*#\1#p')"
    port="${port:-3306}"

    [[ -z "$host" || "$host" == "$url" ]] && return 0

    echo "Waiting for database $host:$port ..."
    for _ in $(seq 1 60); do
        if (echo > "/dev/tcp/$host/$port") >/dev/null 2>&1; then
            echo "Database is up."
            return 0
        fi
        sleep 1
    done
    echo "Database did not become available in 60s." >&2
    return 1
}

if [[ "${APP_RUN_MIGRATIONS:-1}" == "1" ]]; then
    wait_for_db
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

if [[ "${APP_LOAD_FIXTURES:-0}" == "1" ]]; then
    # Purges (via DELETE; --purge-with-truncate breaks on FK constraints in
    # MariaDB) then reloads. Opting into APP_LOAD_FIXTURES is a destructive
    # demo-reset, never a partial seed.
    php bin/console doctrine:fixtures:load --no-interaction
fi

# Cache was warmed at build time; re-warm only if it was wiped (e.g. mounted volume).
if [[ ! -f var/cache/prod/App_KernelProdContainer.php ]]; then
    php bin/console cache:clear --env=prod --no-debug
fi

exec "$@"
