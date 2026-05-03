#!/bin/sh

if [ -x "/opt/homebrew/bin/node" ]; then
  exec /opt/homebrew/bin/node "$@"
fi

if [ -n "$HOMEBREW_PREFIX" ] && [ -x "$HOMEBREW_PREFIX/bin/node" ]; then
  exec "$HOMEBREW_PREFIX/bin/node" "$@"
fi

exec node "$@"
